<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->bearerToken()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        if (!auth('sanctum')->check()) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        // if (!$request->expectsJson()) {
        //     // return redirect()->route('login');
        //     return response()->json([
        //         'message' => 'Unauthenticated. Please log in.'
        //     ], 401);
        // }
        return $next($request);
    }
}
