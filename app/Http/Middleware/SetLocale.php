<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Nếu người dùng bấm chọn đổi ngôn ngữ trên giao diện (?lang=vi hoặc ?lang=en)
        if ($request->has('lang')) {
            $lang = $request->get('lang');
            if (in_array($lang, ['en', 'vi'])) {
                Session::put('locale', $lang);

                // Nếu đã đăng nhập, cập nhật luôn vào Database của user đó
                if (Auth::check()) {
                    $user = Auth::user();
                    $user->locale = $lang;
                    $user->save();
                }
            }
        }

        // 2. Xác định ngôn ngữ ưu tiên theo thứ tự: Request/Session -> Database User -> Mặc định ('vi')
        $locale = Session::get('locale');

        if (!$locale && Auth::check()) {
            $locale = Auth::user()->locale;
            Session::put('locale', $locale);
        }

        if (!$locale) {
            $locale = config('app.locale', 'vi');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
