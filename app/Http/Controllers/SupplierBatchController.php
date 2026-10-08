<?php

namespace App\Http\Controllers;

use App\Models\SupplierBatch;
use App\Models\GoodsReceiptItem;
use App\Services\InventoryLedger;
use DomainException;
use App\Services\SupplierBatchAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SupplierBatchController extends Controller
{
    public function updateCoa(Request $request, SupplierBatch $supplierBatch)
    {
        if (!auth()->user()->isQADepartment() && !auth()->user()->isITDepartment()) {
            return back()->with('error', 'Chỉ bộ phận QA mới có quyền cập nhật hồ sơ COA.');
        }
        if ($supplierBatch->status === 'rejected') {
            return back()->with('error', 'Lô đã bị QC kết luận không đạt và lập đơn trả; không thể kích hoạt lại.');
        }

        $request->validate([
            'batch_number' => 'required|string|max:255',
            'mfg_date'     => 'required|date',
            'exp_date'     => 'required|date|after_or_equal:mfg_date',
            'coa_file'     => 'nullable|mimes:pdf|max:10240', // File PDF tối đa 10MB
        ]);

        // Xử lý upload file PDF COA nếu QA có chọn file
        $oldPath = $supplierBatch->coa_file;
        $path = $supplierBatch->coa_file; // Giữ nguyên file cũ nếu không upload file mới
        
        if ($request->hasFile('coa_file')) {
            $path = $request->file('coa_file')->store('coas', 'local');
        }

        if (!$path) {
            return back()->withInput()->with('error', 'Cần tải COA PDF lên trước khi QA cho phép sử dụng lô.');
        }

        // Cập nhật COA không đổi trạng thái; chỉ QC xác nhận đạt mới kích hoạt lô.
        $supplierBatch->update([
            'batch_number' => $request->batch_number,
            'mfg_date'     => $request->mfg_date,
            'exp_date'     => $request->exp_date,
            'coa_file'     => $path,
        ]);

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'QA đã cập nhật thông tin lô và file COA. Lô chỉ được kích hoạt sau khi QC xác nhận đạt.');
    }

    public function approve(SupplierBatch $supplierBatch)
    {
        abort_unless(auth()->user()->isQCDepartment() || auth()->user()->isITDepartment(), 403);

        try {
            DB::transaction(function () use ($supplierBatch) {
                $batch = SupplierBatch::with('goodsReceiptItem.goodsReceipt')
                    ->whereKey($supplierBatch->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($batch->status !== 'pending_qa') {
                    throw new DomainException('Chỉ được xác nhận đạt lô NCC đang chờ QC.');
                }
                if ((float) $batch->current_quantity <= 0) {
                    throw new DomainException('Lô không còn tồn để kích hoạt.');
                }

                $batch->update(['status' => 'active']);
                $this->syncGoodsReceiptQcStatus($batch);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'QC đã xác nhận chất lượng đạt, lô NCC được kích hoạt.');
    }

    private function syncGoodsReceiptQcStatus(SupplierBatch $supplierBatch): void
    {
        $receipt = $supplierBatch->goodsReceiptItem?->goodsReceipt;
        if (!$receipt) {
            return;
        }

        $batches = SupplierBatch::query()
            ->whereHas('goodsReceiptItem', fn ($query) => $query->where('goods_receipt_id', $receipt->id))
            ->get(['status']);
        $hasPending = $batches->contains(fn ($batch) => $batch->status === 'pending_qa');
        $passedCount = $batches->where('status', 'active')->count();
        $rejectedCount = $batches->where('status', 'rejected')->count();
        $hasImmediateReturn = GoodsReceiptItem::query()
            ->where('goods_receipt_id', $receipt->id)
            ->where('returned_quantity', '>', 0)
            ->exists();

        $status = match (true) {
            $hasPending => 'pending',
            $passedCount > 0 && ($rejectedCount > 0 || $hasImmediateReturn) => 'partially_passed',
            $passedCount > 0 => 'passed',
            default => 'failed',
        };
        $receipt->update(['qc_status' => $status]);
    }

    public function index(Request $request, SupplierBatchAvailability $availability)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        // Lọc các lô kèm thông tin sản phẩm, phiếu nhập và nhà cung cấp
        $query = SupplierBatch::with(['product', 'goodsReceiptItem.goodsReceipt.purchaseOrder.supplier']);

        // Bộ lọc tìm kiếm theo số lô hoặc tên sản phẩm
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                ->orWhereHas('product', function($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Bộ lọc trạng thái COA (chưa có hoặc đã có)
        if ($request->filled('status')) {
            if ($request->status == 'missing') {
                $query->whereNull('coa_file');
            } elseif ($request->status == 'uploaded') {
                $query->whereNotNull('coa_file');
            }
        }

        $batches = $query->latest()->paginate($this->perPage($request))->withQueryString();
        $availability->addTo($batches->getCollection());

        $canManageBatches = $user && ((method_exists($user, 'isQCDepartment') && $user->isQCDepartment()) || (method_exists($user, 'isQADepartment') && $user->isQADepartment()) || (method_exists($user, 'isITDepartment') && $user->isITDepartment()) || in_array($user->role ?? '', ['qc', 'qc_manager', 'qa', 'qa_manager'], true));

        return view('qa.batches.index', compact('batches', 'canManageBatches'));
    }

    public function coasIndex(Request $request, SupplierBatchAvailability $availability)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        // Lọc các lô kèm thông tin sản phẩm, phiếu nhập và nhà cung cấp cho trang COA riêng biệt
        $query = SupplierBatch::with(['product', 'goodsReceiptItem.goodsReceipt.purchaseOrder.supplier']);

        // Bộ lọc tìm kiếm theo số lô hoặc tên sản phẩm
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Bộ lọc trạng thái COA (chưa có hoặc đã có)
        if ($request->filled('status')) {
            if ($request->status == 'missing') {
                $query->whereNull('coa_file');
            } elseif ($request->status == 'uploaded') {
                $query->whereNotNull('coa_file');
            }
        }

        $batches = $query->latest()->paginate($this->perPage($request))->withQueryString();
        $availability->addTo($batches->getCollection());

        $canManageCoas = $user && ((method_exists($user, 'isQADepartment') && $user->isQADepartment()) || (method_exists($user, 'isITDepartment') && $user->isITDepartment()));

        return view('qa.coas.index', compact('batches', 'canManageCoas'));
    }
    /**
     * Tìm kiếm lô nhà cung cấp phục vụ cho Select2 Ajax.
     */
    public function searchAjax(Request $request)
    {
        $search = $request->input('q');

        $batches = SupplierBatch::with('product')
            ->where('status', 'active') // Chỉ lấy các lô đã active (đã được QA duyệt)
            ->where('current_quantity', '>', 0) // Chỉ lấy các lô còn tồn kho
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('batch_number', 'like', "%{$search}%")
                      ->orWhereHas('product', function ($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->limit(20)
            ->get();

        // Trả về dữ liệu chuẩn cấu trúc JSON cho Select2
        return response()->json($batches->map(function ($batch) {
            $productName = $batch->product->name ?? 'Không rõ sản phẩm';
            $stockQty = $batch->current_quantity; // Lấy số lượng tồn kho
            
            return [
                'id'   => $batch->id,
                'text' => "{$batch->batch_number} - {$productName} (Tồn: {$stockQty})"
            ];
        }));
    }
}