<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $username = config('admin.basic_auth.username');
        $password = config('admin.basic_auth.password');

        if ($request->getUser() != $username || $request->getPassword() != $password) {
            return response('Unauthorized', 401)
                ->header('WWW-Authenticate', 'Basic');
        }

        return $next($request);
    }
}
