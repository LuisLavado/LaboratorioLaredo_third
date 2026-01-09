<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Solicitud;
use App\Models\Paciente;
use App\Models\Examen;
use App\Models\Servicio;
use App\Models\DetalleSolicitud;
use App\Services\ChartDataService;
use App\Exports\ModernReportExport;
use App\Exports\ReportExcelExport;
use App\Exports\General\GeneralReportExport;
use App\Exports\Patients\PatientsReportExport;
use App\Exports\Exams\ExamsReportExport;
use App\Exports\Services\ServicesReportExport;
use App\Exports\Services\Pdf\ServicesPdfExport;
use App\Exports\Results\ResultsDetailedExport;
use App\Exports\Results\ResultsReportExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    private $chartDataService;

    public function __construct(ChartDataService $chartDataService)
    {
        $this->chartDataService = $chartDataService;
    }
    /**
     * Obtener reportes según el tipo y rango de fechas
     */
    public function getReports(Request $request): JsonResponse
    {
        $type = $request->input('type', 'general');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        Log::info('getReports called with:', [
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'all_params' => $request->all()
        ]);

        // Validar fechas
        if (!$startDate || !$endDate) {
            return response()->json([
                'status' => false,
                'message' => 'Las fechas de inicio y fin son requeridas'
            ], 400);
        }

        try {
            // Convertir fechas a objetos Carbon
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();

            Log::info('Fechas procesadas:', [
                'startDate' => $startDate->format('Y-m-d H:i:s'),
                'endDate' => $endDate->format('Y-m-d H:i:s')
            ]);

            // Obtener datos según el tipo de reporte
            $data = [];

            switch ($type) {
                case 'general':
                    $data = $this->getGeneralReport($startDate, $endDate);
                    break;
                case 'exams':
                    // Verificar si hay exámenes seleccionados para filtrar
                    $examIds = $request->input('exam_ids');

                    // Si no se recibió exam_ids, intentar con exam_ids_csv (respaldo)
                    if (empty($examIds) && $request->has('exam_ids_csv')) {
                        $examIds = explode(',', $request->input('exam_ids_csv'));
                        \Log::info('Usando exam_ids_csv como respaldo en getReports (exams_detailed): ' . $request->input('exam_ids_csv'));
                    }

                    $filterByExams = $request->input('filter_by_exams', false);

                    $data = $this->getExamsDetailedReport($startDate, $endDate, $examIds, $filterByExams);

                    // Añadir flag para indicar que los datos ya están filtrados
                    if ($filterByExams && $examIds) {
                        $data['selected_exams_only'] = true;
                    }
                    break;
                case 'services':
                    // Verificar si hay servicios seleccionados para filtrar
                    $serviceIds = $request->input('service_ids');
                    $filterByServices = $request->input('filter_by_services', false);

                    $data = $this->getServicesReport($startDate, $endDate, $serviceIds, $filterByServices);

                    // Añadir flag para indicar que los datos ya están filtrados
                    if ($filterByServices && $serviceIds) {
                        $data['selected_services_only'] = true;
                    }
                    break;
                case 'doctors':
                    // Verificar si hay doctores seleccionados para filtrar
                    $doctorsIds = $request->input('doctor_ids');
                    $filterByDoctors = $request->input('filter_by_doctors', false);

                    // Usar getDoctorsReportData en lugar de getDoctorsReport
                    $data = $this->getDoctorsReportData($startDate, $endDate, $doctorsIds, $filterByDoctors);

                    // Añadir flag para indicar que los datos ya están filtrados
                    if ($filterByDoctors && $doctorsIds) {
                        $data['selected_doctors_only'] = true;
                    }

                    break;
                case 'patients':
                    \Log::info('🔍 Procesando reporte de pacientes en generateReport', [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'user_id' => $request->user()->id ?? 'no_user'
                    ]);
                    
                    $data = $this->getPatientsReport($startDate, $endDate);
                    
                    \Log::info('🎯 Datos obtenidos del reporte de pacientes', [
                        'has_data' => !empty($data),
                        'data_keys' => array_keys($data),
                        'totalPatients' => $data['totalPatients'] ?? 'not_set',
                        'totalRequests' => $data['totalRequests'] ?? 'not_set',
                        'totalExams' => $data['totalExams'] ?? 'not_set'
                    ]);
                    break;
                case 'results':
                    $data = $this->getResultsReport($startDate, $endDate, $request->input('status'));
                    break;
                case 'categories':
                    $data = $this->getCategoriesReport($startDate, $endDate);
                    break;
                case 'doctor_personal':
                    // Reporte específico para un doctor
                    $doctorId = $request->input('doctor_id');
                    if (!$doctorId) {
                        $doctorId = $request->user()->id;
                    }
                    $data = $this->getDoctorPersonalReport($startDate, $endDate, $doctorId);
                    break;
                default:
                    return response()->json([
                        'status' => false,
                        'message' => 'Tipo de reporte no válido'
                    ], 400);
            }

            // Aplicar limpieza final de examStats para asegurar agrupación correcta
            if (isset($data['examStats']) && ($type === 'exams' || $type === 'general')) {
                $data['examStats'] = $this->cleanAndGroupExamStats($data['examStats']);
            }

            Log::info('Datos finales para el reporte:', [
                'type' => $type,
                'data_keys' => is_array($data) ? array_keys($data) : (is_object($data) && method_exists($data, 'keys') ? $data->keys()->toArray() : 'Collection or Object'),
                'data_counts' => [
                    'totalRequests' => $data['totalRequests'] ?? 'N/A',
                    'totalPatients' => $data['totalPatients'] ?? 'N/A',
                    'totalExams' => $data['totalExams'] ?? 'N/A',
                    'pendingCount' => $data['pendingCount'] ?? 'N/A',
                    'inProcessCount' => $data['inProcessCount'] ?? 'N/A',
                    'completedCount' => $data['completedCount'] ?? 'N/A',
                    'examStats_count' => isset($data['examStats']) ? count($data['examStats']) : 'N/A',
                    'serviceStats_count' => isset($data['serviceStats']) ? count($data['serviceStats']) : 'N/A',
                    'dailyStats_count' => isset($data['dailyStats']) ? count($data['dailyStats']) : 'N/A'
                ],
                'examStats_sample' => isset($data['examStats']) ? (is_array($data['examStats']) ? array_slice($data['examStats'], 0, 3) : (method_exists($data['examStats'], 'take') ? $data['examStats']->take(3) : 'N/A')) : 'N/A',
                'serviceStats_sample' => isset($data['serviceStats']) ? (is_array($data['serviceStats']) ? array_slice($data['serviceStats'], 0, 3) : (method_exists($data['serviceStats'], 'take') ? $data['serviceStats']->take(3) : 'N/A')) : 'N/A'
            ]);

            return response()->json([
                'status' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getReports: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar reporte general
     */
    private function getGeneralReport($startDate, $endDate)
    {
        \Log::info('getGeneralReport called', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d')
        ]);

        // Verificar si hay solicitudes en el rango de fechas
        $totalSolicitudesEnRango = Solicitud::whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->count();
        \Log::info('Total solicitudes en rango:', ['count' => $totalSolicitudesEnRango]);

        // Si no hay solicitudes en el rango, verificar todas las solicitudes
        if ($totalSolicitudesEnRango === 0) {
            $todasLasSolicitudes = Solicitud::count();
            $primeraFecha = Solicitud::orderBy('fecha')->first()?->fecha;
            $ultimaFecha = Solicitud::orderBy('fecha', 'desc')->first()?->fecha;

            \Log::info('No hay solicitudes en el rango especificado', [
                'total_solicitudes_sistema' => $todasLasSolicitudes,
                'primera_fecha' => $primeraFecha,
                'ultima_fecha' => $ultimaFecha
            ]);
        }

        // Contar solicitudes por estado (el estado está en la tabla detallesolicitud, no en solicitudes)
        // CORREGIDO: Usar campo 'fecha' en lugar de 'created_at'
        $statusCounts = DetalleSolicitud::whereHas('solicitud', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->select('estado', DB::raw('count(*) as count'))
            ->groupBy('estado')
            ->get()
            ->pluck('count', 'estado')
            ->toArray();

        \Log::info('Status counts:', $statusCounts);

        // Si no hay datos, inicializar con valores por defecto
        if (empty($statusCounts)) {
            $statusCounts = [
                'pendiente' => 0,
                'en_proceso' => 0,
                'completado' => 0
            ];
        }

        // Contar solicitudes por día
        try {
            // Método 1: Usar una subconsulta para evitar el error de GROUP BY
            $dailyStats = DB::table(function($query) use ($startDate, $endDate) {
                $query->from('solicitudes')
                    ->select('fecha as date')
                    ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->groupBy('fecha');
            })->select('date', DB::raw('count(*) as count'))
              ->groupBy('date')
              ->orderBy('date')
              ->get();
        } catch (\Exception $e) {
            // Método 2: Alternativa si el método 1 falla
            try {
                $dailyStats = DB::select(
                    "SELECT fecha as date, COUNT(*) as count
                     FROM solicitudes
                     WHERE fecha BETWEEN ? AND ?
                     GROUP BY fecha
                     ORDER BY date ASC",
                    [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
                );
            } catch (\Exception $e2) {
                // Método 3: Última alternativa
                $dailyStats = collect();
                $currentDate = $startDate->copy();

                while ($currentDate->lte($endDate)) {
                    $dateStr = $currentDate->format('Y-m-d');

                    $count = Solicitud::where('fecha', $dateStr)->count();

                    $dailyStats->push((object)[
                        'date' => $currentDate->format('Y-m-d'),
                        'count' => $count
                    ]);

                    $currentDate->addDay();
                }
            }
        }

        // Agregar conteo de pacientes y exámenes por día
        foreach ($dailyStats as $stat) {
            $date = $stat->date;

            // Contar pacientes únicos por día
            $stat->patientCount = Solicitud::where('fecha', $date)
                ->distinct('paciente_id')
                ->count('paciente_id');

            // CORREGIDO: examCount debe ser igual al count de solicitudes porque cada solicitud es "un examen"
            $stat->examCount = $stat->count;
        }

        // Totales
        $totalRequests = Solicitud::whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->count();
        $totalPatients = Solicitud::whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->distinct('paciente_id')
            ->count('paciente_id');
        // CORREGIDO: totalExams debe ser igual a totalRequests porque cada solicitud es "un examen"
        // aunque internamente tenga múltiples sub-exámenes
        $totalExams = $totalRequests;

        // Agregar estadísticas de exámenes más solicitados para gráficos
        $topExams = DB::table('detallesolicitud')
            ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
            ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
            ->join('categorias', 'examenes.categoria_id', '=', 'categorias.id')
            ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                'examenes.id',
                'examenes.nombre as name',
                'categorias.nombre as categoria',
                

                DB::raw('count(*) as count')
            )
            ->groupBy('examenes.id', 'examenes.nombre', 'categorias.nombre')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Agregar estadísticas de servicios más utilizados para gráficos
        $topServices = DB::table('solicitudes')
            ->join('servicios', 'solicitudes.servicio_id', '=', 'servicios.id')
            ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                'servicios.id',
                'servicios.nombre as name',
                DB::raw('count(*) as count')
            )
            ->groupBy('servicios.id', 'servicios.nombre')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Obtener solicitudes para mostrar en el detalle (limitado a las primeras 50 para evitar PDFs muy grandes)
        $solicitudes = Solicitud::with(['paciente', 'user', 'servicio', 'detalles.examen'])
            ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('fecha', 'desc')
            ->limit(50)
            ->get();

        // Obtener información de pacientes únicos para el análisis de género
        $pacientesUnicos = Solicitud::with('paciente')
            ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->distinct('paciente_id')
            ->get()
            ->pluck('paciente')
            ->filter(); // Eliminar valores null

        return [
            'pendingCount' => $statusCounts['pendiente'] ?? 0,
            'inProcessCount' => $statusCounts['en_proceso'] ?? 0,
            'completedCount' => $statusCounts['completado'] ?? 0,
            'dailyStats' => $dailyStats,
            'totalRequests' => $totalRequests,
            'totalPatients' => $totalPatients,
            'totalExams' => $totalExams,
            'examStats' => $topExams,
            'serviceStats' => $topServices,
            'solicitudes' => $solicitudes,
            'patients' => $pacientesUnicos // Agregamos la lista de pacientes únicos
        ];
    }

    /**
     * Generar reporte por exámenes
     *
     * @param Carbon $startDate Fecha de inicio
     * @param Carbon $endDate Fecha de fin
     * @param array|null $examIds IDs de exámenes para filtrar (opcional)
     * @param bool $filterByExams Indica si se debe filtrar por exámenes
     * @return array
     */
    private function getExamsReport($startDate, $endDate, $examIds = null, $filterByExams = false)
    {
        // Contar exámenes por tipo
        try {
            $query = DB::table('detallesolicitud')
                ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                ->leftJoin('categorias', 'examenes.categoria_id', '=', 'categorias.id')
                ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            // Filtrar por exámenes específicos si se proporciona
            if ($filterByExams && $examIds) {
                // Asegurarse de que $examIds sea un array
                if (!is_array($examIds)) {
                    // Si es un string, intentar convertirlo a array
                    if (strpos($examIds, ',') !== false) {
                        $examIds = explode(',', $examIds);
                    } else {
                        $examIds = [$examIds];
                    }
                }

                // Limpiar y convertir los IDs a enteros
                $cleanIds = [];
                foreach ($examIds as $id) {
                    $cleanIds[] = (int)$id;
                }

                // Registrar los IDs para depuración
                \Log::info('Filtrando por exámenes IDs: ' . json_encode($cleanIds));

                $query->whereIn('examenes.id', $cleanIds);
            }

            $examStatsRaw = $query->select(
                    'examenes.id',
                    'examenes.codigo',
                    'examenes.nombre as name',
                    'categorias.nombre as categoria',
                    DB::raw('count(DISTINCT solicitudes.id) as count')
                )
                ->groupBy('examenes.id', 'examenes.codigo', 'examenes.nombre', 'categorias.nombre')
                ->orderByDesc('count')
                ->get();

            // Agrupar por nombre de examen para evitar duplicados
            $groupedExamStats = [];
            foreach ($examStatsRaw as $stat) {
                $key = $stat->name; // Usar el nombre como clave única

                if (isset($groupedExamStats[$key])) {
                    // Si ya existe, sumar el count
                    $groupedExamStats[$key]->count += $stat->count;
                } else {
                    // Si no existe, crear nuevo registro
                    $groupedExamStats[$key] = (object)[
                        'id' => $stat->id,
                        'codigo' => $stat->codigo,
                        'name' => $stat->name,
                        'categoria' => $stat->categoria,
                        'count' => $stat->count
                    ];
                }
            }

            // Convertir de vuelta a collection y ordenar por count
            $examStats = collect(array_values($groupedExamStats))->sortByDesc('count');
        } catch (\Exception $e) {
            // Alternativa usando consulta SQL directa
            if ($filterByExams && $examIds) {
                // Asegurarse de que $examIds sea un array
                if (!is_array($examIds)) {
                    // Si es un string, intentar convertirlo a array
                    if (strpos($examIds, ',') !== false) {
                        $examIds = explode(',', $examIds);
                    } else {
                        $examIds = [$examIds];
                    }
                }

                // Limpiar y convertir los IDs a enteros
                $cleanIds = [];
                foreach ($examIds as $id) {
                    $cleanIds[] = (int)$id;
                }

                // Registrar los IDs para depuración
                \Log::info('Filtrando por exámenes IDs (SQL): ' . json_encode($cleanIds));

                // Convertir array a string para la consulta SQL
                $examIdsStr = implode(',', $cleanIds);

                // Verificar que tenemos IDs válidos
                if (!empty($examIdsStr)) {
                    $examStats = DB::select(
                        "SELECT e.id, e.codigo, e.nombre as name, c.nombre as categoria, COUNT(DISTINCT s.id) as count
                         FROM detallesolicitud d
                         JOIN solicitudes s ON d.solicitud_id = s.id
                         JOIN examenes e ON d.examen_id = e.id
                         LEFT JOIN categorias c ON e.categoria_id = c.id
                         WHERE s.fecha BETWEEN ? AND ?
                         AND e.id IN ($examIdsStr)
                         GROUP BY e.id, e.codigo, e.nombre, c.nombre
                         ORDER BY count DESC",
                        [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
                    );
                } else {
                    // Si no hay IDs válidos, devolver un conjunto vacío
                    $examStats = [];
                }
            } else {
                $examStats = DB::select(
                    "SELECT e.id, e.codigo, e.nombre as name, c.nombre as categoria, COUNT(DISTINCT s.id) as count
                     FROM detallesolicitud d
                     JOIN solicitudes s ON d.solicitud_id = s.id
                     JOIN examenes e ON d.examen_id = e.id
                     LEFT JOIN categorias c ON e.categoria_id = c.id
                     WHERE s.fecha BETWEEN ? AND ?
                     GROUP BY e.id, e.codigo, e.nombre, c.nombre
                     ORDER BY count DESC",
                    [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
                );
            }
        }

        // Convertir a Collection si es necesario
        if (is_array($examStats)) {
            $examStats = collect($examStats);
        }

        // Calcular porcentajes
        $totalExams = $examStats->sum('count');
        foreach ($examStats as $stat) {
            $stat->percentage = $totalExams > 0 ? round(($stat->count / $totalExams) * 100, 2) : 0;

            // Asegurar que tenemos los campos necesarios
            if (empty($stat->categoria)) {
                $stat->categoria = 'General';
            }
            
            if (empty($stat->codigo)) {
                $stat->codigo = 'N/A';
            }
        }

        // Contar solicitudes por estado (para gráficos)
        $statusCounts = DetalleSolicitud::whereHas('solicitud', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->select('estado', DB::raw('count(*) as count'))
            ->groupBy('estado')
            ->get()
            ->pluck('count', 'estado')
            ->toArray();

        // Totales
        $totalRequests = Solicitud::whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->count();
        $totalPatients = Solicitud::whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->distinct('paciente_id')
            ->count('paciente_id');

        return [
            'examStats' => $examStats,
            'pendingCount' => $statusCounts['pendiente'] ?? 0,
            'inProcessCount' => $statusCounts['en_proceso'] ?? 0,
            'completedCount' => $statusCounts['completado'] ?? 0,
            'totalRequests' => $totalRequests,
            'totalPatients' => $totalPatients,
            'totalExams' => $totalExams
        ];
    }

    /**
     * Generar reporte por servicios
     */
    private function getServicesReport($startDate, $endDate, $serviceIds = null, $filterByServices = false)
    {
        \Log::info('getServicesReport called', [
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
            'serviceIds' => $serviceIds,
            'filterByServices' => $filterByServices
        ]);

        // Debug: Verificar datos en las tablas
        $totalSolicitudes = DB::table('solicitudes')->whereBetween('created_at', [$startDate, $endDate])->count();
        $totalServicios = DB::table('servicios')->count();

        \Log::info('Services Report Debug counts', [
            'totalSolicitudes' => $totalSolicitudes,
            'totalServicios' => $totalServicios
        ]);

        // Contar solicitudes por servicio
        try {
            $query = DB::table('solicitudes')
                ->join('servicios', 'solicitudes.servicio_id', '=', 'servicios.id')
                ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            // Filtrar por servicios específicos si se proporcionan
            if ($filterByServices && $serviceIds && is_array($serviceIds) && count($serviceIds) > 0) {
                $query->whereIn('servicios.id', $serviceIds);
            }

            $serviceStats = $query->select(
                    'servicios.id',
                    'servicios.nombre as name',
                    DB::raw('count(*) as count')
                )
                ->groupBy('servicios.id', 'servicios.nombre')
                ->orderByDesc('count')
                ->get();
        } catch (\Exception $e) {
            // Alternativa usando consulta SQL directa
            $whereClause = "sol.fecha BETWEEN ? AND ?";
            $params = [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];

            if ($filterByServices && $serviceIds && is_array($serviceIds) && count($serviceIds) > 0) {
                $placeholders = str_repeat('?,', count($serviceIds) - 1) . '?';
                $whereClause .= " AND s.id IN ($placeholders)";
                $params = array_merge($params, $serviceIds);
            }

            $serviceStats = DB::select(
                "SELECT s.id, s.nombre as name, COUNT(*) as count
                 FROM solicitudes sol
                 JOIN servicios s ON sol.servicio_id = s.id
                 WHERE $whereClause
                 GROUP BY s.id, s.nombre
                 ORDER BY count DESC",
                $params
            );
        }

        // Si hay servicios específicos seleccionados, obtener también los exámenes por servicio
        if ($filterByServices && $serviceIds && is_array($serviceIds) && count($serviceIds) > 0) {
            foreach ($serviceStats as $service) {
                // Obtener exámenes más solicitados para este servicio
                $examsByService = DB::table('solicitudes')
                    ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
                    ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                    ->where('solicitudes.servicio_id', $service->id)
                    ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->select(
                        'examenes.nombre as name',
                        DB::raw('count(*) as count')
                    )
                    ->groupBy('examenes.id', 'examenes.nombre')
                    ->orderByDesc('count')
                    ->limit(10) // Top 10 exámenes por servicio
                    ->get();

                // Calcular porcentajes dentro del servicio
                $totalExamsInService = $examsByService->sum('count');
                foreach ($examsByService as $exam) {
                    $exam->service_percentage = $totalExamsInService > 0
                        ? round(($exam->count / $totalExamsInService) * 100, 2)
                        : 0;
                }

                $service->exams = $examsByService;
                $service->unique_exams = count($examsByService);
            }
        }

        // Calcular porcentajes
        $totalServices = $serviceStats->sum('count');
        foreach ($serviceStats as $stat) {
            $stat->percentage = $totalServices > 0 ? round(($stat->count / $totalServices) * 100, 2) : 0;
        }

        // Obtener exámenes más solicitados por servicio
        $topExamsByService = [];
        
        // Primero verificar si hay datos básicos
        $totalDetalles = DB::table('detallesolicitud')->count();
        $totalSolicitudesConExamenes = DB::table('solicitudes')
            ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
            ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->count();
            
        \Log::info("Debug exámenes por servicio", [
            'total_detalles' => $totalDetalles,
            'solicitudes_con_examenes_en_rango' => $totalSolicitudesConExamenes,
            'servicios_encontrados' => count($serviceStats)
        ]);

        foreach ($serviceStats as $service) {
            try {
                // Primero verificar si este servicio tiene solicitudes con exámenes
                $solicitudesConExamenes = DB::table('solicitudes')
                    ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
                    ->where('solicitudes.servicio_id', $service->id)
                    ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->count();

                if ($solicitudesConExamenes > 0) {
                    // Si hay datos, hacer la consulta completa
                    $exams = DB::table('detallesolicitud')
                        ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                        ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                        ->leftJoin('categorias', 'examenes.categoria_id', '=', 'categorias.id')
                        ->where('solicitudes.servicio_id', $service->id)
                        ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                        ->select(
                            'examenes.id',
                            'examenes.nombre as name',
                            DB::raw('COALESCE(categorias.nombre, "Sin categoría") as category'),
                            DB::raw('count(*) as count')
                        )
                        ->groupBy('examenes.id', 'examenes.nombre', 'categorias.nombre')
                        ->orderByDesc('count')
                        ->limit(5)
                        ->get();
                } else {
                    $exams = collect([]);
                }

                \Log::info("Servicio {$service->id} ({$service->name})", [
                    'solicitudes_con_examenes' => $solicitudesConExamenes,
                    'examenes_encontrados' => count($exams),
                    'examenes' => $exams->toArray()
                ]);

                $topExamsByService[$service->id] = $exams;
            } catch (\Exception $e) {
                \Log::error("Error obteniendo exámenes para servicio {$service->id}: " . $e->getMessage());
                \Log::error("Stack trace: " . $e->getTraceAsString());
                $topExamsByService[$service->id] = collect([]);
            }
        }

        // Contar solicitudes por estado (para gráficos)
        $statusCounts = DetalleSolicitud::whereHas('solicitud', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->select('estado', DB::raw('count(*) as count'))
            ->groupBy('estado')
            ->get()
            ->pluck('count', 'estado')
            ->toArray();

        // Totales
        if ($filterByServices && $serviceIds && is_array($serviceIds) && count($serviceIds) > 0) {
            // Totales solo de los servicios seleccionados
            $totalServices = $serviceStats->count();
            // Contar solicitudes únicas para los servicios seleccionados
            $totalRequests = DB::table('solicitudes')
                ->whereIn('servicio_id', $serviceIds)
                ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->count();
            $totalExams = DB::table('solicitudes')
                ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
                ->whereIn('solicitudes.servicio_id', $serviceIds)
                ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->count();
            $totalPatients = DB::table('solicitudes')
                ->whereIn('servicio_id', $serviceIds)
                ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->distinct('paciente_id')
                ->count('paciente_id');
        } else {
            // Totales globales
            $totalServices = $serviceStats->count();
            $totalRequests = Solicitud::whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->count();
            $totalExams = $totalRequests;
            $totalPatients = Solicitud::whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->distinct('paciente_id')
                ->count('paciente_id');
        }

        $result = [
            'serviceStats' => $serviceStats,
            'topExamsByService' => $topExamsByService,
            'pendingCount' => $statusCounts['pendiente'] ?? 0,
            'inProcessCount' => $statusCounts['en_proceso'] ?? 0,
            'completedCount' => $statusCounts['completado'] ?? 0,
            'totalServices' => $totalServices,
            'totalRequests' => $totalRequests,
            'totalExams' => $totalExams,
            'totalPatients' => $totalPatients,
            'startDate' => $startDate->format('d/m/Y'),
            'endDate' => $endDate->format('d/m/Y')
        ];

        \Log::info('getServicesReport result', [
            'serviceStats_count' => count($serviceStats),
            'totalRequests' => $totalRequests,
            'totalPatients' => $totalPatients,
            'totalExams' => $totalExams,
            'serviceStats' => $serviceStats->toArray()
        ]);

        return $result;
        
    }

    /**
     * Generar reporte en PDF
     */
    public function generatePDF(Request $request)
    {
        $type = $request->input('type', 'general');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        \Log::info('🔍 generatePDF called', [
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'all_params' => $request->all()
        ]);

        // Validar fechas
        if (!$startDate || !$endDate) {
            return response()->json([
                'status' => false,
                'message' => 'Las fechas de inicio y fin son requeridas'
            ], 400);
        }

        // Convertir fechas a objetos Carbon
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        \Log::info('📅 Fechas procesadas', [
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate' => $endDate->format('Y-m-d H:i:s')
        ]);

        // Obtener datos según el tipo de reporte
        $data = [];

        switch ($type) {
            case 'general':
                $data = $this->getGeneralReport($startDate, $endDate);
                \Log::info('📊 Datos obtenidos para reporte general', [
                    'totalRequests' => $data['totalRequests'] ?? 'N/A',
                    'totalPatients' => $data['totalPatients'] ?? 'N/A',
                    'totalExams' => $data['totalExams'] ?? 'N/A',
                    'pendingCount' => $data['pendingCount'] ?? 'N/A',
                    'inProcessCount' => $data['inProcessCount'] ?? 'N/A',
                    'completedCount' => $data['completedCount'] ?? 'N/A',
                    'dailyStats_count' => is_array($data['dailyStats']) ? count($data['dailyStats']) : 'N/A',
                    'examStats_count' => is_array($data['examStats']) ? count($data['examStats']) : 'N/A',
                    'serviceStats_count' => is_array($data['serviceStats']) ? count($data['serviceStats']) : 'N/A'
                ]);
                $title = 'Reporte General';
                break;
            case 'exams':
                // Verificar si hay exámenes seleccionados para filtrar
                $examIds = $request->input('exam_ids');
                $filterByExams = $request->input('filter_by_exams', false);
                $data = $this->getExamsDetailedReport($startDate, $endDate, $examIds, $filterByExams);

                // Añadir flag para indicar que los datos ya están filtrados
                if ($filterByExams && $examIds) {
                    $data['selected_exams_only'] = true;
                }

                $title = 'Reporte por Exámenes';
                break;

            case 'services':
                
                // Verificar si hay servicios seleccionados para filtrar
                $serviceIds = $request->input('service_ids');
                $filterByServices = $request->input('filter_by_services', false);

                $data = $this->getServicesReport($startDate, $endDate, $serviceIds, $filterByServices);

                // Añadir flag para indicar que los datos ya están filtrados
                if ($filterByServices && $serviceIds) {
                    $data['selected_services_only'] = true;
                }

                $title = 'Reporte por Servicios';
                break;
            case 'doctors':
                   // Verificar si hay doctores seleccionados para filtrar
                $doctorsIds = $request->input('doctor_ids');
                $filterByDoctors = $request->input('filter_by_doctors', false);
                $data = $this->getDoctorsReportData($startDate, $endDate, $doctorsIds, $filterByDoctors);

                // Añadir flag para indicar que los datos ya están filtrados
                if ($filterByDoctors && $doctorsIds) {
                    $data['selected_doctors_only'] = true;
                }

                $title = 'Reporte por Doctores';
                break;

            case 'patients':
                $data = $this->getPatientsReport($startDate, $endDate);
                $title = 'Reporte por Pacientes';
                break;
            case 'results':
                $data = $this->getResultsReport($startDate, $endDate, $request->input('status'));
                $title = 'Reporte por Resultados';
                break;
            case 'categories':
                $data = $this->getCategoriesReport($startDate, $endDate);
                $title = 'Reporte por Categorías';
                break;
            case 'doctor_personal':
                // Reporte específico para un doctor
                $doctorId = $request->input('doctor_id');
                if (!$doctorId) {
                    $doctorId = $request->user()->id;
                }
                $data = $this->getDoctorPersonalReport($startDate, $endDate, $doctorId);
                $title = 'Mis Reportes';
                break;
            default:
                return response()->json([
                    'status' => false,
                    'message' => 'Tipo de reporte no válido'
                ], 400);
        }

        // Verificar si se debe mostrar el resumen
        $showSummary = true;

        // Si es un reporte de exámenes con exámenes seleccionados, no mostrar el resumen
        if ($type === 'exams' && $request->has('selected_exams_only') &&
            ($request->input('selected_exams_only') === 'true' || $request->input('selected_exams_only') === true)) {
            $showSummary = false;
        }

        // Preparar datos y plantilla según el tipo de reporte
        $user = $request->user();
        $generatedBy = $user ? ($user->nombre . ' ' . $user->apellido) : 'Sistema';
        
        $viewData = [
            'title' => $title,
            'startDate' => $startDate,  // Pasar como objeto Carbon
            'endDate' => $endDate,      // Pasar como objeto Carbon
            'reportType' => ucfirst($type),
            'generatedBy' => $generatedBy,
        ];


        switch ($type) {
            case 'general':
                // Usar plantilla específica para reportes generales detallados
                $templateName = 'reports.general.general-detailed-pdf';
                $viewData = array_merge($viewData, [
                    // Variables principales que espera la plantilla
                    'totalRequests' => $data['totalRequests'] ?? 0,
                    'totalPatients' => $data['totalPatients'] ?? 0,
                    'totalExams' => $data['totalExams'] ?? 0,
                    'pendingCount' => $data['pendingCount'] ?? 0,
                    'inProcessCount' => $data['inProcessCount'] ?? 0,
                    'completedCount' => $data['completedCount'] ?? 0,
                    'dailyStats' => $data['dailyStats'] ?? [],
                    
                    // Variables adicionales específicas para el template detallado
                    'solicitudes' => $data['solicitudes'] ?? [],
                    'examStats' => $data['examStats'] ?? [],
                    'serviceStats' => $data['serviceStats'] ?? [],
                    'patients' => $data['patients'] ?? [],
                    'topDoctores' => $data['top_doctores'] ?? []
                ]);
                
                \Log::info('📄 ViewData para PDF general detallado', [
                    'xd' => $viewData,
                    'template' => $templateName,
                    'totalRequests' => $viewData['totalRequests'],
                    'totalPatients' => $viewData['totalPatients'],
                    'totalExams' => $viewData['totalExams'],
                    'pendingCount' => $viewData['pendingCount'],
                    'inProcessCount' => $viewData['inProcessCount'],
                    'completedCount' => $viewData['completedCount'],
                    'dailyStats_count' => count($viewData['dailyStats']),
                    'examStats_count' => count($viewData['examStats']),
                    'serviceStats_count' => count($viewData['serviceStats']),
                    'patients_count' => count($viewData['patients'])
                ]);
                break;
            case 'categories':
                // Siempre usar la plantilla detallada para categorías
                $templateName = 'reports.categories.categories-detailed-pdf';
                
                \Log::info('PDF Categories - Data received:', $data);
                $viewData = array_merge($viewData, [
                    'categoryStats' => $data['categoryStats'] ?? [],
                    'topExamsByCategory' => $data['topExamsByCategory'] ?? [],
                    'totalRequests' => $data['totalRequests'] ?? 0,
                    'totalPatients' => $data['totalPatients'] ?? 0,
                    'totalExams' => $data['totalExams'] ?? 0,
                ]);
                
                // Verificación adicional para datos faltantes
                if (empty($viewData['categoryStats'])) {
                    $viewData['categoryStats'] = [];
                }
                if (empty($viewData['topExamsByCategory'])) {
                    $viewData['topExamsByCategory'] = [];
                }
                
                \Log::info('PDF Categories - ViewData prepared, using template: ' . $templateName, [
                    'totalCategories' => count($viewData['categoryStats']),
                    'totalExamsByCategory' => count($viewData['topExamsByCategory']),
                    'totalRequests' => $viewData['totalRequests'],
                    'totalPatients' => $viewData['totalPatients'],
                    'totalExams' => $viewData['totalExams']
                ]);
                break;
            case 'doctors':
                $templateName = 'reports.doctors.doctors-detailed-pdf';
                $viewData = array_merge($viewData, [
                    'doctorStats' => $data['doctorStats'] ?? [],
                    'resultStats' => $data['resultStats'] ?? [],
                    'totalDoctors' => $data['totalDoctors'] ?? 0,
                    'totalRequests' => $data['totalRequests'] ?? 0,
                    'totalPatients' => $data['totalPatients'] ?? 0,
                    'totalExams' => $data['totalExams'] ?? 0,
                ]);
                break;
            case 'patients':
                $templateName = 'reports.patients.patients-detailed-pdf';

                // Obtener todos los resultados detallados usando la misma función que el Excel
                $allDetailedResults = $this->getDetailedResultsData($startDate, $endDate, null);

                \Log::info('PDF Pacientes - Datos obtenidos', [
                    'total_detailed_results' => count($allDetailedResults),
                    'total_patients' => count($data['patients'] ?? []),
                    'sample_detailed_result' => !empty($allDetailedResults) ? [
                        'paciente_dni' => $allDetailedResults[0]->paciente->dni ?? 'NO_DNI',
                        'paciente_nombres' => $allDetailedResults[0]->paciente->nombres ?? 'NO_NOMBRES',
                        'examen_nombre' => $allDetailedResults[0]->examen->nombre ?? 'NO_EXAMEN'
                    ] : 'Sin resultados'
                ]);

                // Agrupar resultados por paciente
                $patientsWithResults = [];
                $patients = $data['patients'] ?? [];

                foreach ($patients as $patient) {
                    // Convertir el paciente a array primero
                    $patientArray = is_array($patient) ? $patient : (array) $patient;
                    $patientId = $patientArray['id'] ?? null;

                    // Asegurar que el nombre esté disponible
                    if (!isset($patientArray['name'])) {
                        $patientArray['name'] = trim(($patientArray['nombres'] ?? '') . ' ' . ($patientArray['apellidos'] ?? ''));
                        if (empty($patientArray['name'])) {
                            $patientArray['name'] = 'Paciente sin nombre';
                        }
                    }

                    if ($patientId) {
                        // Obtener DNI del paciente para hacer el match
                        $patientDNI = $patientArray['documento'] ?? $patientArray['dni'] ?? null;

                        \Log::info('PDF Pacientes - Procesando paciente', [
                            'patient_id' => $patientId,
                            'patient_name' => $patientArray['name'],
                            'patient_dni' => $patientDNI,
                            'patient_keys' => array_keys($patientArray)
                        ]);

                        // Filtrar resultados para este paciente específico
                        $resultadosDelPaciente = [];
                        $matchCount = 0;

                        foreach ($allDetailedResults as $resultado) {
                            // Los resultados vienen como objetos con estructura anidada
                            // Hacer match por DNI ya que los IDs no coinciden
                            $resultadoDNI = $resultado->paciente->dni ?? null;

                            if ($patientDNI && $resultadoDNI && $patientDNI === $resultadoDNI) {
                                $matchCount++;
                                $resultadosDelPaciente[] = [
                                    'fecha' => $resultado->fecha_resultado ?? '',
                                    'solicitud_id' => $resultado->solicitud_id ?? '',
                                    'numero_recibo' => $resultado->solicitud_id ?? '', // Usar solicitud_id como número
                                    'examen_nombre' => $resultado->examen->nombre ?? 'Sin nombre',
                                    'campo_nombre' => $resultado->campo_nombre ?? 'Resultado general',
                                    'resultado_valor' => $resultado->valor ?? 'Pendiente',
                                    'resultado_directo' => $resultado->valor ?? 'Pendiente',
                                    'valor_referencia' => $resultado->valor_referencia ?? 'Sin referencia',
                                    'unidad' => $resultado->unidad ?? '',
                                    'estado_examen' => $resultado->estado ?? 'Sin estado',
                                    'observaciones' => $resultado->observaciones ?? ''
                                ];
                            }
                        }

                        \Log::info('PDF Pacientes - Resultados encontrados', [
                            'patient_dni' => $patientDNI,
                            'matches_found' => $matchCount,
                            'total_resultados' => count($resultadosDelPaciente)
                        ]);

                        // Agregar los resultados al paciente
                        $patientArray['resultados_detallados'] = $resultadosDelPaciente;
                        $patientsWithResults[] = $patientArray;

                        \Log::info('Resultados detallados para paciente PDF', [
                            'patient_id' => $patientId,
                            'patient_name' => $patientArray['name'],
                            'resultados_count' => count($resultadosDelPaciente),
                            'sample_resultado' => !empty($resultadosDelPaciente) ? $resultadosDelPaciente[0] : 'Sin resultados'
                        ]);
                    } else {
                        // Agregar paciente sin resultados
                        $patientArray['resultados_detallados'] = [];
                        $patientsWithResults[] = $patientArray;
                    }
                }

                $viewData = array_merge($viewData, [
                    'patientStats' => $patientsWithResults,
                    'patients' => $data['patients'] ?? [],
                    'genderStats' => $data['genderStats'] ?? [],
                    'ageStats' => $data['ageStats'] ?? [],
                    'topPatients' => $data['topPatients'] ?? [],
                    'examStatusStats' => $data['examStatusStats'] ?? [],
                    'totalRequests' => $data['totalRequests'] ?? 0,
                    'totalPatients' => $data['totalPatients'] ?? 0,
                    'totalDoctors' => $data['totalDoctors'] ?? 0,
                    'totalExams' => $data['totalExams'] ?? 0,
                    'pendingCount' => $data['pendingCount'] ?? 0,
                    'inProcessCount' => $data['inProcessCount'] ?? 0,
                    'completedCount' => $data['completedCount'] ?? 0,
                ]);
                break;

            case 'exams':
                // Usar plantilla específica para reportes de exámenes detallados
                $templateName = 'reports.exams.exams-detailed-pdf';
                $viewData = array_merge($viewData, [
                    'totalExams' => $data['totalExams'] ?? 0,
                    'totalCategories' => $data['totalCategories'] ?? 0,
                    'uniqueExams' => $data['uniqueExams'] ?? 0,
                    'mostRequestedExam' => $data['mostRequestedExam'] ?? 'N/A',
                    'topCategory' => $data['topCategory'] ?? 'N/A',
                    'examStats' => $data['examStats'] ?? [],
                    'examsByCategory' => $data['examsByCategory'] ?? [],
                    'categoryStats' => $data['categoryStats'] ?? [],
                    'dailyExamStats' => $data['dailyExamStats'] ?? []
                ]);
                break;
            case 'services':
                // Usar directamente la plantilla detailed-pdf
                $templateName = 'reports.services.services-detailed-pdf';
                $user = $request->user();
                $generatedBy = $user ? ($user->nombres . ' ' . $user->apellidos) : 'Sistema';

                $viewData = array_merge($viewData, [
                    'serviceStats' => $data['serviceStats'] ?? [],
                    'totalRequests' => $data['totalRequests'] ?? 0,
                    'totalPatients' => $data['totalPatients'] ?? 0,
                    'totalExams' => $data['totalExams'] ?? 0,
                    'totalServices' => $data['totalServices'] ?? 0,
                    'generatedBy' => $generatedBy,
                ]);

                \Log::info('PDF Servicios - Datos enviados:', [
                    'serviceStats_count' => count($viewData['serviceStats']),
                    'totalRequests' => $viewData['totalRequests'],
                    'totalServices' => $viewData['totalServices'],
                    'template' => $templateName
                ]);
                break;
            case 'results':
                // Usar plantilla específica para reportes de resultados detallados
                $templateName = 'reports.results.results-detailed-pdf';

                // Obtener resultados detallados individuales para mostrar en el PDF
                $detailedResults = $this->getDetailedResultsData($startDate, $endDate, $request->input('status'));

                $viewData = array_merge($viewData, [
                    'statusCounts' => $data['statusCounts'] ?? [],
                    'dailyStats' => $data['dailyStats'] ?? [],
                    'processingTimeStats' => $data['processingTimeStats'] ?? [],
                    'examStats' => $data['examStats'] ?? [],
                    'examsByCategory' => $data['examsByCategory'] ?? [],
                    'categoryStats' => $data['categoryStats'] ?? [],
                    'totalRequests' => $data['totalRequests'] ?? 0,
                    'totalPatients' => $data['totalPatients'] ?? 0,
                    'totalExams' => $data['totalExams'] ?? 0,
                    'pendingCount' => $data['pendingCount'] ?? 0,
                    'inProcessCount' => $data['inProcessCount'] ?? 0,
                    'completedCount' => $data['completedCount'] ?? 0,
                    'detailedResults' => $detailedResults, // ← Agregar resultados detallados
                ]);
                
                \Log::info('📄 ViewData para PDF resultados detallado', [
                    'template' => $templateName,
                    'totalRequests' => $viewData['totalRequests'],
                    'totalPatients' => $viewData['totalPatients'],
                    'totalExams' => $viewData['totalExams'],
                    'pendingCount' => $viewData['pendingCount'],
                    'inProcessCount' => $viewData['inProcessCount'],
                    'completedCount' => $viewData['completedCount'],
                    'dailyStats_count' => count($viewData['dailyStats']),
                    'examStats_count' => count($viewData['examStats']),
                    'categoryStats_count' => count($viewData['categoryStats']),
                    'statusCounts' => $viewData['statusCounts'],
                    'processingTimeStats_count' => count($viewData['processingTimeStats']),
                    'detailedResults_count' => count($detailedResults),
                    'sample_detailed_result' => !empty($detailedResults) ? [
                        'paciente_nombres' => $detailedResults[0]->paciente->nombres ?? 'NO_NOMBRES',
                        'examen_nombre' => $detailedResults[0]->examen->nombre ?? 'NO_EXAMEN',
                        'valor' => $detailedResults[0]->valor ?? 'NO_VALOR'
                    ] : 'Sin resultados detallados'
                ]);
                break;
            default:
                // Para reportes generales y otros - usar plantilla universal
                $templateName = 'reports.universal-report-pdf';
                $viewData = array_merge($viewData, [
                    // Variables principales que espera la plantilla
                    'totalRequests' => $data['totalRequests'] ?? 0,
                    'totalPatients' => $data['totalPatients'] ?? 0,
                    'totalExams' => $data['totalExams'] ?? 0,
                    'pendingCount' => $data['pendingCount'] ?? 0,
                    'inProcessCount' => $data['inProcessCount'] ?? 0,
                    'completedCount' => $data['completedCount'] ?? 0,
                    'dailyStats' => $data['dailyStats'] ?? [],

                    // Variables adicionales para compatibilidad
                    'solicitudes' => $data['solicitudes'] ?? [],
                    'stats' => [
                        'total_solicitudes' => $data['totalRequests'] ?? 0,
                        'total_pacientes' => $data['totalPatients'] ?? 0,
                        'total_examenes' => $data['totalExams'] ?? 0,
                        'pendientes' => $data['pendingCount'] ?? 0,
                        'en_proceso' => $data['inProcessCount'] ?? 0,
                        'completadas' => $data['completedCount'] ?? 0,
                    ],
                    'topExamenes' => $data['examStats'] ?? [],
                    'topServicios' => $data['serviceStats'] ?? [],
                    'topDoctores' => $data['top_doctores'] ?? []
                ]);

                \Log::info('📄 ViewData para PDF general', [
                    'template' => $templateName,
                    'totalRequests' => $viewData['totalRequests'],
                    'totalPatients' => $viewData['totalPatients'],
                    'totalExams' => $viewData['totalExams'],
                    'pendingCount' => $viewData['pendingCount'],
                    'inProcessCount' => $viewData['inProcessCount'],
                    'completedCount' => $viewData['completedCount'],
                    'dailyStats_count' => count($viewData['dailyStats'])
                ]);
                break;
        }

        // Generar PDF con la plantilla específica
        \Log::info('PDF Generation - Template:', ['template' => $templateName, 'type' => $type]);
        
        try {
            $pdf = Pdf::loadView($templateName, $viewData);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

            // Establecer metadatos del PDF para que aparezca el título
            $pdfTitle = $viewData['title'] ?? 'Reporte Laboratorio Clínico Laredo';
            $fileName = "reporte_laboratorio_{$type}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}.pdf";
            
            // Configurar headers para el navegador
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ];

            return response($pdf->output(), 200, $headers);
        } catch (\Exception $e) {
            \Log::error('Error al generar PDF: ' . $e->getMessage(), [
                'template' => $templateName,
                'type' => $type,
                'viewData_keys' => array_keys($viewData),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Error al generar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar reporte en Excel
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function generateExcel(Request $request)
    {

        
        try {
            $type = $request->input('type', 'general');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            // Validar fechas
            if (!$startDate || !$endDate) {
                return response()->json([
                    'status' => false,
                    'message' => 'Las fechas de inicio y fin son requeridas'
                ], 400);
            }
            
            // Opciones adicionales para filtros específicos
            $options = [];
            
            // Para exámenes, permitir filtrar por ID de examen
            if ($type === 'exams' && $request->has('exam_ids')) {
                $options['exam_ids'] = is_array($request->input('exam_ids')) 
                    ? $request->input('exam_ids') 
                    : explode(',', $request->input('exam_ids'));
                $options['filter_by_exams'] = true;
            }
            
            // Para servicios, permitir filtrar por ID de servicio
            if ($type === 'services' && $request->has('service_ids')) {
                $options['service_ids'] = is_array($request->input('service_ids'))
                    ? $request->input('service_ids')
                    : explode(',', $request->input('service_ids'));
                $options['filter_by_services'] = true;
            }

            // Para doctores, permitir filtrar por ID de doctor
            if ($type === 'doctors' && $request->has('doctor_ids')) {
                $options['doctor_ids'] = is_array($request->input('doctor_ids'))
                    ? $request->input('doctor_ids')
                    : explode(',', $request->input('doctor_ids'));
                $options['filter_by_doctors'] = true;

                \Log::info('Filtros de doctores aplicados en Excel:', [
                    'doctor_ids' => $options['doctor_ids'],
                    'filter_by_doctors' => $options['filter_by_doctors']
                ]);
            }

            // Para resultados, permitir filtrar por estado
            if ($type === 'results' && $request->has('status')) {
                $options['status'] = $request->input('status');

                \Log::info('Filtro de estado aplicado en Excel:', [
                    'status' => $options['status']
                ]);
            }
            
            // Obtener datos del reporte
            $reportData = $this->getReportData($type, $startDate, $endDate, $options);
            
            if (!$reportData) {
                return response()->json([
                    'status' => false,
                    'message' => 'No hay datos para generar el reporte'
                ], 404);
            }
            
            // Añadir información del usuario que generó el reporte
            $reportData['generatedBy'] = $request->user() ? $request->user()->nombre . ' ' . $request->user()->apellido : 'Sistema';
            
            // Formatear nombre del archivo
            $fileName = 'reporte_' . $type . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
            
         
            
            // Crear y devolver el Excel usando el sistema modular
            switch ($type) {
                case 'general':
                    \Log::info('Usando GeneralReportExport para reporte general modular');
                    return Excel::download(
                        new GeneralReportExport($reportData, $startDate, $endDate), 
                        $fileName
                    );
                
                case 'patients':
                 
                    return Excel::download(
                        new PatientsReportExport($reportData, $startDate, $endDate),
                        $fileName
                    );

                case 'patients_detailed_results':
                    \Log::info('Usando PatientsDetailedResultsExport para reporte detallado de resultados por paciente');
                    return Excel::download(
                        new \App\Exports\Patients\PatientsDetailedResultsExport($reportData, $startDate, $endDate),
                        'reporte_pacientes_resultados_detallados_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx'
                    );
                
                case 'exams':
                    \Log::info('Usando ExamsReportExport para reporte detallado de exámenes');
                    return Excel::download(
                        new ExamsReportExport($reportData, $startDate, $endDate), 
                        $fileName
                    );
                
                case 'exams_detailed':
                    \Log::info('Usando ExamsReportExport para reporte detallado de exámenes (exams_detailed)');
                    return Excel::download(
                        new ExamsReportExport($reportData, $startDate, $endDate), 
                        $fileName
                    );
                
                case 'doctors':
                    \Log::info('Usando DoctorsReportExport para reporte de doctores');
                    return Excel::download(
                        new \App\Exports\Doctors\DoctorsReportExport($reportData, $startDate, $endDate), 
                        $fileName
                    );
                
                case 'results':
                    \Log::info('Usando ResultsReportExport (modular) para reporte de resultados');
                    return Excel::download(
                        new ResultsReportExport($reportData, $startDate, $endDate), 
                        $fileName
                    );
                
                case 'services':
                    \Log::info('Usando ServicesMainReportExport para reporte independiente de servicios');
                    return Excel::download(
                        new ServicesReportExport($reportData, $startDate, $endDate), 
                        $fileName
                    );
                                case 'categories':
                    \Log::info('Usando CategoriesReportExport para reporte de categorías');
                    return Excel::download(
                        new \App\Exports\Categories\CategoriesReportExport($reportData, $startDate, $endDate), 
                        $fileName
                    );
                
                default:
                    \Log::info('Usando ReportExcelExport (legacy) para tipo: ' . $type);
                    return Excel::download(
                        new ReportExcelExport($reportData, $type, $startDate, $endDate), 
                        $fileName
                    );
            }
            
        } catch (\Exception $e) {
            \Log::error('Error al generar Excel: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Error al generar el archivo Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos para el reporte según el tipo
     * 
     * @param string $type Tipo de reporte (general, patients, exams, doctors, services)
     * @param string $startDate Fecha inicial
     * @param string $endDate Fecha final
     * @param array $options Opciones adicionales para filtros específicos
     * @return array|null
     */
    private function getReportData($type, $startDate, $endDate, array $options = [])
    {
        // Convertir fechas a objetos Carbon
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        // Datos generales que incluiremos en todos los reportes
        $data = [
            'periodo' => [
                'inicio' => $start->format('d/m/Y'),
                'fin' => $end->format('d/m/Y'),
            ],
            'generado' => Carbon::now()->format('d/m/Y H:i:s'),
            'tipo' => $type
        ];
        
        // Añadir estadísticas básicas para todos los reportes
        $data['totales'] = [
            'solicitudes' => Solicitud::whereBetween('fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])->count(),
            'pacientes' => Solicitud::whereBetween('fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                        ->distinct('paciente_id')
                        ->count('paciente_id'),
            'examenes_realizados' => DetalleSolicitud::whereHas('solicitud', function($q) use ($start, $end) {
                                $q->whereBetween('fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
                            })->count()
        ];
        
        // Datos específicos según el tipo de reporte
        switch ($type) {
            case 'general':
                // Para el reporte general, cargar TODOS los datos necesarios para las 8 hojas
                \Log::info('Cargando datos completos para reporte general modular');
                
                $data['patients'] = $this->getPatientsReportData($start, $end);
                $data['solicitudes'] = $this->getSolicitudesReportData($start, $end);
                $data['examenes'] = $this->getExamsReportData($start, $end, null, false);
                $data['resultados'] = $this->getDetailedResultsData($start, $end, null);
                $data['doctores'] = $this->getDoctorsReportData($start, $end);
                $data['servicios'] = $this->getServicesReportData($start, $end, null, false);
                $data['generatedBy'] = $options['usuario'] ?? 'Sistema';
                
                // Calcular estadísticas avanzadas basadas en los datos reales
                $data = $this->calculateAdvancedStats($data, $start, $end);
                
                \Log::info('Datos cargados para reporte general', [
                    'patients_count' => is_countable($data['patients']) ? count($data['patients']) : 0,
                    'solicitudes_count' => is_countable($data['solicitudes']) ? count($data['solicitudes']) : 0,
                    'examenes_count' => is_countable($data['examenes']) ? count($data['examenes']) : 0,
                    'resultados_count' => is_countable($data['resultados']) ? $data['resultados']->count() : 0,
                    'doctores_count' => is_countable($data['doctores']) ? count($data['doctores']) : 0,
                    'servicios_count' => is_countable($data['servicios']) ? count($data['servicios']) : 0
                ]);
                break;
                
            case 'patients':
                // Usar el método completo que incluye todas las estadísticas calculadas
                $data = array_merge($data, $this->getPatientsReport($start, $end));
                break;

            case 'patients_detailed_results':
                // Usar el mismo método que patients pero con enfoque en resultados detallados
                $data = array_merge($data, $this->getPatientsReport($start, $end));
                break;
            
            case 'exams':
                // Para exámenes, verificar si hay filtros específicos
                $examIds = $options['exam_ids'] ?? null;
                $filterByExams = $options['filter_by_exams'] ?? false;
                
                // Obtener datos filtrados o generales según corresponda
                if ($filterByExams && $examIds) {
                    \Log::info('Aplicando filtros a exámenes para Excel', [
                        'exam_ids' => $examIds,
                        'filter_by_exams' => $filterByExams
                    ]);
                }
                
                $data['examenes'] = $this->getExamsReportData($start, $end, $examIds, $filterByExams);
                break;
            
            case 'doctors':
                // Para doctores, verificar si hay filtros específicos
                $doctorIds = $options['doctor_ids'] ?? null;
                $filterByDoctors = $options['filter_by_doctors'] ?? false;
                
                // Obtener datos de doctores
                $doctorsData = $this->getDoctorsReportData($start, $end, $doctorIds, $filterByDoctors);
                
                // Asegurarnos de que todos los datos están disponibles en el formato correcto
                $data['doctorStats'] = $doctorsData['doctorStats'] ?? [];
                $data['resultStats'] = $doctorsData['resultStats'] ?? [];
                $data['totalRequests'] = $doctorsData['totalRequests'] ?? 0;
                $data['totalPatients'] = $doctorsData['totalPatients'] ?? 0;
                $data['totalExams'] = $doctorsData['totalExams'] ?? 0;
                
                // Mantener compatibilidad con la clave 'doctores' para el reporte general
                $data['doctores'] = $data['doctorStats'];
                break;
            
            case 'services':
                // Para servicios, verificar si hay filtros específicos
                $serviceIds = $options['service_ids'] ?? null;
                $filterByServices = $options['filter_by_services'] ?? false;
                
                // Obtener datos filtrados o generales según corresponda
                if ($filterByServices && $serviceIds) {
                    \Log::info('Aplicando filtros a servicios para Excel', [
                        'service_ids' => $serviceIds,
                        'filter_by_services' => $filterByServices
                    ]);
                }
                
                $data['servicios'] = $this->getServicesReportData($start, $end, $serviceIds, $filterByServices);
                
                // Ahora calcular topExamsByService usando los servicios obtenidos
                $topExamsByService = [];
                $serviceStats = $data['servicios'];
                
                // Debug: Verificar datos básicos
                $totalDetalles = DB::table('detallesolicitud')->count();
                $totalSolicitudesConExamenes = DB::table('solicitudes')
                    ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
                    ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->count();
                    
                \Log::info("Debug exámenes por servicio en Excel", [
                    'total_detalles' => $totalDetalles,
                    'solicitudes_con_examenes_en_rango' => $totalSolicitudesConExamenes,
                    'servicios_encontrados' => count($serviceStats)
                ]);

                foreach ($serviceStats as $service) {
                    try {
                        // Verificar si este servicio tiene solicitudes con exámenes
                        $solicitudesConExamenes = DB::table('solicitudes')
                            ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
                            ->where('solicitudes.servicio_id', $service->id)
                            ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                            ->count();

                        if ($solicitudesConExamenes > 0) {
                            // Si hay datos, hacer la consulta completa
                            $exams = DB::table('detallesolicitud')
                                ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                                ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                                ->leftJoin('categorias', 'examenes.categoria_id', '=', 'categorias.id')
                                ->where('solicitudes.servicio_id', $service->id)
                                ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                                ->select(
                                    'examenes.id',
                                    'examenes.nombre as name',
                                    DB::raw('COALESCE(categorias.nombre, "Sin categoría") as category'),
                                    DB::raw('count(*) as count')
                                )
                                ->groupBy('examenes.id', 'examenes.nombre', 'categorias.nombre')
                                ->orderByDesc('count')
                                ->limit(5)
                                ->get();
                        } else {
                            $exams = collect([]);
                        }

                        \Log::info("Servicio para Excel: {$service->id} ({$service->nombre})", [
                            'solicitudes_con_examenes' => $solicitudesConExamenes,
                            'examenes_encontrados' => count($exams),
                            'examenes' => $exams->toArray()
                        ]);

                        $topExamsByService[$service->id] = $exams;
                    } catch (\Exception $e) {
                        \Log::error("Error obteniendo exámenes para servicio {$service->id} en Excel: " . $e->getMessage());
                        $topExamsByService[$service->id] = collect([]);
                    }
                }
                
                // Agregar topExamsByService a los datos y también serviceStats para la hoja
                $data['topExamsByService'] = $topExamsByService;
                $data['serviceStats'] = $serviceStats;
                break;
            
            case 'results':
                // Para resultados, verificar si hay filtros específicos
                $status = $options['status'] ?? null;
                
                // Obtener datos de resultados
                $resultsData = $this->getResultsReport($start, $end, $status);
                
                // Obtener datos detallados de resultados para la exportación
                $detailedResults = $this->getDetailedResultsData($start, $end, $status);
                
                // Combinar los datos de resultados con los datos básicos
                $data = array_merge($data, $resultsData);
                
                // Añadir los resultados detallados a los datos
                $data['detailed_results'] = $detailedResults;
                
                // Añadir información adicional para el Excel
                $data['generatedBy'] = $options['usuario'] ?? 'Sistema';
                

                break;
                            case 'categories':
                // Obtener datos de categorías directamente
                $categoriesData = $this->getCategoriesReport($start, $end);
                
                // Combinar los datos de categorías con los datos básicos
                $data = array_merge($data, $categoriesData);
                
                // Añadir información adicional para el Excel
                $data['generatedBy'] = $options['usuario'] ?? 'Sistema';
                
                \Log::info('Datos del reporte de categorías obtenidos', [
                    'categoryStats_count' => count($categoriesData['categoryStats']),
                    'topExamsByCategory_count' => count($categoriesData['topExamsByCategory']),
                    'totalRequests' => $categoriesData['totalRequests'],
                    'totalPatients' => $categoriesData['totalPatients'],
                    'totalExams' => $categoriesData['totalExams']
                ]);
                

                break;
        }
        
        return $data;
    }

    /**
     * Wrapper method for patients report
     */
    private function getPatientsReport($startDate, $endDate)
    {


        $patients = $this->getPatientsReportData($startDate, $endDate);
        
        // Calcular estadísticas adicionales para la plantilla detallada
        $totalPatients = count($patients);
        $totalRequests = $patients->sum('total_solicitudes');
        $totalExams = $patients->sum('total_examenes');

      
        
        // Estadísticas por género
        $genderStats = $patients->groupBy('sexo')->map(function ($group, $gender) {
            return [
                'name' => $gender === 'masculino' ? 'Masculino' : 
                         ($gender === 'femenino' ? 'Femenino' : 'No especificado'),
                'count' => $group->count()
            ];
        })->values();


        
        // Estadísticas por edad
        $ageStats = $patients->groupBy(function ($patient) {
            $age = $patient->edad ?? 0;
            if ($age < 18) return '0-17 años';
            if ($age < 30) return '18-29 años';
            if ($age < 50) return '30-49 años';
            if ($age < 70) return '50-69 años';
            return '70+ años';
        })->map(function ($group, $ageGroup) {
            return [
                'name' => $ageGroup,
                'count' => $group->count()
            ];
        })->values();

       
        
        // Top pacientes más activos
        $topPatients = $patients->sortByDesc('total_solicitudes')
            ->take(10)
            ->map(function ($patient) {
                $name = trim(($patient->nombres ?? '') . ' ' . ($patient->apellidos ?? ''));
                if (empty($name)) {
                    $name = 'Paciente ID: ' . $patient->id;
                }
                return [
                    'name' => $name,
                    'documento' => $patient->documento ?? $patient->dni ?? 'Sin documento',
                    'ultima_visita' => $patient->ultima_visita ?? 'Sin visitas',
                    'count' => $patient->total_solicitudes ?? 0
                ];
            })->values();


        
        // Estados de exámenes
        $examStatusStats = [
            [
                'name' => 'Completados',
                
                'count' => $patients->sum('examenes_completados')
            ],
            [
                'name' => 'Pendientes', 
                'count' => $patients->sum('examenes_pendientes')
            ],
            [
                'name' => 'Fuera de Rango',
                'count' => $patients->sum('total_resultados_fuera_rango')
            ]
        ];

       
        
        // Contadores para el resumen ejecutivo
        $pendingCount = $patients->sum('examenes_pendientes');
        $completedCount = $patients->sum('examenes_completados');
        $inProcessCount = $totalExams - $pendingCount - $completedCount;
        
        // Contar doctores únicos involucrados
        $totalDoctors = DB::table('solicitudes')
            ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->distinct('user_id')
            ->count('user_id');

        $reportData = [
            'patients' => $patients,
            'patientStats' => $topPatients, // Para compatibilidad con la plantilla
            'genderStats' => $genderStats,
            'ageStats' => $ageStats,
            'topPatients' => $topPatients,
            'examStatusStats' => $examStatusStats,
            'totalPatients' => $totalPatients,
            'totalRequests' => $totalRequests,
            'totalExams' => $totalExams,
            'totalDoctors' => $totalDoctors,
            'pendingCount' => $pendingCount,
            'inProcessCount' => $inProcessCount,
            'completedCount' => $completedCount
        ];

      
        
        return $reportData;
    }

    /**
     * Obtener datos para reporte de pacientes
     */
    private function getPatientsReportData($start, $end)
    {
     
        $patients = DB::table('pacientes')
            ->join('solicitudes', 'pacientes.id', '=', 'solicitudes.paciente_id')
            ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
            ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
            ->select(
                'pacientes.id',
                'pacientes.nombres',
                'pacientes.apellidos',
                'pacientes.dni as documento',
                'pacientes.fecha_nacimiento',
                'pacientes.sexo',
                'pacientes.edad_gestacional',
                'pacientes.celular',
                'pacientes.historia_clinica',
                DB::raw('TIMESTAMPDIFF(YEAR, pacientes.fecha_nacimiento, CURDATE()) as edad'),
                DB::raw('COUNT(DISTINCT solicitudes.id) as total_solicitudes'),
                DB::raw('COUNT(detallesolicitud.id) as total_examenes'),
                DB::raw('MAX(solicitudes.fecha) as ultima_visita'),
                DB::raw('MIN(solicitudes.fecha) as primera_visita')
            )
            ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->groupBy('pacientes.id', 'pacientes.nombres', 'pacientes.apellidos', 'pacientes.dni', 
                       'pacientes.fecha_nacimiento', 'pacientes.sexo', 'pacientes.edad_gestacional', 'pacientes.celular',
                       'pacientes.historia_clinica')
            ->orderBy('total_solicitudes', 'desc')
            ->get();

      

        // Añadir información detallada de las solicitudes y resultados para cada paciente
        foreach ($patients as &$patient) {
            // Obtener solicitudes y sus detalles con información del médico
            $solicitudes = DB::table('solicitudes')
                ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
                ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                ->leftJoin('servicios', 'solicitudes.servicio_id', '=', 'servicios.id')
                ->leftJoin('users', 'solicitudes.user_id', '=', 'users.id')
                ->where('solicitudes.paciente_id', $patient->id)
                ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->select(
                    'solicitudes.id as solicitud_id',
                    'solicitudes.fecha',
                    'solicitudes.hora',
                    'solicitudes.numero_recibo',
                    'solicitudes.estado as estado_solicitud',
                    'servicios.nombre as servicio',
                    'examenes.nombre as examen',
                    'examenes.codigo as codigo_examen',
                    'detallesolicitud.id as detalle_id',
                    'detallesolicitud.estado',
                    'detallesolicitud.resultado',
                    'detallesolicitud.observaciones as observaciones_detalle',
                    DB::raw('CONCAT(users.nombre, " ", users.apellido) as medico_solicitante')
                )
                ->orderBy('solicitudes.fecha', 'desc')
                ->orderBy('solicitudes.hora', 'desc')
                ->get();

            // Agrupar las solicitudes y agregar sus resultados
            $patient->solicitudes_detalle = [];
            $solicitudesAgrupadas = [];
            
            foreach ($solicitudes as $solicitud) {
                $solicitudId = $solicitud->solicitud_id;
                
                // Si es la primera vez que vemos esta solicitud, la inicializamos
                if (!isset($solicitudesAgrupadas[$solicitudId])) {
                    $solicitudesAgrupadas[$solicitudId] = (object)[
                        'id' => $solicitud->solicitud_id,
                        'fecha' => $solicitud->fecha,
                        'hora' => $solicitud->hora,
                        'numero_recibo' => $solicitud->numero_recibo,
                        'estado_solicitud' => $solicitud->estado_solicitud,
                        'servicio' => $solicitud->servicio,
                        'medico_solicitante' => $solicitud->medico_solicitante,
                        'examenes' => []
                    ];
                }
                
                // Obtener los resultados de este examen específico
                $resultados = DB::table('valores_resultado')
                    ->join('campos_examen', 'valores_resultado.campo_examen_id', '=', 'campos_examen.id')
                    ->where('valores_resultado.detalle_solicitud_id', $solicitud->detalle_id)
                    ->select(
                        'valores_resultado.valor',
                        'valores_resultado.fuera_rango',
                        'campos_examen.nombre as campo',
                        'campos_examen.unidad',
                        'campos_examen.valor_referencia',
                        'campos_examen.tipo',
                        'valores_resultado.created_at',
                        'valores_resultado.updated_at'
                    )
                    ->orderBy('campos_examen.orden')
                    ->orderBy('campos_examen.id')
                    ->get();

                // Agregar el examen con sus resultados a la solicitud
                $solicitudesAgrupadas[$solicitudId]->examenes[] = (object)[
                    'detalle_id' => $solicitud->detalle_id,
                    'nombre' => $solicitud->examen,
                    'codigo' => $solicitud->codigo_examen,
                    'estado' => $solicitud->estado,
                    'resultado' => $solicitud->resultado, // Campo resultado directo de detallesolicitud
                    'observaciones' => $solicitud->observaciones_detalle,
                    'resultados' => $resultados,
                    'total_resultados' => count($resultados),
                    'tiene_resultados' => count($resultados) > 0 || !empty($solicitud->resultado),
                    'resultados_fuera_rango' => $resultados->where('fuera_rango', 1)->count()
                ];
            }
            
            // Convertir el array asociativo a array indexado
            $patient->solicitudes_detalle = array_values($solicitudesAgrupadas);
            
            // Agregar estadísticas resumidas del paciente
            $patient->total_examenes_con_resultados = 0;
            $patient->total_resultados_fuera_rango = 0;
            $patient->examenes_completados = 0;
            $patient->examenes_pendientes = 0;
            
            foreach ($patient->solicitudes_detalle as $solicitud) {
                foreach ($solicitud->examenes as $examen) {
                    if ($examen->tiene_resultados) {
                        $patient->total_examenes_con_resultados++;
                        $patient->total_resultados_fuera_rango += $examen->resultados_fuera_rango;
                    }
                    
                    if ($examen->estado === 'completado') {
                        $patient->examenes_completados++;
                    } elseif ($examen->estado === 'pendiente') {
                        $patient->examenes_pendientes++;
                    }
                }
            }
        }

      

        return $patients;

        
    }

    /**
     * Obtener datos para reporte de exámenes
     */
    private function getExamsReportData($start, $end, $examIds = null, $filterByExams = false)
    {
        // Obtener datos básicos de exámenes
        $query = DB::table('examenes')
            ->leftJoin('categorias', 'examenes.categoria_id', '=', 'categorias.id')
            ->join('detallesolicitud', 'examenes.id', '=', 'detallesolicitud.examen_id')
            ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id');
            
        // Si hay filtro por exámenes específicos, aplicarlo
        if ($filterByExams && $examIds && is_array($examIds) && count($examIds) > 0) {
            $query->whereIn('examenes.id', $examIds);
        }
            
        $exams = $query->select(
                'examenes.id',
                'examenes.nombre',
                'examenes.codigo',
                'categorias.nombre as categoria', // Usar el nombre de la categoría, no un campo inexistente
                'examenes.created_at',
                'examenes.updated_at',
                DB::raw('COUNT(detallesolicitud.id) as total_realizados'),
                DB::raw('COUNT(DISTINCT solicitudes.paciente_id) as total_pacientes'),
                DB::raw('SUM(CASE WHEN detallesolicitud.estado = "pendiente" THEN 1 ELSE 0 END) as pendientes'),
                DB::raw('SUM(CASE WHEN detallesolicitud.estado = "en_proceso" THEN 1 ELSE 0 END) as en_proceso'),
                DB::raw('SUM(CASE WHEN detallesolicitud.estado = "completado" THEN 1 ELSE 0 END) as completados')
            )
            ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->groupBy('examenes.id', 'examenes.nombre', 'examenes.codigo', 'categorias.nombre','examenes.created_at', 'examenes.updated_at')
            ->orderBy('total_realizados', 'desc')
            ->get();

        // Para cada examen, obtener campos si están definidos
        foreach ($exams as &$exam) {
            // Obtener los campos definidos para este examen
            $exam->campos = DB::table('campos_examen')
                ->where('examen_id', $exam->id)
                ->select('nombre', 'tipo', 'unidad', 'valor_referencia', 'seccion', 'activo')
                ->orderBy('orden')
                ->get();
                
            // Obtener servicios donde más se solicita este examen
            $exam->servicios_principales = DB::table('solicitudes')
                ->join('servicios', 'solicitudes.servicio_id', '=', 'servicios.id')
                ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
                ->where('detallesolicitud.examen_id', $exam->id)
                ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->select('servicios.nombre', DB::raw('COUNT(*) as total'))
                ->groupBy('servicios.nombre')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get();
        }
        
        return $exams->toArray();
    }

    /**
     * Obtener datos para reporte de médicos
     */
    private function getDoctorsReportData($start, $end, $doctorsIds = null, $filterByDoctors = false)
    {
        \Log::info('Obteniendo datos de médicos para reporte', [
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
            'doctorsIds' => $doctorsIds,
            'filterByDoctors' => $filterByDoctors
        ]);

        try {
            // Primero, vamos a debuggear qué usuarios existen
            $allUsers = DB::table('users')->select('id', 'nombre', 'apellido', 'role', 'activo')->get();
            \Log::info('🔍 DEBUG: Todos los usuarios en la DB', [
                'total' => $allUsers->count(),
                'usuarios' => $allUsers->map(function($u) {
                    return [
                        'id' => $u->id,
                        'nombre' => $u->nombre . ' ' . $u->apellido,
                        'role' => $u->role,
                        'activo' => $u->activo
                    ];
                })->toArray()
            ]);

            // Decidir si mostrar todos los doctores o solo los que tuvieron actividad
            if ($filterByDoctors && $doctorsIds) {
                // Caso 1: Filtro específico de doctores - usar LEFT JOIN para incluir doctores sin actividad
                $query = DB::table('users')
                    ->leftJoin('solicitudes', function($join) use ($start, $end) {
                        $join->on('users.id', '=', 'solicitudes.user_id')
                             ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
                    })
                    ->select(
                        'users.id',
                        'users.nombre as nombres',
                        'users.apellido as apellidos',
                        'users.especialidad',
                        'users.colegiatura as cmp',
                        'users.email',
                        'users.role',
                        'users.activo as estado',
                        'users.created_at',
                        'users.ultimo_acceso as ultima_actividad',
                        DB::raw('COUNT(DISTINCT solicitudes.id) as total_solicitudes')
                    )
                    ->whereIn('users.role', ['doctor', 'laboratorio']);
            } else {
                // Caso 2: Sin filtro específico - usar INNER JOIN para mostrar solo doctores con actividad
                $query = DB::table('users')
                    ->join('solicitudes', 'users.id', '=', 'solicitudes.user_id')
                    ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->select(
                        'users.id',
                        'users.nombre as nombres',
                        'users.apellido as apellidos',
                        'users.especialidad',
                        'users.colegiatura as cmp',
                        'users.email',
                        'users.role',
                        'users.activo as estado',
                        'users.created_at',
                        'users.ultimo_acceso as ultima_actividad',
                        DB::raw('COUNT(DISTINCT solicitudes.id) as total_solicitudes')
                    )
                    ->whereIn('users.role', ['doctor', 'laboratorio']);

                \Log::info('🎯 Usando INNER JOIN - Solo doctores con actividad en el período', [
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => $end->format('Y-m-d')
                ]);
            }

            // Debug: también vemos cuántos usuarios cumplen los criterios antes del JOIN
            $doctorsAndLabUsers = DB::table('users')
                ->whereIn('role', ['doctor', 'laboratorio'])
                ->select('id', 'nombre', 'apellido', 'role', 'activo')
                ->get();

            \Log::info('🔍 DEBUG: Usuarios doctor/laboratorio (todos)', [
                'count' => $doctorsAndLabUsers->count(),
                'usuarios' => $doctorsAndLabUsers->map(function($u) {
                    return [
                        'id' => $u->id,
                        'nombre' => $u->nombre . ' ' . $u->apellido,
                        'role' => $u->role,
                        'activo' => $u->activo
                    ];
                })->toArray()
            ]);

            // Debug: también vamos a ver qué solicitudes hay en el rango de fechas
            $solicitudesInRange = DB::table('solicitudes')
                ->whereBetween('fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->select('id', 'user_id', 'fecha', 'paciente_id')
                ->get();
            
            \Log::info('🔍 DEBUG: Solicitudes en el rango de fechas', [
                'count' => $solicitudesInRange->count(),
                'rango' => [$start->format('Y-m-d'), $end->format('Y-m-d')],
                'solicitudes' => $solicitudesInRange->take(10)->map(function($s) {
                    return [
                        'id' => $s->id,
                        'user_id' => $s->user_id,
                        'fecha' => $s->fecha,
                        'paciente_id' => $s->paciente_id
                    ];
                })->toArray()
            ]);
                
            // Filtrar por doctores específicos si se solicita
            if ($filterByDoctors && $doctorsIds) {
                // Asegurarse de que $doctorsIds sea un array
                if (!is_array($doctorsIds)) {
                    $doctorsIds = explode(',', $doctorsIds);
                }
                $query->whereIn('users.id', $doctorsIds);
                \Log::info('Filtrando por doctores específicos', ['ids' => $doctorsIds]);
            }
            
            $doctors = $query->groupBy('users.id', 'users.nombre', 'users.apellido', 'users.role', 'users.especialidad', 'users.colegiatura', 'users.email', 'users.activo', 'users.created_at', 'users.ultimo_acceso')
                ->orderBy('total_solicitudes', 'desc')
                ->get();

            \Log::info('🔍 DEBUG: Resultado de la query de doctores', [
                'count' => $doctors->count(),
                'doctores_raw' => $doctors->map(function($d) {
                    return [
                        'id' => $d->id,
                        'nombre' => $d->nombres . ' ' . $d->apellidos,
                        'role' => $d->role,
                        'estado' => $d->estado,
                        'total_solicitudes' => $d->total_solicitudes
                    ];
                })->toArray()
            ]);

            // Transformar los datos para el formato esperado y calcular estadísticas adicionales
            $transformedDoctors = $doctors->map(function($doctor) use ($start, $end) {
                // Calcular pacientes únicos atendidos por este doctor
                $totalPacientes = DB::table('solicitudes')
                    ->where('user_id', $doctor->id)
                    ->whereBetween('fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->distinct('paciente_id')
                    ->count('paciente_id');

                // Calcular total de exámenes realizados por este doctor
                $totalExamenes = DB::table('solicitudes')
                    ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
                    ->where('solicitudes.user_id', $doctor->id)
                    ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->count();

                return (object)[
                    'id' => $doctor->id,
                    'nombres' => $doctor->nombres,
                    'apellidos' => $doctor->apellidos,
                    'especialidad' => $doctor->especialidad ?? ($doctor->role === 'laboratorio' ? 'Laboratorio Clínico' : 'No especificada'),
                    'cmp' => $doctor->cmp ?? 'No registrado',
                    'telefono' => 'No registrado', // No disponible en users
                    'email' => $doctor->email ?? 'No registrado',
                    'estado' => $doctor->estado ? 'Activo' : 'Inactivo',
                    'total_solicitudes' => $doctor->total_solicitudes,
                    'total_pacientes' => $totalPacientes,
                    'total_examenes' => $totalExamenes,
                    'ultima_actividad' => $doctor->ultima_actividad,
                    'created_at' => $doctor->created_at,
                    'role_sistema' => ucfirst($doctor->role)
                ];
            });
            
            // Contar totales para mantener compatibilidad con otros reportes
            $totalSolicitudes = $doctors->sum('total_solicitudes');
            $totalDoctors = $doctors->count();
            
            // Contar pacientes únicos atendidos por estos doctores en el período
            $totalPatients = DB::table('solicitudes')
                ->join('users', 'solicitudes.user_id', '=', 'users.id')
                ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->whereIn('users.role', ['doctor', 'laboratorio']);
                
            // Aplicar filtro por doctores específicos si es necesario    
            if ($filterByDoctors && $doctorsIds) {
                $totalPatients = $totalPatients->whereIn('users.id', $doctorsIds);
            }
            
            $totalPatients = $totalPatients->distinct('paciente_id')->count('paciente_id');

            \Log::info('Médicos obtenidos para reporte', [
                'count' => $transformedDoctors->count(),
                'totalSolicitudes' => $totalSolicitudes,
                'totalPatients' => $totalPatients
            ]);

            // Devolver un array con estructura similar a los otros reportes para compatibilidad
            // Asegurarnos de que transformedDoctors sea un array y no una Collection
            return [
                'doctorStats' => $transformedDoctors->toArray(),
                'totalDoctors' => $totalDoctors,
                'totalRequests' => $totalSolicitudes,
                'totalPatients' => $totalPatients,
                'totalExams' => $totalSolicitudes, // Considerando cada solicitud como un examen
                'pendingCount' => 0, // Estos valores se pueden calcular si son necesarios
                'inProcessCount' => 0,
                'completedCount' => 0
            ];

        } catch (\Exception $e) {
            \Log::error('Error al obtener datos de médicos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]); // Devolver colección vacía en caso de error
        }
    }

    /**
     * Obtener datos para reporte de servicios
     */
    private function getServicesReportData($start, $end, $serviceIds = null, $filterByServices = false)
    {
        \Log::info('getServicesReportData llamado con filtros', [
            'serviceIds' => $serviceIds,
            'filterByServices' => $filterByServices,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ]);
        
        try {
            // Obtener TODOS los servicios (activos e inactivos) con información del padre
            $query = DB::table('servicios as s')
                ->leftJoin('servicios as parent', 's.parent_id', '=', 'parent.id')
                ->leftJoin('solicitudes', function($join) use ($start, $end) {
                    $join->on('s.id', '=', 'solicitudes.servicio_id')
                         ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
                })
                ->select(
                    's.id',
                    's.nombre',
                    's.parent_id',
                    's.activo',
                    's.created_at',
                    's.updated_at',
                    'parent.nombre as parent_nombre',
                    DB::raw('COUNT(solicitudes.id) as total_solicitudes'),
                    DB::raw('COUNT(DISTINCT solicitudes.paciente_id) as total_pacientes_unicos')
                )
                ->groupBy('s.id', 's.nombre', 's.parent_id', 's.activo', 's.created_at', 's.updated_at', 'parent.nombre');

            // Aplicar filtro de servicios si es necesario
            if ($filterByServices && $serviceIds && is_array($serviceIds) && count($serviceIds) > 0) {
                $query->whereIn('s.id', $serviceIds);
                \Log::info('Aplicando filtro por servicios específicos', [
                    'count' => count($serviceIds),
                    'ids' => implode(',', $serviceIds)
                ]);
            }

            $services = $query->orderBy('s.activo', 'desc')->orderBy('total_solicitudes', 'desc')->get();

            \Log::info('Servicios obtenidos', [
                'count' => $services->count(),
                'services' => $services->pluck('nombre', 'id')->toArray()
            ]);

            // Para cada servicio, agregar información adicional
            foreach ($services as $service) {
                // Crear nombre completo con padre si es subservicio
                if ($service->parent_id && $service->parent_nombre) {
                    $service->nombre_completo = $service->parent_nombre . ' - ' . $service->nombre;
                } else {
                    $service->nombre_completo = $service->nombre;
                }

                // Calcular exámenes totales para este servicio
                $service->total_examenes = DB::table('solicitudes')
                    ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
                    ->where('solicitudes.servicio_id', $service->id)
                    ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->count();

                // Estados de exámenes
                $service->completados = DB::table('solicitudes')
                    ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
                    ->where('solicitudes.servicio_id', $service->id)
                    ->where('detallesolicitud.estado', 'completado')
                    ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->count();

                $service->pendientes = $service->total_examenes - $service->completados;

                // Agregar campos adicionales esperados por la hoja de exportación
                $service->categoria = 'Laboratorio'; // Valor por defecto
                $service->precio = 0; // Valor por defecto
                // El campo 'activo' ya viene de la base de datos
                $service->descripcion = 'Servicio de ' . $service->nombre_completo;
                $service->duracion_estimada = 'Variable';
                $service->total_realizados = $service->total_solicitudes;

                // Campos adicionales para la vista de servicios
                $service->pacientes_unicos = $service->total_pacientes_unicos;
                $service->total_solicitudes = $service->total_solicitudes;
            }

            return $services;
        
        } catch (\Exception $e) {
            \Log::error('Error en getServicesReportData: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]); // Devolver colección vacía en caso de error

        }
       
    }

    /**
     * Generar reporte personal para un doctor específico
     */
    private function getDoctorPersonalReport($startDate, $endDate, $doctorId)
    {
        \Log::info('getDoctorPersonalReport called', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'doctorId' => $doctorId
        ]);

        // Obtener información del doctor
        $doctor = DB::table('users')->where('id', $doctorId)->first();
        
        if (!$doctor) {
            \Log::warning('Doctor no encontrado', ['doctorId' => $doctorId]);
            return [
                'error' => 'Doctor no encontrado',
                'totalRequests' => 0,
                'totalPatients' => 0,
                'totalExams' => 0,
                'pendingCount' => 0,
                'inProcessCount' => 0,
                'completedCount' => 0,
                'doctorInfo' => null,
                'solicitudes' => [],
                'examStats' => [],
                'dailyStats' => []
            ];
        }

        // Estadísticas básicas del doctor
        $totalRequests = Solicitud::where('user_id', $doctorId)
            ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->count();

        $totalPatients = Solicitud::where('user_id', $doctorId)
            ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->distinct('paciente_id')
            ->count('paciente_id');

        // Contar exámenes por estado
        $statusCounts = DetalleSolicitud::whereHas('solicitud', function ($query) use ($startDate, $endDate, $doctorId) {
                $query->where('user_id', $doctorId)
                      ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->select('estado', DB::raw('count(*) as count'))
            ->groupBy('estado')
            ->get()
            ->pluck('count', 'estado')
            ->toArray();

        // Obtener solicitudes del doctor
        $solicitudes = Solicitud::with(['paciente', 'servicio', 'detalles.examen'])
            ->where('user_id', $doctorId)
            ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('fecha', 'desc')
            ->limit(100)
            ->get();

        // Exámenes más solicitados por el doctor
        $examStats = DB::table('detallesolicitud')
            ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
            ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
            ->where('solicitudes.user_id', $doctorId)
            ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                'examenes.id',
                'examenes.nombre as name',
                'examenes.codigo',
                DB::raw('count(*) as count')
            )
            ->groupBy('examenes.id', 'examenes.nombre', 'examenes.codigo')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Estadísticas por día
        $dailyStats = DB::table('solicitudes')
            ->where('user_id', $doctorId)
            ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                'fecha as date',
                DB::raw('count(*) as count'),
                DB::raw('count(distinct paciente_id) as patientCount')
            )
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        return [
            'totalRequests' => $totalRequests,
            'totalPatients' => $totalPatients,
            'totalExams' => $totalRequests, // Para este reporte, cada solicitud es un examen
            'pendingCount' => $statusCounts['pendiente'] ?? 0,
            'inProcessCount' => $statusCounts['en_proceso'] ?? 0,
            'completedCount' => $statusCounts['completado'] ?? 0,
            'doctorInfo' => [
                'id' => $doctor->id,
                'nombre' => $doctor->nombre,
                'apellido' => $doctor->apellido,
                'email' => $doctor->email,
                'full_name' => $doctor->nombre . ' ' . $doctor->apellido
            ],
            'solicitudes' => $solicitudes,
            'examStats' => $examStats,
            'dailyStats' => $dailyStats
        ];
    }

    /**
     * Obtener datos para gráficos
     */
    public function getChartData(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'doctor_id' => 'nullable|integer'
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        \Log::info('getChartData called with:', [
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'doctor_id' => $request->doctor_id,
            'parsed_start' => $startDate->format('Y-m-d H:i:s'),
            'parsed_end' => $endDate->format('Y-m-d H:i:s')
        ]);

        try {
            // Usar los mismos métodos que ya funcionan en getReports
            switch ($request->type) {
                case 'general':
                    $chartData = $this->getGeneralReport($startDate, $endDate);
                    break;
                case 'exams':
                    $chartData = $this->getExamsReport($startDate, $endDate);
                    break;
                case 'services':
                    $chartData = $this->getServicesReport($startDate, $endDate);
                    break;
                case 'doctors':
                    $chartData = $this->getDoctorsReportData($startDate, $endDate);
                    break;
                case 'patients':
                    $chartData = $this->getPatientsReport($startDate, $endDate);
                    break;
                case 'doctor_personal':
                    $doctorId = $request->doctor_id ?: $request->user()->id;
                    $chartData = $this->getDoctorPersonalReport($startDate, $endDate, $doctorId);
                    break;
                default:
                    $chartData = $this->getGeneralReport($startDate, $endDate);
                    break;
            }

            \Log::info('📊 getChartData ENVIANDO DATOS:', [
                'type' => $request->type,
                'chartData_keys' => array_keys($chartData),
                'has_examStats' => isset($chartData['examStats']),
                'has_serviceStats' => isset($chartData['serviceStats']),
                'has_dailyStats' => isset($chartData['dailyStats']),
                'pendingCount' => $chartData['pendingCount'] ?? 'N/A',
                'examStats_count' => isset($chartData['examStats']) ? count($chartData['examStats']) : 'N/A'
            ]);

            return response()->json([
                'status' => true,
                'data' => $chartData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error obteniendo datos de gráficos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles completos de los resultados para exportación
     */
    private function getDetailedResultsData($startDate, $endDate, $status = null)
    {
        \Log::info('Obteniendo datos detallados de resultados', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'status' => $status
        ]);

        try {
            // Consulta principal desde detallesolicitud para incluir todos los exámenes
            $query = \DB::table('detallesolicitud')
                ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                ->join('pacientes', 'solicitudes.paciente_id', '=', 'pacientes.id')
                ->leftJoin('users', 'solicitudes.user_id', '=', 'users.id')
                ->leftJoin('valores_resultado', 'detallesolicitud.id', '=', 'valores_resultado.detalle_solicitud_id')
                ->leftJoin('campos_examen', 'valores_resultado.campo_examen_id', '=', 'campos_examen.id')
                ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            // Aplicar filtro por estado si es necesario
            if ($status) {
                $query->where('detallesolicitud.estado', $status);
            }

            // Seleccionar los campos disponibles
            $results = $query->select(
                'detallesolicitud.id',
                'solicitudes.fecha as fecha_resultado',
                'solicitudes.hora',
                'solicitudes.id as solicitud_id',
                \DB::raw("CONCAT(pacientes.nombres, ' ', pacientes.apellidos) as nombre_paciente"),
                'pacientes.dni as dni_paciente',
                'examenes.nombre as nombre_examen',
                'examenes.codigo as codigo_examen',
                \DB::raw("COALESCE(campos_examen.nombre, 'Resultado general') as nombre_campo"),
                \DB::raw("COALESCE(valores_resultado.valor, detallesolicitud.resultado, 'Pendiente') as valor"),
                \DB::raw("COALESCE(campos_examen.unidad, '') as unidad"),
                \DB::raw("COALESCE(campos_examen.valor_referencia, 'Sin referencia') as valor_referencia"),
                'detallesolicitud.estado',
                'detallesolicitud.observaciones as observaciones_detalle',
                \DB::raw("COALESCE(valores_resultado.observaciones, '') as observaciones_valor"),
                \DB::raw("COALESCE(valores_resultado.fuera_rango, 0) as fuera_rango"),
                \DB::raw("CONCAT(COALESCE(users.nombre, 'Sin'), ' ', COALESCE(users.apellido, 'asignar')) as medico_nombre")
            )
            ->orderBy('solicitudes.fecha', 'desc')
            ->orderBy('solicitudes.hora', 'desc')
            ->orderBy('examenes.nombre')
            ->orderBy('campos_examen.orden')
            ->get();

            // Si no hay datos en valores_resultado, obtener de detallesolicitud directamente
            if ($results->count() === 0) {
                \Log::info('No hay datos en valores_resultado, obteniendo de detallesolicitud');
                
                $query = \DB::table('detallesolicitud')
                    ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                    ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                    ->join('pacientes', 'solicitudes.paciente_id', '=', 'pacientes.id')
                    ->leftJoin('users', 'solicitudes.user_id', '=', 'users.id')
                    ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

                if ($status) {
                    $query->where('detallesolicitud.estado', $status);
                }

                $results = $query->select(
                    'detallesolicitud.id',
                    'solicitudes.fecha as fecha_resultado',
                    'solicitudes.hora',
                    'solicitudes.id as solicitud_id',
                    \DB::raw("CONCAT(pacientes.nombres, ' ', pacientes.apellidos) as nombre_paciente"),
                    'pacientes.dni as dni_paciente',
                    'examenes.nombre as nombre_examen',
                    'examenes.id as codigo_examen',
                    \DB::raw("'Resultado general' as nombre_campo"),
                    'detallesolicitud.resultado as valor',
                    \DB::raw("'' as unidad"),
                    \DB::raw("'' as valor_referencia"),
                    'detallesolicitud.estado',
                    'detallesolicitud.observaciones as observaciones_detalle',
                    \DB::raw("'' as observaciones_valor"),
                    \DB::raw("0 as fuera_rango"),
                    \DB::raw("CONCAT(COALESCE(users.nombre, 'Sin'), ' ', COALESCE(users.apellido, 'asignar')) as medico_nombre")
                )
                ->orderBy('solicitudes.fecha', 'desc')
                ->orderBy('solicitudes.hora', 'desc')
                ->orderBy('examenes.nombre')
                ->get();
            }

            // Transformar los datos para que coincidan con lo esperado por ResultsOverviewSheet
            $transformedResults = collect();
            
            foreach ($results as $item) {
                // Asegurar que todos los campos existan y sean objetos
                $transformedItem = (object)[
                    'id' => $item->id ?? '',
                    'paciente' => (object)[
                        'nombres' => explode(' ', $item->nombre_paciente ?? '')[0] ?? '',
                        'apellidos' => implode(' ', array_slice(explode(' ', $item->nombre_paciente ?? ''), 1)) ?: '',
                        'dni' => $item->dni_paciente ?? ''

                    ],
                    'examen' => (object)[
                        'nombre' => ($item->nombre_examen ?? '') . ($item->nombre_campo && $item->nombre_campo !== 'Resultado general' ? ' - ' . $item->nombre_campo : ''),
                        'codigo' => $item->codigo_examen ?? ''

                    ],
                    'solicitud_id' => $item->solicitud_id ?? '',
                    'codigo_examen' => $item->codigo_examen ?? '',
                    'valor' => $item->valor ?? '',
                    'unidad' => $item->unidad ?? '',
                    'valor_referencia' => $item->valor_referencia ?? '',
                    'campo_nombre' => $item->nombre_campo ?? '',
                    'estado' => $this->determineResultStatusFromData($item),
                    'observaciones' => ($item->observaciones_detalle ?? '') . ($item->observaciones_valor ? ' | ' . $item->observaciones_valor : ''),
                    'fecha_resultado' => ($item->fecha_resultado ?? '') . ' ' . ($item->hora ?? '00:00:00'),
                    'fuera_rango' => isset($item->fuera_rango) ? (bool)$item->fuera_rango : false,
                    'medico' => (object)[
                        'nombres' => explode(' ', $item->medico_nombre ?? 'Sin asignar')[0] ?? 'Sin',
                        'apellidos' => implode(' ', array_slice(explode(' ', $item->medico_nombre ?? 'Sin asignar'), 1)) ?: 'asignar'
                    ],
                    'verificado' => strtolower($item->estado ?? '') === 'completado'
                ];
                
                $transformedResults->push($transformedItem);
            }

            \Log::info('Resultados detallados obtenidos y transformados', [
                'count' => $transformedResults->count(),
                'first_record_type' => $transformedResults->first() ? gettype($transformedResults->first()) : 'No data',
                'sample_verificado_type' => $transformedResults->first() ? gettype($transformedResults->first()->verificado) : 'N/A'
            ]);

            return $transformedResults;

        } catch (\Exception $e) {
            \Log::error('Error al obtener datos detallados de resultados: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]); // Devolver colección vacía en caso de error
        }
    }

    /**
     * Determinar el estado del resultado basado en los datos disponibles
     */
    private function determineResultStatusFromData($item)
    {
        $estado = strtolower($item->estado ?? '');
        switch ($estado) {
            case 'completado':
                return 'Normal';
            case 'pendiente':
                return 'Pendiente';
            case 'en_proceso':
                return 'En proceso';
            default:
                return 'Sin estado';
        }
    }

    /**
     * Obtener datos de solicitudes para reportes
     */
    private function getSolicitudesReportData($start, $end)
    {
        \Log::info('Obteniendo datos de solicitudes para reporte', [
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d')
        ]);

        try {
            $solicitudes = DB::table('solicitudes')
                ->join('pacientes', 'solicitudes.paciente_id', '=', 'pacientes.id')
                ->leftJoin('servicios', 'solicitudes.servicio_id', '=', 'servicios.id')
                ->leftJoin('users', 'solicitudes.user_id', '=', 'users.id')
                ->select(
                    'solicitudes.id',
                    'solicitudes.numero_recibo as numero_solicitud',
                    'solicitudes.fecha as fecha_solicitud',
                    'solicitudes.hora',
                    'solicitudes.estado',
                    DB::raw("CONCAT(pacientes.nombres, ' ', pacientes.apellidos) as paciente_nombre"),
                    'pacientes.dni as documento_paciente',
                    DB::raw("CONCAT(COALESCE(users.nombre, 'Sin'), ' ', COALESCE(users.apellido, 'asignar')) as medico_solicitante"),
                    'servicios.nombre as servicio',
                    // Contar exámenes totales y completados
                    DB::raw('(SELECT COUNT(*) FROM detallesolicitud WHERE detallesolicitud.solicitud_id = solicitudes.id) as total_examenes'),
                    DB::raw('(SELECT COUNT(*) FROM detallesolicitud WHERE detallesolicitud.solicitud_id = solicitudes.id AND detallesolicitud.estado = "completado") as examenes_completados')
                )
                ->whereBetween('solicitudes.fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->orderBy('solicitudes.fecha', 'desc')
                ->orderBy('solicitudes.hora', 'desc')
                ->get();

            \Log::info('Solicitudes obtenidas para reporte', [
                'count' => $solicitudes->count()
            ]);

            return $solicitudes;

        } catch (\Exception $e) {
            \Log::error('Error al obtener datos de solicitudes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]); // Devolver colección vacía en caso de error
        }
    }

    /**
     * Calcular estadísticas avanzadas basadas en los datos reales
     */
    private function calculateAdvancedStats($data, $start, $end)
    {
        \Log::info('Calculando estadísticas avanzadas');

        // Datos de pacientes
        $patients = $data['patients'] ?? [];
        $patientCount = count($patients);
        
        // Calcular pacientes nuevos (registrados en este período)
        $newPatients = 0;
        $malePatients = 0;
        $femalePatients = 0;
        $totalAge = 0;
        $patientsWithAge = 0;
        
        foreach ($patients as $patient) {
            // Verificar si es paciente nuevo (primera solicitud en este período)
            if (isset($patient->primera_visita) && 
                Carbon::parse($patient->primera_visita)->between($start, $end)) {
                $newPatients++;
            }
            
            // Contar por género
            if (isset($patient->sexo)) {
                if (strtolower($patient->sexo) === 'm' || strtolower($patient->sexo) === 'masculino') {
                    $malePatients++;
                } elseif (strtolower($patient->sexo) === 'f' || strtolower($patient->sexo) === 'femenino') {
                    $femalePatients++;
                }
            }
            
            // Calcular edad promedio
            if (isset($patient->edad) && is_numeric($patient->edad)) {
                $totalAge += $patient->edad;
                $patientsWithAge++;
            }
        }
        
        $averageAge = $patientsWithAge > 0 ? $totalAge / $patientsWithAge : 0;

        // Datos de solicitudes
        $solicitudes = $data['solicitudes'] ?? [];
        $requestCount = count($solicitudes);
        
        $completedRequests = 0;
        $totalExamsInRequests = 0;
        
        foreach ($solicitudes as $solicitud) {
            if (isset($solicitud->estado) && strtolower($solicitud->estado) === 'completado') {
                $completedRequests++;
            }
            if (isset($solicitud->total_examenes)) {
                $totalExamsInRequests += $solicitud->total_examenes;
            }
        }
        
        $averageExamsPerRequest = $requestCount > 0 ? $totalExamsInRequests / $requestCount : 0;

        // Datos de exámenes
        $examenes = $data['examenes'] ?? [];
        $examCount = 0;
        $mostRequestedExam = 'N/A';
        $mostPopularCategory = 'N/A';
        
        if (count($examenes) > 0) {
            $examCount = array_sum(array_column($examenes, 'total_realizados'));
            
            // Encontrar el examen más solicitado
            $maxCount = 0;
            foreach ($examenes as $examen) {
                if (isset($examen->total_realizados) && $examen->total_realizados > $maxCount) {
                    $maxCount = $examen->total_realizados;
                    $mostRequestedExam = $examen->nombre ?? 'N/A';
                }
            }
            
            // Encontrar la categoría más popular
            $categorias = [];
            foreach ($examenes as $examen) {
                $categoria = $examen->categoria ?? 'Sin categoría';
                if (!isset($categorias[$categoria])) {
                    $categorias[$categoria] = 0;
                }
                $categorias[$categoria] += $examen->total_realizados ?? 0;
            }
            
            if (!empty($categorias)) {
                $mostPopularCategory = array_keys($categorias, max($categorias))[0];
            }
        }

        // Datos de resultados
        $resultados = $data['resultados'] ?? collect([]);
        $normalResults = 0;
        $abnormalResults = 0;
        
        foreach ($resultados as $resultado) {
            if (isset($resultado->fuera_rango) && $resultado->fuera_rango) {
                $abnormalResults++;
            } else {
                $normalResults++;
            }
        }

        // Agregar las estadísticas calculadas al array de datos
        $data['total_patients'] = $patientCount;
        $data['new_patients'] = $newPatients;
        $data['male_patients'] = $malePatients;
        $data['female_patients'] = $femalePatients;
        $data['average_age'] = $averageAge;
        
        $data['total_requests'] = $requestCount;
        $data['completed_requests'] = $completedRequests;
        $data['average_exams_per_request'] = $averageExamsPerRequest;
        $data['average_processing_time'] = 'Inmediato'; // Simplificado por ahora
        
        $data['total_exams'] = $examCount;
        $data['most_requested_exam'] = $mostRequestedExam;
        $data['most_popular_category'] = $mostPopularCategory;
        $data['normal_results'] = $normalResults;
        $data['abnormal_results'] = $abnormalResults;

        \Log::info('Estadísticas avanzadas calculadas', [
            'total_patients' => $patientCount,
            'new_patients' => $newPatients,
            'total_requests' => $requestCount,
            'completed_requests' => $completedRequests,
            'total_exams' => $examCount,
            'most_requested_exam' => $mostRequestedExam,
            'normal_results' => $normalResults,
            'abnormal_results' => $abnormalResults
        ]);

        return $data;
    }

    private function getExamsDetailedReport($startDate, $endDate, $examIds = null, $filterByExams = false)
    {
        \Log::info('getExamsDetailedReport called', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'examIds' => $examIds,
            'filterByExams' => $filterByExams
        ]);

        $data = [];

        try {
            // Construir la consulta base para estadísticas de exámenes con categorías
            // CORREGIDO: Contar exámenes solicitados, no campos individuales
            $examStatsQuery = DB::table('detallesolicitud')
                ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                ->leftJoin('categorias', 'examenes.categoria_id', '=', 'categorias.id')
                ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            // Aplicar filtro de exámenes si se especifica
            if ($filterByExams && !empty($examIds)) {
                $examStatsQuery->whereIn('examenes.id', $examIds);
                \Log::info('Aplicando filtro de exámenes en getExamsDetailedReport', ['examIds' => $examIds]);
            }

            $examStats = $examStatsQuery
                ->select(
                    'examenes.id',
                    'examenes.codigo',
                    'examenes.nombre as name',
                    'categorias.nombre as categoria',
                    DB::raw('count(DISTINCT solicitudes.id) as count')
                )
                ->groupBy('examenes.id', 'examenes.codigo', 'examenes.nombre', 'categorias.nombre')
                ->orderByDesc('count')
                ->get();

            \Log::info("Datos brutos de examStats antes de agrupación:", [
                'total_registros' => $examStats->count(),
                'registros' => $examStats->map(function($exam) {
                    return [
                        'id' => $exam->id,
                        'name' => $exam->name,
                        'count' => $exam->count,
                        'categoria' => $exam->categoria
                    ];
                })->toArray()
            ]);

            // Agrupar por ID de examen para evitar duplicados (más confiable que el nombre)
            $groupedExamStats = [];
            foreach ($examStats as $stat) {
                $key = $stat->id; // Usar el ID como clave única (más confiable)

                if (isset($groupedExamStats[$key])) {
                    // Si ya existe, sumar el count
                    $groupedExamStats[$key]->count += $stat->count;
                    \Log::info("Agrupando examen duplicado: {$stat->name} (ID: {$stat->id}), count anterior: {$groupedExamStats[$key]->count}, sumando: {$stat->count}");
                } else {
                    // Si no existe, crear nuevo registro
                    $groupedExamStats[$key] = (object)[
                        'id' => $stat->id,
                        'codigo' => $stat->codigo,
                        'name' => $stat->name,
                        'categoria' => $stat->categoria,
                        'count' => $stat->count
                    ];
                    \Log::info("Nuevo examen en agrupación: {$stat->name} (ID: {$stat->id}), count: {$stat->count}");
                }
            }

            // Convertir de vuelta a array indexado y ordenar por count
            $examStats = collect(array_values($groupedExamStats))->sortByDesc('count');

            \Log::info("Exámenes después de agrupación:", [
                'total_examenes' => $examStats->count(),
                'examenes' => $examStats->map(function($exam) {
                    return ['name' => $exam->name, 'count' => $exam->count];
                })->toArray()
            ]);

            // Calcular porcentajes
            $totalExams = $examStats->sum('count');
            foreach ($examStats as $stat) {
                $stat->percentage = $totalExams > 0 ? round(($stat->count / $totalExams) * 100, 2) : 0;
            }

            $data['examStats'] = $examStats;

            // Agrupar exámenes por categoría
            $examsByCategory = [];
            foreach ($examStats as $exam) {
                $category = $exam->categoria ?? 'Sin categoría';
                if (!isset($examsByCategory[$category])) {
                    $examsByCategory[$category] = [];
                }
                $examsByCategory[$category][] = $exam;
            }
            $data['examsByCategory'] = $examsByCategory;

            // Estadísticas por categoría
            $categoryStatsQuery = DB::table('detallesolicitud')
                ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                ->leftJoin('categorias', 'examenes.categoria_id', '=', 'categorias.id')
                ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            // Aplicar filtro de exámenes si se especifica
            if ($filterByExams && !empty($examIds)) {
                $categoryStatsQuery->whereIn('examenes.id', $examIds);
            }

            $categoryStats = $categoryStatsQuery
                ->select(
                    'categorias.nombre as categoria',
                    DB::raw('count(DISTINCT solicitudes.id) as total_count'),
                    DB::raw('count(distinct examenes.id) as unique_count')
                )
                ->groupBy('categorias.nombre')
                ->orderByDesc('total_count')
                ->get();

            $data['categoryStats'] = $categoryStats;

            // Estadísticas diarias de exámenes
            $dailyExamStatsQuery = DB::table('detallesolicitud')
                ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                ->leftJoin('categorias', 'examenes.categoria_id', '=', 'categorias.id')
                ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            // Aplicar filtro de exámenes si se especifica
            if ($filterByExams && !empty($examIds)) {
                $dailyExamStatsQuery->whereIn('examenes.id', $examIds);
            }

            $dailyExamStats = $dailyExamStatsQuery
                ->select(
                    'solicitudes.fecha as date',
                    DB::raw('count(DISTINCT solicitudes.id) as exam_count'),
                    DB::raw('count(distinct categorias.id) as category_count')
                )
                ->groupBy('solicitudes.fecha')
                ->orderBy('solicitudes.fecha')
                ->get();

            $data['dailyExamStats'] = $dailyExamStats;

            // Calcular totales y métricas
            $data['totalExams'] = $examStats->sum('count');
            $data['uniqueExams'] = $examStats->count();
            $data['totalCategories'] = $categoryStats->count();

            // Examen más solicitado
            $mostRequested = $examStats->first();
            $data['mostRequestedExam'] = $mostRequested ? $mostRequested->name : 'N/A';

            // Categoría con más solicitudes
            $topCategory = $categoryStats->first();
            $data['topCategory'] = $topCategory ? $topCategory->categoria : 'N/A';

            \Log::info('Datos del reporte de exámenes detallado obtenidos', [
                'totalExams' => $data['totalExams'],
                'uniqueExams' => $data['uniqueExams'],
                'totalCategories' => $data['totalCategories'],
                'examStats_count' => count($data['examStats']),
                'categoryStats_count' => count($data['categoryStats']),
                'dailyExamStats_count' => count($data['dailyExamStats'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en getExamsDetailedReport: ' . $e->getMessage());
            $data = [
                'examStats' => [],
                'examsByCategory' => [],
                'categoryStats' => [],
                'dailyExamStats' => [],
                'totalExams' => 0,
                'uniqueExams' => 0,
                'totalCategories' => 0,
                'mostRequestedExam' => 'N/A',
                'topCategory' => 'N/A'
            ];
        }

        return $data;
    }

    /**
     * Generar reporte por resultados
     */
    private function getResultsReport($startDate, $endDate, $status = null)
    {
        \Log::info('getResultsReport called', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'status' => $status
        ]);

        try {
            // Estadísticas por estado de exámenes
            $statusQuery = DetalleSolicitud::whereHas('solicitud', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                });

            // Aplicar filtro de estado si se especifica
            if ($status) {
                $statusQuery->where('estado', $status);
                \Log::info('Aplicando filtro de estado', ['status' => $status]);
            }

            $statusCounts = $statusQuery
                ->select('estado', DB::raw('count(*) as count'))
                ->groupBy('estado')
                ->get()
                ->pluck('count', 'estado')
                ->toArray();

            // Estadísticas diarias de resultados
            $dailyStatsQuery = DB::table('detallesolicitud')
                ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            // Aplicar filtro de estado si se especifica
            if ($status) {
                $dailyStatsQuery->where('detallesolicitud.estado', $status);
            }

            $dailyStats = $dailyStatsQuery
                ->select(
                    'solicitudes.fecha as date',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(CASE WHEN detallesolicitud.estado = "completado" THEN 1 ELSE 0 END) as completed'),
                    DB::raw('SUM(CASE WHEN detallesolicitud.estado = "pendiente" THEN 1 ELSE 0 END) as pending'),
                    DB::raw('SUM(CASE WHEN detallesolicitud.estado = "en_proceso" THEN 1 ELSE 0 END) as in_process')
                )
                ->groupBy('solicitudes.fecha')
                ->orderBy('solicitudes.fecha')
                ->get();

            // Estadísticas de tiempo de procesamiento (simuladas por ahora)
            $processingTimeStats = [
                ['range' => '0-24 horas', 'count' => $statusCounts['completado'] ?? 0],
                ['range' => '1-3 días', 'count' => intval(($statusCounts['completado'] ?? 0) * 0.3)],
                ['range' => '3-7 días', 'count' => intval(($statusCounts['completado'] ?? 0) * 0.1)],
                ['range' => 'Más de 7 días', 'count' => intval(($statusCounts['completado'] ?? 0) * 0.05)]
            ];

            // Totales
            $totalRequests = Solicitud::whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->count();
            $totalPatients = Solicitud::whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->distinct('paciente_id')
                ->count('paciente_id');
            $totalExamsQuery = DetalleSolicitud::whereHas('solicitud', function($q) use ($startDate, $endDate) {
                $q->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            });

            // Aplicar filtro de estado si se especifica
            if ($status) {
                $totalExamsQuery->where('estado', $status);
            }

            $totalExams = $totalExamsQuery->count();

            // Estadísticas de exámenes por resultado
            $examStatsQuery = DB::table('detallesolicitud')
                ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                ->join('pacientes', 'solicitudes.paciente_id', '=', 'pacientes.id')
                ->leftJoin('categorias', 'examenes.categoria_id', '=', 'categorias.id')
                ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            // Aplicar filtro de estado si se especifica
            if ($status) {
                $examStatsQuery->where('detallesolicitud.estado', $status);
            }

            $examStats = $examStatsQuery
                ->select(
                    'examenes.id',
                    'examenes.codigo',
                    'examenes.nombre as name',
                    'categorias.nombre as categoria',
                    DB::raw('COUNT(*) as total_realizados'),
                    DB::raw('COUNT(DISTINCT pacientes.id) as total_pacientes'),
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw('SUM(CASE WHEN detallesolicitud.estado = "completado" THEN 1 ELSE 0 END) as completed_count'),
                    DB::raw('SUM(CASE WHEN detallesolicitud.estado = "pendiente" THEN 1 ELSE 0 END) as pending_count'),
                    DB::raw('SUM(CASE WHEN detallesolicitud.estado = "en_proceso" THEN 1 ELSE 0 END) as in_process_count')
                )
                ->groupBy('examenes.id', 'examenes.codigo', 'examenes.nombre', 'categorias.nombre')
                ->orderBy('total_count', 'desc')
                ->limit(20)
                ->get();

            // Agrupar exámenes por categoría para resultados
            $examsByCategory = [];
            foreach ($examStats as $exam) {
                $category = $exam->categoria ?? 'Sin categoría';
                if (!isset($examsByCategory[$category])) {
                    $examsByCategory[$category] = [];
                }
                $examsByCategory[$category][] = $exam;
            }

            // Estadísticas de categorías para resultados
            $categoryStats = DB::table('detallesolicitud')
                ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
                ->leftJoin('categorias', 'examenes.categoria_id', '=', 'categorias.id')
                ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->select(
                    'categorias.nombre as categoria',
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw('SUM(CASE WHEN detallesolicitud.estado = "completado" THEN 1 ELSE 0 END) as completed_count'),
                    DB::raw('SUM(CASE WHEN detallesolicitud.estado = "pendiente" THEN 1 ELSE 0 END) as pending_count'),
                    DB::raw('SUM(CASE WHEN detallesolicitud.estado = "en_proceso" THEN 1 ELSE 0 END) as in_process_count')
                )
                ->groupBy('categorias.nombre')
                ->orderBy('total_count', 'desc')
                ->get();

            $data = [
                'statusCounts' => $statusCounts,
                'dailyStats' => $dailyStats,
                'processingTimeStats' => $processingTimeStats,
                'examStats' => $examStats,
                'examsByCategory' => $examsByCategory,
                'categoryStats' => $categoryStats,
                'totalRequests' => $totalRequests,
                'totalPatients' => $totalPatients,
                'totalExams' => $totalExams,
                'pendingCount' => $statusCounts['pendiente'] ?? 0,
                'inProcessCount' => $statusCounts['en_proceso'] ?? 0,
                'completedCount' => $statusCounts['completado'] ?? 0,
            ];

            \Log::info('Datos del reporte de resultados obtenidos', [
                'statusCounts' => $statusCounts,
                'dailyStats_count' => count($dailyStats),
                'processingTimeStats_count' => count($processingTimeStats),
                'totals' => [
                    'requests' => $totalRequests,
                    'patients' => $totalPatients,
                    'exams' => $totalExams
                ]
            ]);

            return $data;

        } catch (\Exception $e) {
            \Log::error('Error en getResultsReport: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'statusCounts' => [],
                'dailyStats' => [],
                'processingTimeStats' => [],
                'totalRequests' => 0,
                'totalPatients' => 0,
                'totalExams' => 0,
                'pendingCount' => 0,
                'inProcessCount' => 0,
                'completedCount' => 0,
            ];
        }
    }

    /**
     * Generar reporte por categorías
     */
    private function getCategoriesReport($startDate, $endDate, $status = null)
    {
        \Log::info('getCategoriesReport called', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'status' => $status
        ]);

        try {
            // Estadísticas por categoría
            // Verificar cuántas categorías tenemos en total
            $totalCategorias = DB::table('categorias')->count();
            \Log::info('Total categorías en la base de datos: ' . $totalCategorias);
            
            // Verificar si hay algún dato en el rango de fechas
            $totalSolicitudes = DB::table('solicitudes')
                ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->count();
            \Log::info('Total solicitudes en el rango de fechas: ' . $totalSolicitudes);
            
            // Verificar exámenes y detalles en el rango
            $totalDetalles = DB::table('solicitudes')
                ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
                ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->count();
            \Log::info('Total detalles de solicitud en el rango: ' . $totalDetalles);
            
            // ENFOQUE MODIFICADO: Primero obtenemos todas las categorías, luego contamos los exámenes
            // relacionados con ellas en el rango de fechas (si hay alguno)
            $query = DB::table('categorias')
                ->select(
                    'categorias.id',
                    'categorias.nombre as name',
                    DB::raw('(SELECT COUNT(*) FROM examenes 
                             LEFT JOIN detallesolicitud ON examenes.id = detallesolicitud.examen_id 
                             LEFT JOIN solicitudes ON detallesolicitud.solicitud_id = solicitudes.id 
                             WHERE examenes.categoria_id = categorias.id 
                             AND solicitudes.fecha BETWEEN ? AND ?) as count'),
                    DB::raw('(SELECT COUNT(DISTINCT solicitudes.paciente_id) FROM examenes 
                             LEFT JOIN detallesolicitud ON examenes.id = detallesolicitud.examen_id 
                             LEFT JOIN solicitudes ON detallesolicitud.solicitud_id = solicitudes.id 
                             WHERE examenes.categoria_id = categorias.id 
                             AND solicitudes.fecha BETWEEN ? AND ?) as total_pacientes')
                )
                ->orderBy('name', 'asc')
                ->setBindings([
                    $startDate->format('Y-m-d'), $endDate->format('Y-m-d'),
                    $startDate->format('Y-m-d'), $endDate->format('Y-m-d')
                ]);
                
            \Log::info('SQL de la consulta de categorías: ' . $query->toSql());
            
            $categoryStats = $query->get();
            
            \Log::info('Categorías encontradas: ' . $categoryStats->count(), [
                'categorias' => $categoryStats->map(function($item) {
                    return ['id' => $item->id, 'name' => $item->name, 'count' => $item->count];
                })->toArray()
            ]);

            // Exámenes más solicitados por categoría
            $topExamsByCategory = [];
            foreach ($categoryStats as $category) {
                $topExams = DB::table('examenes')
                    ->join('detallesolicitud', 'examenes.id', '=', 'detallesolicitud.examen_id')
                    ->join('solicitudes', 'detallesolicitud.solicitud_id', '=', 'solicitudes.id')
                    ->where('examenes.categoria_id', $category->id)
                    ->whereBetween('solicitudes.fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->select(
                        'examenes.nombre as name',
                        'examenes.codigo as code',
                        DB::raw('COUNT(*) as count'),
                        DB::raw("'{$category->name}' as category")
                    )
                    ->groupBy('examenes.id', 'examenes.nombre', 'examenes.codigo')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get();
                
                $topExamsByCategory[$category->id] = $topExams;
            }

            // Totales
            $totalRequests = Solicitud::whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->count();
            $totalPatients = Solicitud::whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->distinct('paciente_id')
                ->count('paciente_id');
            $totalExams = DetalleSolicitud::whereHas('solicitud', function($q) use ($startDate, $endDate) {
                $q->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })->count();

            // Calcular porcentajes para cada categoría
            $totalExamsCount = $categoryStats->sum('count');
            foreach ($categoryStats as $stat) {
                $stat->percentage = $totalExamsCount > 0 ? round(($stat->count / $totalExamsCount) * 100, 2) : 0;
            }
            
            // Verificar estructura de datos para depuración
            \Log::info('Estructura de topExamsByCategory:', [
                'sample' => !empty($topExamsByCategory) ? json_encode(array_values($topExamsByCategory)[0]) : 'No hay datos'
            ]);
            
            // Asegurar que todas las categorías tienen un nombre en los exámenes
            foreach ($topExamsByCategory as $categoryId => $exams) {
                foreach ($exams as $exam) {
                    // Si no existe la propiedad category, añadirla
                    if (!isset($exam->category)) {
                        $categoria = DB::table('categorias')
                            ->where('id', $categoryId)
                            ->first();
                        if ($categoria) {
                            $exam->category = $categoria->nombre;
                        } else {
                            $exam->category = 'Categoría ' . $categoryId;
                        }
                    }
                }
            }
            
            $result = [
                'categoryStats' => $categoryStats,
                'topExamsByCategory' => $topExamsByCategory,
                'totalRequests' => $totalRequests,
                'totalPatients' => $totalPatients,
                'totalExams' => $totalExams,
            ];
            
            \Log::info('Resultado final del reporte de categorías', [
                'totalRequests' => $totalRequests,
                'totalPatients' => $totalPatients,
                'totalExams' => $totalExams,
                'categoryStats_count' => count($categoryStats),
                'topExamsByCategory_count' => count($topExamsByCategory),
                'exams_sample' => !empty($topExamsByCategory) ? 
                    array_map(function($e) { return (array)$e; }, array_slice(array_values($topExamsByCategory)[0]->toArray(), 0, 1)) : 'No hay exámenes'
            ]);
            
            return $result;

        } catch (\Exception $e) {
            \Log::error('Error en getCategoriesReport: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            \Log::error('Error completo en getCategoriesReport', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d')
            ]);
            
            return [
                'categoryStats' => [],
                'topExamsByCategory' => [],
                'totalRequests' => 0,
                'totalPatients' => 0,
                'totalExams' => 0,
            ];
        }
    }

    /**
     * Limpiar y agrupar examStats para evitar duplicados
     */
    private function cleanAndGroupExamStats($examStats)
    {
        if (!$examStats || (is_countable($examStats) && count($examStats) === 0)) {
            return $examStats;
        }

        // Convertir a array si es una Collection
        if (method_exists($examStats, 'toArray')) {
            $examStats = $examStats->toArray();
        }

        \Log::info("Limpiando examStats:", [
            'total_registros_antes' => count($examStats),
            'primer_registro' => isset($examStats[0]) ? (array)$examStats[0] : 'N/A'
        ]);

        $groupedExamStats = [];
        foreach ($examStats as $stat) {
            // Convertir a objeto si es array
            if (is_array($stat)) {
                $stat = (object)$stat;
            }

            // Usar ID como clave principal, nombre como respaldo
            $key = isset($stat->id) ? $stat->id : ($stat->name ?? 'unknown');

            if (isset($groupedExamStats[$key])) {
                // Si ya existe, sumar el count
                $groupedExamStats[$key]->count += ($stat->count ?? 0);
                \Log::info("Agrupando duplicado en limpieza: {$stat->name} (clave: {$key}), count sumado: {$stat->count}");
            } else {
                // Si no existe, crear nuevo registro limpio (sin campos extra como 'unidad')
                $groupedExamStats[$key] = (object)[
                    'id' => $stat->id ?? null,
                    'codigo' => $stat->codigo ?? ($stat->code ?? null),
                    'name' => $stat->name ?? 'Sin nombre',
                    'categoria' => $stat->categoria ?? ($stat->category ?? 'General'),
                    'count' => $stat->count ?? 0,
                    'percentage' => $stat->percentage ?? 0
                ];
            }
        }

        // Convertir de vuelta a array indexado y ordenar por count
        $cleanedStats = array_values($groupedExamStats);
        usort($cleanedStats, function($a, $b) {
            return $b->count - $a->count;
        });

        \Log::info("ExamStats después de limpieza:", [
            'total_registros_despues' => count($cleanedStats),
            'registros' => array_map(function($exam) {
                return ['name' => $exam->name, 'count' => $exam->count];
            }, array_slice($cleanedStats, 0, 5)) // Solo los primeros 5 para el log
        ]);

        return $cleanedStats;
    }
}