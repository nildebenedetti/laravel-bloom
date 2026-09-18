<?php

namespace App\Http\Middleware;

use App\Http\Middleware\IsAdmin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(auth()->check() && auth()->user()->isAdmin()) { // calling method defined on User model, which returns boolean value
            return $next($request);
        }

        abort(403, 'Access denied. Only Admins can access this area.');

    }
}
