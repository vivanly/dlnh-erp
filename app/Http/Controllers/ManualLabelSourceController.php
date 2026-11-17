<?php

namespace App\Http\Controllers;

use App\Models\ManualLabelProductionOrder;
use Illuminate\Http\Request;

class ManualLabelSourceController extends Controller
{
    private function authorizePrinting(): void
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
    }

    public function search(Request $request)
    {
        $this->authorizePrinting();
        $q = trim((string) $request->query('q'));
        if ($q === '') {
            return response()->json([]);
        }

        return response()->json(
            ManualLabelOrderController::searchQuery($q)->orderByDesc('order_date')->orderByDesc('id')->limit(20)->get()
                ->map(fn ($o) => $o->toFormArray())->values()
        );
    }
}
