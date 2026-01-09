<?php

namespace App\Exports\Patients;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Hoja Individual de Resultados por Paciente
 */
class PatientResultsDetailSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $patient;
    protected $startDate;
    protected $endDate;
    protected $patientNumber;

    public function __construct($patient, $startDate, $endDate, $patientNumber)
    {
        $this->patient = $patient;
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        $this->patientNumber = $patientNumber;
    }

    public function title(): string
    {
        $patientName = $this->getPatientName($this->patient);
        // Limpiar caracteres no válidos para nombres de hojas de Excel
        $cleanName = preg_replace('/[\\\\\/\?\*\[\]:]+/', '', $patientName);
        return substr($cleanName, 0, 31); // Máximo 31 caracteres
    }

    public function headings(): array
    {
        $patientName = $this->getPatientName($this->patient);
        $documento = $this->getPatientField($this->patient, ['documento', 'dni']);
        $edad = $this->getPatientField($this->patient, ['edad']);
        $sexo = $this->getPatientField($this->patient, ['sexo']);

        return [
            ['LABORATORIO CLÍNICO LAREDO'],
            ['RESULTADOS DETALLADOS DE EXÁMENES'],
            [],
            ['INFORMACIÓN DEL PACIENTE'],
            ['Nombre:', $patientName],
            ['Documento:', $documento ?: 'Sin documento'],
            ['Edad:', $edad ?: 'N/A'],
            ['Sexo:', $sexo ?: 'N/A'],
            ['Período:', $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')],
            [],
            ['RESULTADOS DE EXÁMENES'],
            ['Fecha', 'Solicitud', 'Examen', 'Campo/Tipo', 'Resultado', 'Valor Referencia', 'Unidad', 'Estado', 'Observaciones']
        ];
    }

    public function array(): array
    {
        $data = [];
        $patientId = $this->getPatientField($this->patient, ['id']);

        if (!$patientId) {
            return [['No se encontró ID del paciente']];
        }

        // Hacer query directa para obtener TODOS los exámenes (completados, pendientes, en proceso)
        $examenes = DB::table('solicitudes')
            ->join('detallesolicitud', 'solicitudes.id', '=', 'detallesolicitud.solicitud_id')
            ->join('examenes', 'detallesolicitud.examen_id', '=', 'examenes.id')
            ->leftJoin('servicios', 'solicitudes.servicio_id', '=', 'servicios.id')
            ->leftJoin('users', 'solicitudes.user_id', '=', 'users.id')
            ->where('solicitudes.paciente_id', $patientId)
            ->whereBetween('solicitudes.fecha', [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')])
            ->select(
                'solicitudes.id as solicitud_id',
                'solicitudes.fecha',
                'solicitudes.numero_recibo',
                'solicitudes.estado as estado_solicitud',
                'servicios.nombre as servicio',
                'examenes.nombre as examen_nombre',
                'examenes.codigo as examen_codigo',
                'detallesolicitud.id as detalle_id',
                'detallesolicitud.estado as estado_examen',
                'detallesolicitud.resultado as resultado_directo',
                'detallesolicitud.observaciones',
                DB::raw('CONCAT(users.nombre, " ", users.apellido) as medico_solicitante')
            )
            ->orderBy('solicitudes.fecha', 'desc')
            ->orderBy('solicitudes.id')
            ->orderBy('examenes.nombre')
            ->get();

        if ($examenes->isEmpty()) {
            return [['No se encontraron exámenes para este paciente en el período especificado']];
        }

        \Log::info('Exámenes encontrados para paciente:', [
            'patient_id' => $patientId,
            'total_examenes' => $examenes->count(),
            'examenes' => $examenes->toArray()
        ]);

        // Procesar cada examen encontrado
        foreach ($examenes as $examen) {
            $fecha = $examen->fecha ? Carbon::parse($examen->fecha)->format('d/m/Y') : 'Sin fecha';
            $numeroRecibo = $examen->numero_recibo ?: $examen->solicitud_id;
            $examenNombre = $examen->examen_nombre ?: 'Sin nombre';
            $estadoExamen = $this->formatEstado($examen->estado_examen);
            $observaciones = $examen->observaciones ?: '';
            $resultadoDirecto = $examen->resultado_directo;

            // Obtener resultados detallados por campos si existen
            $resultadosDetallados = DB::table('valores_resultado')
                ->join('campos_examen', 'valores_resultado.campo_examen_id', '=', 'campos_examen.id')
                ->where('valores_resultado.detalle_solicitud_id', $examen->detalle_id)
                ->select(
                    'campos_examen.nombre as campo_nombre',
                    'valores_resultado.valor',
                    'campos_examen.valor_referencia',
                    'campos_examen.unidad',
                    'valores_resultado.fuera_rango'
                )
                ->get();

            if ($resultadosDetallados->isNotEmpty()) {
                // EXAMEN COMPLEJO: Tiene campos específicos
                foreach ($resultadosDetallados as $resultado) {
                    $data[] = [
                        $fecha,
                        $numeroRecibo,
                        $examenNombre,
                        $resultado->campo_nombre ?: 'Campo específico',
                        $resultado->valor ?: 'Sin resultado',
                        $resultado->valor_referencia ?: 'Sin referencia',
                        $resultado->unidad ?: '',
                        $estadoExamen,
                        $observaciones
                    ];
                }
            } else {
                // EXAMEN SIMPLE: Sin campos específicos
                $valorResultado = '';

                if ($resultadoDirecto !== null && $resultadoDirecto !== '') {
                    // Tiene resultado directo
                    $valorResultado = $resultadoDirecto;
                } else {
                    // Sin resultado, mostrar según estado
                    switch (strtolower($estadoExamen)) {
                        case 'completado':
                            $valorResultado = 'Completado - Sin resultado registrado';
                            break;
                        case 'pendiente':
                            $valorResultado = 'Pendiente de resultado';
                            break;
                        case 'en proceso':
                        case 'en_proceso':
                            $valorResultado = 'En proceso de análisis';
                            break;
                        default:
                            $valorResultado = 'Estado: ' . $estadoExamen;
                    }
                }

                $data[] = [
                    $fecha,
                    $numeroRecibo,
                    $examenNombre,
                    'Resultado general',
                    $valorResultado,
                    'Sin referencia específica',
                    '',
                    $estadoExamen,
                    $observaciones
                ];
            }
        }

        if (empty($data)) {
            return [['No se encontraron resultados para mostrar']];
        }

        \Log::info('Datos finales para Excel:', ['total_rows' => count($data), 'first_row' => $data[0] ?? null]);

        return $data;
    }


    public function columnWidths(): array
    {
        return [
            'A' => 40,  // Fecha
            'B' => 15,  // Solicitud
            'C' => 35,  // Examen
            'D' => 20,  // Campo
            'E' => 25,  // Resultado
            'F' => 30,  // Valor Referencia
            'G' => 10,  // Unidad
            'H' => 17,  // Estado
            'I' => 30,  // Observaciones
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Título principal
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1f4e79']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Subtítulo
            2 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Título información paciente
            4 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'ffffff']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2f5f8f']],
            ],
            // Título resultados
            11 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'ffffff']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2f5f8f']],
            ],
            // Headers de tabla
            12 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '1f4e79']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'dae3f3']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '4472c4']],
                ],
            ],
        ];
    }

    private function getPatientName($patient)
    {
        if (is_array($patient)) {
            $name = trim(($patient['nombres'] ?? '') . ' ' . ($patient['apellidos'] ?? ''));
            return !empty($name) ? $name : 'Paciente ' . ($patient['id'] ?? 'Sin ID');
        } else {
            $name = trim(($patient->nombres ?? '') . ' ' . ($patient->apellidos ?? ''));
            return !empty($name) ? $name : 'Paciente ' . ($patient->id ?? 'Sin ID');
        }
    }

    private function getPatientField($patient, $fields)
    {
        foreach ($fields as $field) {
            if (is_array($patient)) {
                if (isset($patient[$field]) && !empty($patient[$field])) {
                    return $patient[$field];
                }
            } else {
                if (isset($patient->$field) && !empty($patient->$field)) {
                    return $patient->$field;
                }
            }
        }
        return null;
    }

    private function formatEstado($estado)
    {
        switch (strtolower($estado)) {
            case 'completado':
                return 'COMPLETADO';
            case 'pendiente':
                return 'PENDIENTE';
            case 'en_proceso':
                return 'EN PROCESO';
            default:
                return strtoupper($estado ?: 'SIN ESTADO');
        }
    }
}
