<?php

namespace App\Http\Middleware;

use App\Models\BlackList;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ApiDefense
{

    public static $limitAttempts = 60;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = BlackList::where('ip', $request->ip())->first();

        # Проверка на блокировку IP
        if(isset($ip) && $ip->attempts >= self::$limitAttempts) {
            return response(['error' => 'IP Заблокирован'], 403);
        }

        $user = User::where('api_token', $request->api_token);

        # Валидация пользователя
        if(!$user->exists()) {
            # Обновить Black List
            BlackList::attempt($request->ip());
            return response(['error' => 'Неверный токен'], 503);
        }

        return $next($request);
    }
}
