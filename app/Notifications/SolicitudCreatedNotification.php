<?php

namespace App\Notifications;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SolicitudCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $solicitud;

    /**
     * Create a new notification instance.
     */
    public function __construct(Solicitud $solicitud)
    {
        $this->solicitud = $solicitud->load(['paciente', 'user']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'solicitud.created',
            'solicitud_id' => $this->solicitud->id,
            'solicitud' => [
                'id' => $this->solicitud->id,
                'fecha' => $this->solicitud->fecha,
                'hora' => $this->solicitud->hora,
                'numero_recibo' => $this->solicitud->numero_recibo,
                'paciente' => [
                    'id' => $this->solicitud->paciente->id,
                    'nombres' => $this->solicitud->paciente->nombres,
                    'apellidos' => $this->solicitud->paciente->apellidos,
                    'dni' => $this->solicitud->paciente->dni,
                ],
                'doctor' => [
                    'id' => $this->solicitud->user->id,
                    'nombre' => $this->solicitud->user->nombre,
                    'apellido' => $this->solicitud->user->apellido,
                ],
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
