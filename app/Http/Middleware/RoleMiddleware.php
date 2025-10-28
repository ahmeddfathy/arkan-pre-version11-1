<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (in_array($user->role, $roles)) {
                return $next($request);
            }
        }

        return redirect()->route('login')->with('error', 'You do not have the required access');
    }
}
