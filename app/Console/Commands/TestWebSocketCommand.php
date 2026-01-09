<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Examen;
use App\Models\Solicitud;
use App\Http\Controllers\WebhookController;

class TestWebSocketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:test {--count=1 : Number of test requests to create} {--complete : Mark requests as completed for doctor notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test solicitudes to test WebSocket notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');
        $complete = $this->option('complete');

        if ($complete) {
            $this->info("🚀 Creando {$count} solicitud(es) y marcándolas como COMPLETADAS para probar notificaciones a doctores...");
        } else {
            $this->info("🚀 Creando {$count} solicitud(es) de prueba para notificaciones a laboratorio...");
        }
        $this->newLine();

        // Verificar datos necesarios
        $doctor = User::where('role', 'doctor')->first();
        $paciente = Paciente::first();
        $servicio = Servicio::first();
        $examen = Examen::first();

        if (!$doctor) {
            $this->error('❌ No hay doctores en la base de datos');
            return 1;
        }

        if (!$paciente) {
            $this->error('❌ No hay pacientes en la base de datos');
            return 1;
        }

        if (!$servicio) {
            $this->error('❌ No hay servicios en la base de datos');
            return 1;
        }

        if (!$examen) {
            $this->error('❌ No hay exámenes en la base de datos');
            return 1;
        }

        $this->info("👨‍⚕️ Doctor: {$doctor->nombre} {$doctor->apellido}");
        $this->info("🏥 Paciente: {$paciente->nombres} {$paciente->apellidos}");
        $this->info("🔬 Examen: {$examen->nombre}");
        $this->info("📋 Servicio: {$servicio->nombre}");
        $this->newLine();

        $webhookController = new WebhookController();

        for ($i = 1; $i <= $count; $i++) {
            $this->info("📝 Creando solicitud {$i}/{$count}...");

            // Crear solicitud
            $solicitud = Solicitud::create([
                'fecha' => now()->format('Y-m-d'),
                'hora' => now()->format('H:i'),
                'servicio_id' => $servicio->id,
                'numero_recibo' => 'WEBSOCKET-TEST-' . now()->format('YmdHis') . '-' . $i,
                'rdr' => false,
                'sis' => false,
                'exon' => false,
                'paciente_id' => $paciente->id,
                'user_id' => $doctor->id,
            ]);

            // Agregar examen
            $solicitud->examenes()->attach($examen->id);
            $solicitud->load(['paciente', 'examenes', 'user', 'servicio']);

            $this->info("✅ Solicitud #{$solicitud->id} creada - Recibo: {$solicitud->numero_recibo}");

            // Disparar evento WebSocket según el tipo
            if ($complete) {
                // Marcar como completada para probar notificaciones a doctores
                $webhookController->triggerSolicitudWebhook($solicitud, 'solicitud.completed');
                $this->info("✅ Evento WebSocket 'solicitud.completed' enviado (para doctores)");
            } else {
                // Crear solicitud para probar notificaciones a laboratorio
                $webhookController->triggerSolicitudWebhook($solicitud, 'solicitud.created');
                $this->info("🔥 Evento WebSocket 'solicitud.created' enviado (para laboratorio)");
            }

            if ($i < $count) {
                $this->info("⏳ Esperando 2 segundos antes de la siguiente...");
                sleep(2);
            }

            $this->newLine();
        }

        $this->info("🎉 ¡Todas las solicitudes creadas exitosamente!");

        if ($complete) {
            $this->info("👨‍⚕️ Revisa el frontend como DOCTOR para ver las notificaciones de resultados completados");
            $this->info("🔔 Solo los doctores deberían recibir estas notificaciones");
        } else {
            $this->info("🏥 Revisa el frontend como LABORATORIO para ver las notificaciones de solicitudes creadas");
            $this->info("🔔 Solo el laboratorio debería recibir estas notificaciones");
        }

        $this->info("👀 Verifica que el rol correcto reciba las notificaciones");

        return 0;
    }
}
