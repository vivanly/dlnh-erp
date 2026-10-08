<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function summary(NotificationService $notifications): JsonResponse
    {
        $items = $notifications->forUser(auth()->user());

        return response()->json([
            'total' => array_sum(array_column($items, 'count')),
            'items' => $items,
        ]);
    }
}
