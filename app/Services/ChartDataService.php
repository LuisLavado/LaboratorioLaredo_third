<?php

namespace App\Services;

use App\Models\Solicitud;
use App\Models\DetalleSolicitud;
use App\Models\Paciente;
use App\Models\Examen;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChartDataService
{
    /**
     * Obtener datos para gráficos de reportes generales
     */
    public function getGeneralChartData($startDate, $endDate): array
    {
        return [
            'statusDistribution' => $this->getStatusDistribution($startDate, $endDate),
            'dailyTrends' => $this->getDailyTrends($startDate, $endDate),
            'topExams' => $this->getTopExams($startDate, $endDate),
            'topDoctors' => $this->getTopDoctors($startDate, $endDate)
        ];
    }

    /**
     * Obtener datos para gráficos de reportes de doctor
     */
    public function getDoctorChartData($startDate, $endDate, $doctorId): array
    {
        return [
            'statusDistribution' => $this->getDoctorStatusDistribution($startDate, $endDate, $doctorId),
            'dailyTrends' => $this->getDoctorDailyTrends($startDate, $endDate, $doctorId),
            'topExams' => $this->getDoctorTopExams($startDate, $endDate, $doctorId),
            'topPatients' => $this->getDoctorTopPatients($startDate, $endDate, $doctorId)
        ];
    }

    /**
     * Obtener datos dinámicos según el tipo de reporte
     */
    public function getChartDataByType($type, $startDate, $endDate): array
    {
        switch ($type) {
            case 'general':
                return [
                    'statusDistribution' => $this->getStatusDistribution($startDate, $endDate),
                    'dailyTrends' => $this->getDailyTrends($startDate, $endDate),
                    'topExams' => $this->getTopExams($startDate, $endDate),
                    'topDoctors' => $this->getTopDoctors($startDate, $endDate)
                ];

            case 'exams':
                return [
                    'statusDistribution' => $this->getStatusDistribution($startDate, $endDate),
                    'dailyTrends' => $this->getDailyTrends($startDate, $endDate),
                    'examDistribution' => $this->getExamDistribution($startDate, $endDate),
                    'examsByCategory' => $this->getExamsByCategory($startDate, $endDate)
                ];

            case 'services':
                return [
                    'statusDistribution' => $this->getStatusDistribution($startDate, $endDate),
                    'dailyTrends' => $this->getDailyTrends($startDate, $endDate),
                    'serviceDistribution' => $this->getServiceDistribution($startDate, $endDate),
                    'topServices' => $this->getTopServices($startDate, $endDate)
                ];

            default:
                // Para otros tipos, usar datos generales
                return [
                    'statusDistribution' => $this->getStatusDistribution($startDate, $endDate),
                    'dailyTrends' => $this->getDailyTrends($startDate, $endDate),
                    'topExams' => $this->getTopExams($startDate, $endDate),
                    'topDoctors' => $this->getTopDoctors($startDate, $endDate)
                ];
        }
    }

    /**
     * Distribución por estado (para gráfico circular)
     */
    private function getStatusDistribution($startDate, $endDate): array
    {
        try {
            $statusCounts = DetalleSolicitud::whereHas('solicitud', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->select('estado', DB::raw('count(*) as count'))
            ->groupBy('estado')
            ->get();
        } catch (\Exception $e) {
            // Fallback usando consulta SQL directa
            $statusCounts = DB::select(
                "SELECT estado, COUNT(*) as count
                 FROM detallesolicitud d
                 JOIN solicitudes s ON d.solicitud_id = s.id
                 WHERE s.created_at BETWEEN ? AND ?
                 GROUP BY estado",
                [$startDate, $endDate]
            );
        }

        $total = collect($statusCounts)->sum('count');

        $data = [];
        $colors = [
            'pendiente' => '#f59e0b',
            'en_proceso' => '#3b82f6',
            'completado' => '#10b981'
        ];

        foreach ($statusCounts as $status) {
            $count = is_object($status) ? $status->count : $status['count'];
            $estado = is_object($status) ? $status->estado : $status['estado'];

            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            $data[] = [
                'label' => ucfirst(str_replace('_', ' ', $estado)),
                'value' => (int)$count,
                'percentage' => $percentage,
                'color' => $colors[$estado] ?? '#6b7280'
            ];
        }

        return $data;
    }

    /**
     * Tendencias diarias (para gráfico de líneas)
     */
    private function getDailyTrends($startDate, $endDate): array
    {
        try {
            $dailyStats = Solicitud::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();
        } catch (\Exception $e) {
            // Fallback usando consulta SQL directa
            $dailyStats = DB::select(
                "SELECT DATE(created_at) as date, COUNT(*) as count
                 FROM solicitudes
                 WHERE created_at BETWEEN ? AND ?
                 GROUP BY DATE(created_at)
                 ORDER BY date ASC",
                [$startDate, $endDate]
            );
        }

        return collect($dailyStats)->map(function ($stat) {
            return [
                'date' => Carbon::parse($stat->date)->format('Y-m-d'),
                'count' => (int)$stat->count
            ];
        })->toArray();
    }

    /**
     * Top exámenes (para gráfico de barras)
     */
    private function getTopExams($startDate, $endDate, $limit = 10): array
    {
        $examStats = DetalleSolicitud::whereHas('solicitud', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })
        ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
        ->select('examenes.nombre', 'examenes.codigo', DB::raw('count(*) as count'))
        ->groupBy('examenes.id', 'examenes.nombre', 'examenes.codigo')
        ->orderBy('count', 'desc')
        ->limit($limit)
        ->get();

        $total = $examStats->sum('count');

        return $examStats->map(function ($stat) use ($total) {
            $percentage = $total > 0 ? round(($stat->count / $total) * 100, 1) : 0;
            return [
                'name' => $stat->nombre,
                'code' => $stat->codigo,
                'count' => $stat->count,
                'percentage' => $percentage
            ];
        })->toArray();
    }

    /**
     * Top doctores (para gráfico de barras)
     */
    private function getTopDoctors($startDate, $endDate, $limit = 10): array
    {
        try {
            $doctorStats = Solicitud::whereBetween('created_at', [$startDate, $endDate])
                ->join('users', 'solicitudes.user_id', '=', 'users.id')
                ->select(
                    DB::raw('CONCAT(users.nombre, " ", users.apellido) as name'),
                    DB::raw('count(*) as count')
                )
                ->groupBy('users.id', 'users.nombre', 'users.apellido')
                ->orderBy('count', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            // Fallback usando consulta SQL directa
            $doctorStats = DB::select(
                "SELECT CONCAT(u.nombre, ' ', u.apellido) as name, COUNT(*) as count
                 FROM solicitudes s
                 JOIN users u ON s.user_id = u.id
                 WHERE s.created_at BETWEEN ? AND ?
                 GROUP BY u.id, u.nombre, u.apellido
                 ORDER BY count DESC
                 LIMIT ?",
                [$startDate, $endDate, $limit]
            );
        }

        $total = collect($doctorStats)->sum('count');

        return collect($doctorStats)->map(function ($stat) use ($total) {
            $percentage = $total > 0 ? round(($stat->count / $total) * 100, 1) : 0;
            return [
                'name' => $stat->name,
                'count' => (int)$stat->count,
                'percentage' => $percentage
            ];
        })->toArray();
    }

    /**
     * Distribución de exámenes (para reporte de exámenes)
     */
    private function getExamDistribution($startDate, $endDate, $limit = 8): array
    {
        try {
            $examStats = DB::table('detalle_solicitudes')
                ->join('examenes', 'detalle_solicitudes.examen_id', '=', 'examenes.id')
                ->join('solicitudes', 'detalle_solicitudes.solicitud_id', '=', 'solicitudes.id')
                ->whereBetween('solicitudes.created_at', [$startDate, $endDate])
                ->select('examenes.nombre as name', DB::raw('count(*) as count'))
                ->groupBy('examenes.id', 'examenes.nombre')
                ->orderBy('count', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            $examStats = collect([]);
        }

        $total = $examStats->sum('count');

        return $examStats->map(function ($stat) use ($total) {
            $percentage = $total > 0 ? round(($stat->count / $total) * 100, 1) : 0;
            return [
                'name' => $stat->name,
                'count' => (int)$stat->count,
                'percentage' => $percentage
            ];
        })->toArray();
    }

    /**
     * Exámenes por categoría
     */
    private function getExamsByCategory($startDate, $endDate, $limit = 6): array
    {
        try {
            $categoryStats = DB::table('detalle_solicitudes')
                ->join('examenes', 'detalle_solicitudes.examen_id', '=', 'examenes.id')
                ->join('categorias', 'examenes.categoria_id', '=', 'categorias.id')
                ->join('solicitudes', 'detalle_solicitudes.solicitud_id', '=', 'solicitudes.id')
                ->whereBetween('solicitudes.created_at', [$startDate, $endDate])
                ->select('categorias.nombre as name', DB::raw('count(*) as count'))
                ->groupBy('categorias.id', 'categorias.nombre')
                ->orderBy('count', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            $categoryStats = collect([]);
        }

        $total = $categoryStats->sum('count');

        return $categoryStats->map(function ($stat) use ($total) {
            $percentage = $total > 0 ? round(($stat->count / $total) * 100, 1) : 0;
            return [
                'name' => $stat->name,
                'count' => (int)$stat->count,
                'percentage' => $percentage
            ];
        })->toArray();
    }

    /**
     * Distribución de servicios
     */
    private function getServiceDistribution($startDate, $endDate, $limit = 8): array
    {
        try {
            $serviceStats = DB::table('solicitudes')
                ->join('servicios', 'solicitudes.servicio_id', '=', 'servicios.id')
                ->whereBetween('solicitudes.created_at', [$startDate, $endDate])
                ->select('servicios.nombre as name', DB::raw('count(*) as count'))
                ->groupBy('servicios.id', 'servicios.nombre')
                ->orderBy('count', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            $serviceStats = collect([]);
        }

        $total = $serviceStats->sum('count');

        return $serviceStats->map(function ($stat) use ($total) {
            $percentage = $total > 0 ? round(($stat->count / $total) * 100, 1) : 0;
            return [
                'name' => $stat->name,
                'count' => (int)$stat->count,
                'percentage' => $percentage
            ];
        })->toArray();
    }

    /**
     * Top servicios (alias para compatibilidad)
     */
    private function getTopServices($startDate, $endDate, $limit = 8): array
    {
        return $this->getServiceDistribution($startDate, $endDate, $limit);
    }

    /**
     * Distribución por estado para doctor específico
     */
    private function getDoctorStatusDistribution($startDate, $endDate, $doctorId): array
    {
        $statusCounts = DetalleSolicitud::whereHas('solicitud', function ($query) use ($startDate, $endDate, $doctorId) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                  ->where('user_id', $doctorId);
        })
        ->select('estado', DB::raw('count(*) as count'))
        ->groupBy('estado')
        ->get();

        $total = $statusCounts->sum('count');
        
        $data = [];
        $colors = [
            'pendiente' => '#f59e0b',
            'en_proceso' => '#3b82f6', 
            'completado' => '#10b981'
        ];

        foreach ($statusCounts as $status) {
            $percentage = $total > 0 ? round(($status->count / $total) * 100, 1) : 0;
            $data[] = [
                'label' => ucfirst(str_replace('_', ' ', $status->estado)),
                'value' => $status->count,
                'percentage' => $percentage,
                'color' => $colors[$status->estado] ?? '#6b7280'
            ];
        }

        return $data;
    }

    /**
     * Tendencias diarias para doctor específico
     */
    private function getDoctorDailyTrends($startDate, $endDate, $doctorId): array
    {
        $dailyStats = Solicitud::whereBetween('created_at', [$startDate, $endDate])
            ->where('user_id', $doctorId)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $dailyStats->map(function ($stat) {
            return [
                'date' => Carbon::parse($stat->date)->format('Y-m-d'),
                'count' => $stat->count
            ];
        })->toArray();
    }

    /**
     * Top exámenes para doctor específico
     */
    private function getDoctorTopExams($startDate, $endDate, $doctorId, $limit = 10): array
    {
        $examStats = DetalleSolicitud::whereHas('solicitud', function ($query) use ($startDate, $endDate, $doctorId) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                  ->where('user_id', $doctorId);
        })
        ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
        ->select('examenes.nombre', 'examenes.codigo', DB::raw('count(*) as count'))
        ->groupBy('examenes.id', 'examenes.nombre', 'examenes.codigo')
        ->orderBy('count', 'desc')
        ->limit($limit)
        ->get();

        $total = $examStats->sum('count');

        return $examStats->map(function ($stat) use ($total) {
            $percentage = $total > 0 ? round(($stat->count / $total) * 100, 1) : 0;
            return [
                'name' => $stat->nombre,
                'code' => $stat->codigo,
                'count' => $stat->count,
                'percentage' => $percentage
            ];
        })->toArray();
    }

    /**
     * Top pacientes para doctor específico
     */
    private function getDoctorTopPatients($startDate, $endDate, $doctorId, $limit = 10): array
    {
        $patientStats = Solicitud::whereBetween('created_at', [$startDate, $endDate])
            ->where('user_id', $doctorId)
            ->join('pacientes', 'solicitudes.paciente_id', '=', 'pacientes.id')
            ->select('pacientes.nombres', 'pacientes.apellidos', 'pacientes.dni', DB::raw('count(*) as count'))
            ->groupBy('pacientes.id', 'pacientes.nombres', 'pacientes.apellidos', 'pacientes.dni')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();

        $total = $patientStats->sum('count');

        return $patientStats->map(function ($stat) use ($total) {
            $percentage = $total > 0 ? round(($stat->count / $total) * 100, 1) : 0;
            return [
                'name' => $stat->nombres . ' ' . $stat->apellidos,
                'dni' => $stat->dni,
                'count' => $stat->count,
                'percentage' => $percentage
            ];
        })->toArray();
    }
}
