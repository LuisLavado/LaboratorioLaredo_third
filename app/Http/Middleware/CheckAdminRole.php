<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'status' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        if (auth()->user()->role !== 'administrador') {
            return response()->json([
                'status' => false,
                'message' => 'Acceso denegado. Solo administradores pueden acceder.'
            ], 403);
        }

        return $next($request);
    }
}
