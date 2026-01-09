<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($request->user()->role !== 'administrador') {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Solo los administradores pueden acceder a este recurso'
            ], 403);
        }

        return $next($request);
    }
}
