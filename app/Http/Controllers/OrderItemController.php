<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    private function canManageLockedOrder(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    private function canManageSalesOrder(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            $this->canManageLockedOrder()
        );
    }

    private function orderIsLocked(Order $order): bool
    {
        return in_array($order->status, ['pending_sales_approval', 'pending_warehouse_check', 'pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true);
    }

    /**
     * Thêm một vị thuốc mới vào đơn hàng đã tồn tại.
     */
    public function store(Request $request, string $orderId)
    {
        $order = Order::findOrFail($orderId);
        abort_unless($this->canManageSalesOrder(), 403);
        if ($this->orderIsLocked($order) && ! $this->canManageLockedOrder()) {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn đã gửi sản xuất, chỉ IT mới được sửa sản phẩm.');
        }

        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id', // Hoặc required tùy logic của bạn
            'product_name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'origin' => 'nullable|string|max:255',
            'qcdg' => 'nullable|numeric|min:0',
            'ppcb_name' => 'nullable|string|max:255',
            'ppcb_ma' => 'nullable|string|max:50',
            'qa_batch_or_supplier' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $order->items()->create($validated);

        return redirect()->route('orders.show', $order->id)->with('success', 'Đã thêm vị thuốc vào đơn hàng thành công!');
    }

    /**
     * QA cập nhật chỉ định lô nội bộ hoặc nhà cung cấp cho vị thuốc.
     */
    public function updateQa(Request $request, string $itemId)
    {
        $item = OrderItem::findOrFail($itemId);
        $order = $item->order;
        abort_unless(auth()->user() && (
            (method_exists(auth()->user(), 'isQADepartment') && auth()->user()->isQADepartment()) ||
            $this->canManageLockedOrder()
        ), 403);
        if ($order && $this->orderIsLocked($order) && ! $this->canManageLockedOrder()) {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn đã gửi sản xuất, chỉ IT mới được sửa số lô.');
        }

        $validated = $request->validate([
            'qa_batch_or_supplier' => 'nullable|string|max:255',
        ]);

        $item->update($validated);

        return redirect()->route('orders.show', $item->order_id)->with('success', 'QA đã cập nhật chỉ định lô/nhà cung cấp thành công!');
    }

    /**
     * Xóa một dòng vị thuốc khỏi đơn hàng.
     */
    public function destroy(string $itemId)
    {
        $item = OrderItem::findOrFail($itemId);
        $orderId = $item->order_id;
        $order = $item->order;
        abort_unless($this->canManageSalesOrder(), 403);
        if ($order && $order->isApproved()) {
            return redirect()->route('orders.show', $orderId)->with('error', 'Đơn hàng đã được duyệt, không thể xóa sản phẩm.');
        }
        if ($item->labelPrints()->exists()) {
            return redirect()->route('orders.show', $orderId)->with('error', 'Dòng đơn đã có lịch sử in nhãn, không thể xóa để bảo toàn đối soát tem.');
        }
        if ($order && $this->orderIsLocked($order) && ! $this->canManageLockedOrder()) {
            return redirect()->route('orders.show', $orderId)->with('error', 'Đơn đã gửi sản xuất, chỉ IT mới được xóa sản phẩm.');
        }

        $item->delete();

        return redirect()->route('orders.show', $orderId)->with('success', 'Đã xóa vị thuốc khỏi đơn hàng!');
    }
}
