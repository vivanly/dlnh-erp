<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DirectorReadOnly
{
    private const ALLOWED_MUTATION_ROUTES = [
        'logout',
        'purchase-orders.approve',
        'purchase-orders.reject',
        'orders.approve-sales',
        'orders.reject-sales',
        'production-planning.approve',
        'production-planning.reject',
        'production-monthly-plans.approve',
        'production-monthly-plans.reject',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isExecutiveBoardMember()) {
            return $next($request);
        }

        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)
            || in_array($request->route()?->getName(), self::ALLOWED_MUTATION_ROUTES, true)) {
            return $next($request);
        }

        abort(403, 'Ban Giám đốc chỉ được xem; thao tác chỉ dành cho các mục duyệt trong menu Ban Giám đốc.');
    }
}
