<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
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
        // Solo aplicar esta validación a usuarios autenticados
        if (Auth::check()) {
            $user = Auth::user();
            
            // Si el usuario no está activo, cerrar sesión y devolver error
            if (!$user->activo) {
                // Revocar el token actual
                if ($user->currentAccessToken()) {
                    $user->currentAccessToken()->delete();
                }
                
                // Revocar todos los tokens del usuario
                $user->tokens()->delete();
                
                return response()->json([
                    'message' => 'Su cuenta ha sido desactivada. Por favor, contacte al administrador.',
                    'error' => 'ACCOUNT_DEACTIVATED'
                ], 401);
            }
        }

        return $next($request);
    }
}
