<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Solicitud;
use App\Models\Examen;
use App\Models\ResultadoExamen;
use App\Models\Paciente;
use App\Models\CentroSalud;

class AdminController extends Controller
{
    /**
     * Dashboard de administración
     */
    public function dashboard(Request $request): JsonResponse
    {
        // Fechas para estadísticas
        $hoy = now()->startOfDay();
        $semanaAnterior = now()->subDays(7);
        $mesAnterior = now()->subDays(30);

        // Estadísticas de usuarios
        $totalUsuarios = User::count();
        $usuariosActivos = User::where('activo', true)->count();
        $usuariosNuevosHoy = User::whereDate('created_at', $hoy)->count();
        $usuariosNuevosSemana = User::where('created_at', '>=', $semanaAnterior)->count();

        // Estadísticas por rol
        $doctores = User::where('role', 'doctor')->where('activo', true)->count();
        $tecnicos = User::where('role', 'laboratorio')->where('activo', true)->count();
        $administradores = User::where('role', 'administrador')->where('activo', true)->count();

        // Actividad reciente (accesos)
        $accesosHoy = User::whereDate('ultimo_acceso', $hoy)->count();
        $accesosSemana = User::where('ultimo_acceso', '>=', $semanaAnterior)->count();

        // Estadísticas de solicitudes
        $totalSolicitudes = Solicitud::count();
        $solicitudesHoy = Solicitud::whereDate('created_at', $hoy)->count();
        $solicitudesSemana = Solicitud::where('created_at', '>=', $semanaAnterior)->count();
        $solicitudesPendientes = Solicitud::where('estado', 'pendiente')->count();

        // Estadísticas de exámenes
        $totalExamenes = Examen::where('activo', true)->count();
        $examenesHoy = ResultadoExamen::whereDate('created_at', $hoy)->count();
        $examenesPendientes = Solicitud::where('estado', 'en_proceso')->count();

        // Estadísticas de pacientes
        $totalPacientes = Paciente::count();
        $pacientesNuevosHoy = Paciente::whereDate('created_at', $hoy)->count();

        // Usuarios conectados desde WebSocket
        $usuariosConectados = 0;
        try {
            $response = Http::timeout(5)->get('http://3.14.3.69:3002/api/admin/connected-users');
            if ($response->successful()) {
                $wsData = $response->json();
                $usuariosConectados = $wsData['data']['total'] ?? 0;
            }
        } catch (\Exception $e) {
            // Ignorar errores de WebSocket
        }

        $data = [
            'stats' => [
                'usuarios_totales' => $totalUsuarios,
                'usuarios_activos' => $usuariosActivos,
                'usuarios_conectados' => $usuariosConectados,
                'usuarios_en_linea' => $usuariosConectados,
                'doctores' => $doctores,
                'tecnicos' => $tecnicos,
                'administradores' => $administradores,
                'usuarios_nuevos_hoy' => $usuariosNuevosHoy,
                'usuarios_nuevos_semana' => $usuariosNuevosSemana,
                'accesos_hoy' => $accesosHoy,
                'accesos_semana' => $accesosSemana,
                'solicitudes_totales' => $totalSolicitudes,
                'solicitudes_hoy' => $solicitudesHoy,
                'solicitudes_semana' => $solicitudesSemana,
                'solicitudes_pendientes' => $solicitudesPendientes,
                'examenes_activos' => $totalExamenes,
                'examenes_hoy' => $examenesHoy,
                'examenes_pendientes' => $examenesPendientes,
                'pacientes_totales' => $totalPacientes,
                'pacientes_nuevos_hoy' => $pacientesNuevosHoy
            ],
            'actividad_reciente' => $this->getActividadReciente(),
            'usuarios_por_dia' => $this->getUsuariosPorDia(),
            'accesos_por_dia' => $this->getAccesosPorDia(),
            'solicitudes_por_dia' => $this->getSolicitudesPorDia()
        ];

        return response()->json($data);
    }

    /**
     * Obtener actividad reciente del sistema
     */
    private function getActividadReciente()
    {
        // Últimos usuarios registrados
        $usuariosRecientes = User::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'nombre', 'apellido', 'role', 'created_at'])
            ->map(function ($user) {
                return [
                    'tipo' => 'registro',
                    'descripcion' => "Nuevo usuario registrado: {$user->nombre} {$user->apellido} ({$user->role})",
                    'fecha' => $user->created_at,
                    'usuario' => $user
                ];
            });

        // Últimos accesos
        $accesosRecientes = User::whereNotNull('ultimo_acceso')
            ->orderBy('ultimo_acceso', 'desc')
            ->limit(5)
            ->get(['id', 'nombre', 'apellido', 'role', 'ultimo_acceso'])
            ->map(function ($user) {
                return [
                    'tipo' => 'acceso',
                    'descripcion' => "Acceso al sistema: {$user->nombre} {$user->apellido} ({$user->role})",
                    'fecha' => $user->ultimo_acceso,
                    'usuario' => $user
                ];
            });

        // Últimas solicitudes
        $solicitudesRecientes = Solicitud::with(['user', 'paciente'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($solicitud) {
                $usuario = $solicitud->user;
                $paciente = $solicitud->paciente;
                return [
                    'tipo' => 'solicitud',
                    'descripcion' => "Nueva solicitud por {$usuario->nombre} {$usuario->apellido} para {$paciente->nombres} {$paciente->apellidos}",
                    'fecha' => $solicitud->created_at,
                    'usuario' => $usuario,
                    'solicitud' => $solicitud
                ];
            });

        // Combinar y ordenar actividades
        $actividades = $usuariosRecientes->concat($accesosRecientes)
            ->concat($solicitudesRecientes)
            ->sortByDesc('fecha')
            ->take(15)
            ->values();

        return $actividades;
    }

    /**
     * Obtener estadísticas de usuarios por día (últimos 7 días)
     */
    private function getUsuariosPorDia()
    {
        $datos = [];
        $diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        
        for ($i = 6; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->startOfDay();
            $count = User::whereDate('created_at', $fecha)->count();
            $datos[] = [
                'fecha' => $fecha->format('Y-m-d'),
                'dia' => $diasSemana[$fecha->dayOfWeek],
                'usuarios' => $count
            ];
        }
        return $datos;
    }

    /**
     * Obtener estadísticas de accesos por día (últimos 7 días)
     */
    private function getAccesosPorDia()
    {
        $datos = [];
        $diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        
        for ($i = 6; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->startOfDay();
            $count = User::whereDate('ultimo_acceso', $fecha)->count();
            $datos[] = [
                'fecha' => $fecha->format('Y-m-d'),
                'dia' => $diasSemana[$fecha->dayOfWeek],
                'accesos' => $count
            ];
        }
        return $datos;
    }

    /**
     * Obtener estadísticas de solicitudes por día (últimos 7 días)
     */
    private function getSolicitudesPorDia()
    {
        $datos = [];
        $diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        
        for ($i = 6; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->startOfDay();
            $count = Solicitud::whereDate('created_at', $fecha)->count();
            $datos[] = [
                'fecha' => $fecha->format('Y-m-d'),
                'dia' => $diasSemana[$fecha->dayOfWeek],
                'solicitudes' => $count
            ];
        }
        return $datos;
    }

    /**
     * Obtener usuarios en línea
     */    public function usuariosEnLinea(Request $request): JsonResponse
    {
        try {
            // Usa la URL desde las variables de entorno, con fallback a la URL antigua
            $webhookUrl = env('WEBHOOK_URL', 'https://webhook.shiroharu.online');
            $url = $webhookUrl . '/api/connected-users';
            
            \Log::info('Consultando usuarios en línea en: ' . $url);
            $response = Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                \Log::info('Respuesta exitosa de usuarios en línea: ' . json_encode($response->json()));
                return response()->json([
                    'data' => [
                        'usuarios' => $response->json('users') ?? []
                    ],
                    'status' => 'success'
                ]);
            }
            
            \Log::warning('No se pudo obtener usuarios en línea. Status: ' . $response->status());
            return response()->json([
                'data' => [
                    'usuarios' => []
                ],
                'message' => 'No se pudo conectar al servidor WebSocket',
                'status' => 'error'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error consultando usuarios en línea: ' . $e->getMessage());
            return response()->json([
                'data' => [
                    'usuarios' => []
                ],
                'error' => 'Error consultando usuarios en línea: ' . $e->getMessage(),
                'status' => 'error'
            ]);
        }
    }

    /**
     * Listar todos los usuarios with información de centros de salud
     */
    public function usuarios(Request $request): JsonResponse
    {
        $query = User::with('centroSalud');

        // Filtros
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('activo') && $request->activo !== '') {
            $query->where('activo', $request->activo == '1');
        }

        if ($request->has('centro_salud_id') && $request->centro_salud_id) {
            $query->where('centro_salud_id', $request->centro_salud_id);
        }

        $usuarios = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'data' => [
                'usuarios' => $usuarios
            ]
        ]);
    }

    /**
     * Obtener centros de salud
     */
    public function centrosSalud(Request $request): JsonResponse
    {
        $centros = CentroSalud::activos()->orderBy('nombre')->get();
        
        return response()->json([
            'data' => [
                'centros' => $centros
            ]
        ]);
    }

    /**
     * Crear un nuevo usuario
     */
    public function crearUsuario(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string|in:administrador,laboratorio,doctor',
            'password' => 'required|string|min:8',
            'centro_salud_id' => 'nullable|exists:centros_salud,id',
            'especialidad' => 'nullable|string|max:255',
            'colegiatura' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'role' => $request->role,
            'password' => bcrypt($request->password),
            'centro_salud_id' => $request->centro_salud_id,
            'especialidad' => $request->especialidad,
            'colegiatura' => $request->colegiatura,
            'activo' => true,
        ]);

        $user->load('centroSalud');

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => $user
        ], 201);
    }

    /**
     * Actualizar un usuario existente
     */
    public function actualizarUsuario(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        $rules = [
            'nombre' => 'sometimes|string|max:255',
            'apellido' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$id,
            'role' => 'sometimes|string|in:administrador,laboratorio,doctor',
            'centro_salud_id' => 'nullable|exists:centros_salud,id',
            'especialidad' => 'nullable|string|max:255',
            'colegiatura' => 'nullable|string|max:255',
        ];

        if ($request->has('password') && $request->password) {
            $rules['password'] = 'string|min:8';
        }

        $request->validate($rules);

        // Actualizar campos
        $fillableFields = ['nombre', 'apellido', 'email', 'role', 'centro_salud_id', 'especialidad', 'colegiatura'];
        
        foreach ($fillableFields as $field) {
            if ($request->has($field)) {
                $user->$field = $request->$field;
            }
        }

        if ($request->has('password') && $request->password) {
            $user->password = bcrypt($request->password);
        }
        
        $user->save();
        $user->load('centroSalud');

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'user' => $user
        ]);
    }    /**
     * Desactivar un usuario
     */
    public function desactivarUsuario(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->activo = false;
        $user->fecha_desactivacion = now();
        $user->motivo_desactivacion = $request->input('motivo', 'Desactivado por administrador');
        $user->save();

        // Cerrar automáticamente todas las sesiones activas del usuario
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Usuario desactivado exitosamente y sesiones cerradas',
            'user' => $user
        ]);
    }

    /**
     * Activar un usuario
     */
    public function activarUsuario(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->activo = true;
        $user->fecha_desactivacion = null;
        $user->motivo_desactivacion = null;
        $user->save();

        return response()->json([
            'message' => 'Usuario activado exitosamente',
            'user' => $user
        ]);
    }

    /**
     * Cerrar sesión de un usuario
     */
    public function cerrarSesionUsuario(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // Revocar todos los tokens del usuario
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
            'user' => $user
        ]);
    }

    /**
     * Obtener actividades detalladas por usuario y fecha con paginación
     */
    public function actividadesDetalladas(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $fechaInicio = $request->input('fecha_inicio', now()->subDays(30)->format('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', now()->format('Y-m-d'));
        $usuarioId = $request->input('usuario_id');
        $tipo = $request->input('tipo'); // 'registro', 'acceso', 'solicitud'

        $actividades = collect();

        // Actividades de registro
        if (!$tipo || $tipo === 'registro') {
            $query = User::with('centroSalud')
                ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            
            if ($usuarioId) {
                $query->where('id', $usuarioId);
            }

            $registros = $query->get()->map(function ($user) {
                return [
                    'id' => 'registro_' . $user->id,
                    'tipo' => 'registro',
                    'usuario_id' => $user->id,
                    'usuario_nombre' => $user->nombre . ' ' . $user->apellido,
                    'usuario_email' => $user->email,
                    'usuario_role' => $user->role,
                    'centro_salud' => $user->centroSalud ? $user->centroSalud->nombre : null,
                    'descripcion' => 'Usuario registrado en el sistema',
                    'fecha' => $user->created_at,
                    'detalles' => [
                        'email' => $user->email,
                        'rol' => $user->role,
                        'centro_salud_id' => $user->centro_salud_id
                    ]
                ];
            });

            $actividades = $actividades->concat($registros);
        }

        // Actividades de acceso
        if (!$tipo || $tipo === 'acceso') {
            $query = User::with('centroSalud')
                ->whereNotNull('ultimo_acceso')
                ->whereBetween('ultimo_acceso', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            
            if ($usuarioId) {
                $query->where('id', $usuarioId);
            }

            $accesos = $query->get()->map(function ($user) {
                return [
                    'id' => 'acceso_' . $user->id,
                    'tipo' => 'acceso',
                    'usuario_id' => $user->id,
                    'usuario_nombre' => $user->nombre . ' ' . $user->apellido,
                    'usuario_email' => $user->email,
                    'usuario_role' => $user->role,
                    'centro_salud' => $user->centroSalud ? $user->centroSalud->nombre : null,
                    'descripcion' => 'Acceso al sistema',
                    'fecha' => $user->ultimo_acceso,
                    'detalles' => [
                        'ip' => '127.0.0.1',
                        'dispositivo' => 'Web'
                    ]
                ];
            });

            $actividades = $actividades->concat($accesos);
        }

        // Actividades de solicitudes
        if (!$tipo || $tipo === 'solicitud') {
            $query = Solicitud::with(['user.centroSalud', 'paciente'])
                ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            
            if ($usuarioId) {
                $query->where('user_id', $usuarioId);
            }

            $solicitudes = $query->get()->map(function ($solicitud) {
                $usuario = $solicitud->user;
                $paciente = $solicitud->paciente;
                return [
                    'id' => 'solicitud_' . $solicitud->id,
                    'tipo' => 'solicitud',
                    'usuario_id' => $usuario->id,
                    'usuario_nombre' => $usuario->nombre . ' ' . $usuario->apellido,
                    'usuario_email' => $usuario->email,
                    'usuario_role' => $usuario->role,
                    'centro_salud' => $usuario->centroSalud ? $usuario->centroSalud->nombre : null,
                    'descripcion' => "Nueva solicitud para {$paciente->nombres} {$paciente->apellidos}",
                    'fecha' => $solicitud->created_at,
                    'detalles' => [
                        'solicitud_id' => $solicitud->id,
                        'paciente' => $paciente->nombres . ' ' . $paciente->apellidos,
                        'estado' => $solicitud->estado
                    ]
                ];
            });

            $actividades = $actividades->concat($solicitudes);
        }

        // Ordenar y paginar
        $actividades = $actividades->sortByDesc('fecha')->values();
        $total = $actividades->count();
        $offset = ($page - 1) * $perPage;
        $paginatedActividades = $actividades->slice($offset, $perPage)->values();

        return response()->json([
            'data' => $paginatedActividades,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
            ],
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'usuario_id' => $usuarioId,
                'tipo' => $tipo
            ]
        ]);
    }

    /**
     * Obtener actividades por fecha
     */
    public function actividadesPorFecha(Request $request): JsonResponse
    {
        $fechaInicio = $request->input('fecha_inicio', now()->subDays(7)->format('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', now()->format('Y-m-d'));
        $usuarioId = $request->input('usuario_id');

        $query = User::query();

        if ($usuarioId) {
            $query->where('id', $usuarioId);
        }

        // Actividades de registro
        $registros = $query->clone()
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->get(['id', 'nombre', 'apellido', 'email', 'role', 'created_at'])
            ->map(function ($user) {
                return [
                    'tipo' => 'registro',
                    'usuario_id' => $user->id,
                    'usuario_nombre' => $user->nombre . ' ' . $user->apellido,
                    'usuario_email' => $user->email,
                    'usuario_role' => $user->role,
                    'descripcion' => 'Usuario registrado en el sistema',
                    'fecha' => $user->created_at,
                    'detalles' => [
                        'email' => $user->email,
                        'rol' => $user->role
                    ]
                ];
            });

        // Actividades de acceso
        $accesos = $query->clone()
            ->whereNotNull('ultimo_acceso')
            ->whereBetween('ultimo_acceso', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->get(['id', 'nombre', 'apellido', 'email', 'role', 'ultimo_acceso'])
            ->map(function ($user) {
                return [
                    'tipo' => 'acceso',
                    'usuario_id' => $user->id,
                    'usuario_nombre' => $user->nombre . ' ' . $user->apellido,
                    'usuario_email' => $user->email,
                    'usuario_role' => $user->role,
                    'descripcion' => 'Acceso al sistema',
                    'fecha' => $user->ultimo_acceso,
                    'detalles' => [
                        'ip' => '127.0.0.1',
                        'dispositivo' => 'Web'
                    ]
                ];
            });

        // Combinar y ordenar todas las actividades
        $actividades = $registros->concat($accesos)
            ->sortByDesc('fecha')
            ->values();

        return response()->json([
            'actividades' => $actividades,
            'resumen' => [
                'total_actividades' => $actividades->count(),
                'registros' => $registros->count(),
                'accesos' => $accesos->count(),
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]
        ]);
    }

    /**
     * Obtener estadísticas de un usuario específico
     */
    public function estadisticasUsuario(Request $request, $id): JsonResponse
    {
        $user = User::with('centroSalud')->findOrFail($id);

        $totalSolicitudes = Solicitud::where('user_id', $id)->count();
        $solicitudesCompletadas = Solicitud::where('user_id', $id)->where('estado', 'completado')->count();
        $solicitudesPendientes = Solicitud::where('user_id', $id)->where('estado', 'pendiente')->count();
        $solicitudesHoy = Solicitud::where('user_id', $id)->whereDate('created_at', now())->count();

        $diasDesdeRegistro = $user->created_at->diffInDays(now());
        $diasDesdeUltimoAcceso = $user->ultimo_acceso ? $user->ultimo_acceso->diffInDays(now()) : null;

        $estadoConexion = 'offline';
        if ($user->ultimo_acceso && $user->ultimo_acceso->gt(now()->subMinutes(5))) {
            $estadoConexion = 'online';
        } elseif ($user->ultimo_acceso && $user->ultimo_acceso->gt(now()->subHour())) {
            $estadoConexion = 'away';
        }

        return response()->json([
            'usuario' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'email' => $user->email,
                'role' => $user->role,
                'centro_salud' => $user->centroSalud,
                'especialidad' => $user->especialidad,
                'colegiatura' => $user->colegiatura,
                'activo' => $user->activo,
                'fecha_registro' => $user->created_at,
                'ultimo_acceso' => $user->ultimo_acceso,
                'estado_conexion' => $estadoConexion
            ],
            'estadisticas' => [
                'dias_desde_registro' => $diasDesdeRegistro,
                'dias_desde_ultimo_acceso' => $diasDesdeUltimoAcceso,
                'total_solicitudes' => $totalSolicitudes,
                'solicitudes_completadas' => $solicitudesCompletadas,
                'solicitudes_pendientes' => $solicitudesPendientes,
                'solicitudes_hoy' => $solicitudesHoy
            ],
            'actividad_reciente' => [
                'accesos_ultima_semana' => $user->ultimo_acceso && $user->ultimo_acceso->gte(now()->subWeek()) ? 1 : 0,
                'accesos_ultimo_mes' => $user->ultimo_acceso && $user->ultimo_acceso->gte(now()->subMonth()) ? 1 : 0,
            ]
        ]);
    }

    /**
     * Obtener resumen de actividades por día en un rango de fechas
     */
    public function resumenPorDia(Request $request): JsonResponse
    {
        $fechaInicio = $request->input('fecha_inicio', now()->subDays(30)->format('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', now()->format('Y-m-d'));

        $datos = [];
        $fechaActual = \Carbon\Carbon::parse($fechaInicio);
        $fechaFinal = \Carbon\Carbon::parse($fechaFin);

        $diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

        while ($fechaActual->lte($fechaFinal)) {
            $fecha = $fechaActual->format('Y-m-d');
            
            $registros = User::whereDate('created_at', $fecha)->count();
            $accesos = User::whereDate('ultimo_acceso', $fecha)->count();
            $solicitudes = Solicitud::whereDate('created_at', $fecha)->count();

            $datos[] = [
                'fecha' => $fecha,
                'dia_semana' => $diasSemana[$fechaActual->dayOfWeek],
                'dia_mes' => $fechaActual->format('d'),
                'mes' => $fechaActual->format('M'),
                'registros' => $registros,
                'accesos' => $accesos,
                'solicitudes' => $solicitudes,
                'total_actividades' => $registros + $accesos + $solicitudes
            ];

            $fechaActual->addDay();
        }

        return response()->json([
            'datos' => $datos,
            'resumen' => [
                'total_dias' => count($datos),
                'total_registros' => array_sum(array_column($datos, 'registros')),
                'total_accesos' => array_sum(array_column($datos, 'accesos')),
                'total_solicitudes' => array_sum(array_column($datos, 'solicitudes')),
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]
        ]);
    }

    /**
     * Obtener un usuario específico por ID
     */
    public function obtenerUsuario($id): JsonResponse
    {
        $usuario = User::with('centroSalud')->find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        return response()->json($usuario);
    }

    /**
     * Obtener actividad reciente de un usuario
     */
    public function obtenerActividadUsuario($id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        // Por ahora devolvemos datos mock, pero aquí se podría implementar
        // un sistema de logs de actividad real
        $activities = [
            [
                'id' => 1,
                'action' => 'Login',
                'description' => 'Usuario inició sesión',
                'timestamp' => now()->subMinutes(5)->toISOString(),
                'ip' => '192.168.1.100'
            ],
            [
                'id' => 2,
                'action' => 'Vista Dashboard',
                'description' => 'Accedió al panel principal',
                'timestamp' => now()->subMinutes(10)->toISOString(),
                'ip' => '192.168.1.100'
            ],
            [
                'id' => 3,
                'action' => 'Ver Solicitudes',
                'description' => 'Consultó solicitudes de laboratorio',
                'timestamp' => now()->subMinutes(15)->toISOString(),
                'ip' => '192.168.1.100'
            ]
        ];

        return response()->json([
            'activities' => $activities
        ]);
    }

    /**
     * Crear un nuevo centro de salud
     */
    public function crearCentroSalud(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'required|string|max:20|unique:centros_salud,codigo',
            'direccion' => 'required|string|max:500',
            'distrito' => 'required|string|max:255',
            'provincia' => 'required|string|max:255',
            'departamento' => 'required|string|max:255',
            'tipo' => 'required|in:hospital,clinica,centro_salud,posta',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'activo' => 'boolean'
        ]);

        $centro = CentroSalud::create($request->all());

        return response()->json([
            'message' => 'Centro de salud creado exitosamente',
            'data' => $centro
        ], 201);
    }

    /**
     * Actualizar un centro de salud
     */
    public function actualizarCentroSalud(Request $request, $id): JsonResponse
    {
        $centro = CentroSalud::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'required|string|max:20|unique:centros_salud,codigo,' . $id,
            'direccion' => 'required|string|max:500',
            'distrito' => 'required|string|max:255',
            'provincia' => 'required|string|max:255',
            'departamento' => 'required|string|max:255',
            'tipo' => 'required|in:hospital,clinica,centro_salud,posta',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'activo' => 'boolean'
        ]);

        $centro->update($request->all());

        return response()->json([
            'message' => 'Centro de salud actualizado exitosamente',
            'data' => $centro
        ]);
    }

    /**
     * Eliminar un centro de salud
     */
    public function eliminarCentroSalud($id): JsonResponse
    {
        $centro = CentroSalud::findOrFail($id);
        
        // Verificar si el centro tiene usuarios asociados
        $usuariosAsociados = User::where('centro_salud_id', $id)->count();
        
        if ($usuariosAsociados > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el centro de salud porque tiene usuarios asociados'
            ], 400);
        }

        $centro->delete();

        return response()->json([
            'message' => 'Centro de salud eliminado exitosamente'
        ]);
    }
}
