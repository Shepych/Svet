<?php

namespace App\Http\Middleware;

use App\Models\BlackList;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ModeratorCheck
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
        $user = User::where('api_token', $request->api_token)->first();
        # Проверка прав доступа
        if(!$user->hasRole('moderator')) {
            return response(['status' => ['error' => 'Недостаточно прав']], 503);
        }
        return $next($request);
    }
}
