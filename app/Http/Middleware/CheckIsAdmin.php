<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Infrastructure\Enumerations\RoleEnums;
use Illuminate\Support\Facades\Auth;

class CheckIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (
            Auth::check()
            && $this->checkCurrentUserRole()
        ) {
            return $next($request);
        }

        abort(403, 'دسترسی شما به این بخش امکان پذیر نمی باشد.');
    }

    private function checkCurrentUserRole()
    {
        return Auth::user()->roles()
            ->whereIn('role_id', [
                RoleEnums::ADMIN_ID,
                RoleEnums::MARKETING_ID
            ])
            ->exists();
    }
}
