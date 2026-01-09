<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:doctor,laboratorio'],
            'especialidad' => ['required_if:role,doctor', 'nullable', 'string', 'max:255'],
            'colegiatura' => ['required_if:role,doctor', 'nullable', 'string', 'max:255'],
        ]);

        // Crear el nombre completo para el campo 'name' (si se necesita en el futuro)
        $fullName = $request->nombre . ' ' . $request->apellido;

        $userData = [
            'nombre' => $request->nombre, // Guardar solo el nombre
            'apellido' => $request->apellido,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ];

        // Add doctor-specific fields if role is doctor
        if ($request->role === 'doctor') {
            $userData['especialidad'] = $request->especialidad;
            $userData['colegiatura'] = $request->colegiatura;
        }

        $user = User::create($userData);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Las credenciales proporcionadas son incorrectas.',
            ], 401);
        }

        // Verificar si el usuario está activo
        if (!$user->activo) {
            return response()->json([
                'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador.',
                'error' => 'ACCOUNT_DEACTIVATED'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Actualizar último acceso
        $user->ultimo_acceso = now();
        $user->save();

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.',
        ]);
    }

    public function show($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        return response()->json($user);
    }

    /**
     * Actualizar información del usuario
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        // Validar los datos según el rol del usuario
        if ($user->role === 'doctor') {
            $validatedData = $request->validate([
                'nombre' => 'sometimes|required|string|max:255',
                'apellido' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
                'especialidad' => 'sometimes|nullable|string|max:255',
                'colegiatura' => 'sometimes|nullable|string|max:255',
            ]);
        } else {
            $validatedData = $request->validate([
                'nombre' => 'sometimes|required|string|max:255',
                'apellido' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
            ]);
        }

        // Actualizar los datos del usuario
        $user->update($validatedData);

        return response()->json([
            'message' => 'Usuario actualizado con éxito',
            'user' => $user
        ]);
    }

    /**
     * Obtener usuarios por IDs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsersByIds(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|string',
            ]);

            // Convertir string de IDs separados por comas a array
            $userIds = explode(',', $request->ids);
            $userIds = array_filter(array_map('trim', $userIds)); // Limpiar espacios y elementos vacíos

            if (empty($userIds)) {
                return response()->json([
                    'message' => 'No se proporcionaron IDs válidos',
                    'users' => []
                ]);
            }

            // Obtener usuarios por IDs
            $users = User::whereIn('id', $userIds)
                ->select('id', 'nombre', 'apellido', 'email', 'role', 'especialidad', 'colegiatura')
                ->get();

            return response()->json([
                'users' => $users,
                'total' => $users->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage(),
                'users' => []
            ], 500);
        }
    }

    /**
     * Obtener actividad reciente de un usuario
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getUserActivity($id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no encontrado',
                    'activities' => []
                ], 404);
            }

            // Por ahora retornamos actividad mock
            // En el futuro se puede implementar una tabla de logs de actividad
            $activities = [
                [
                    'id' => 1,
                    'action' => 'Login',
                    'description' => 'Usuario inició sesión en el sistema',
                    'timestamp' => now()->subMinutes(5)->toISOString(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ],
                [
                    'id' => 2,
                    'action' => 'Consulta',
                    'description' => 'Consultó la lista de solicitudes',
                    'timestamp' => now()->subMinutes(10)->toISOString(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ],
                [
                    'id' => 3,
                    'action' => 'Navegación',
                    'description' => 'Navegó por el dashboard principal',
                    'timestamp' => now()->subMinutes(15)->toISOString(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            ];

            return response()->json([
                'user_id' => $id,
                'activities' => $activities,
                'total' => count($activities)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener actividad del usuario',
                'error' => $e->getMessage(),
                'activities' => []
            ], 500);
        }
    }
}