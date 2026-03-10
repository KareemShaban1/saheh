<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class DocsPasswordMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $password = config('documentation.password');

        // If no password configured, allow access
        if (empty($password)) {
            return $next($request);
        }

        // Check if already authenticated for docs
        if ($request->session()->get('docs_authenticated') === true) {
            return $next($request);
        }

        // Allow access to login page (GET and POST)
        if ($request->routeIs('docs.login')) {
            return $next($request);
        }

        return redirect()->route('docs.login');
    }
}
