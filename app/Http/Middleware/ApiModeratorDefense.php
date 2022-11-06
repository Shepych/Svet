<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ModeratorController;
use App\Models\BlackList;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ApiModeratorDefense
{
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
        if(isset($ip) && $ip->attempts >= ApiDefense::$limitAttempts) {
            return response(['status' => ['error' => 'IP Заблокирован']], 403);
        }

        # 3 API ключа для безопасности
        if($request->api_token_1 != ModeratorController::$api_token_1 || $request->api_token_2 != ModeratorController::$api_token_2) {
            BlackList::attempt($request->ip());
            return response(['status' => ['error' => 'Неверные токены']], 503);
        }

        return $next($request);
    }
}
