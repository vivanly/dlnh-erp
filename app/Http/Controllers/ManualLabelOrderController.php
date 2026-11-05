<?php

namespace App\Http\Controllers;

use App\Models\ManualLabelCatalog;
use App\Models\ManualLabelOrderLine;
use App\Models\ManualLabelPrint;
use App\Models\ManualLabelSource;
use Illuminate\Http\Request;

class ManualLabelOrderController extends Controller
{
    private function authorizePrinting(): void
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
    }

    public function index(Request $request)
    {
        $this->authorizePrinting();
        $code = strtoupper(trim((string) $request->query('code')));

        $lines = collect();
        $suggestions = collect();
        if ($code !== '') {
            $lines = ManualLabelOrderLine::where('order_code', $code)->orderBy('line_no')->orderBy('id')->get();
            if ($lines->isEmpty()) {
                $like = '%' . addcslashes($code, '%_\\') . '%';
                $suggestions = ManualLabelOrderLine::where('order_code', 'like', $like)
                    ->select('order_code', 'customer')->distinct()->orderBy('order_code')->limit(15)->get();
            }
        }

        $catalog = ManualLabelCatalog::whereIn('name_key', $lines->map(fn ($l) => ManualLabelCatalog::key($l->name))->unique())
            ->get()->keyBy('name_key');

        return view('manual-labels.orders', [
            'code' => $code,
            'lines' => $lines,
            'order' => $lines->first(),
            'catalog' => $catalog,
            'suggestions' => $suggestions,
            'hasData' => ManualLabelOrderLine::exists(),
        ]);
    }
}
