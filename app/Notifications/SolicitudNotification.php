<?php

namespace App\Notifications;

use App\Models\Solicitud;
use App\Events\NotificationSent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Solicitud $solicitud;
    public string $event;
    public array $customData;

    /**
     * Create a new notification instance.
     */
    public function __construct(Solicitud $solicitud, string $event, array $customData = [])
    {
        $this->solicitud = $solicitud;
        $this->event = $event;
        $this->customData = $customData;
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
        $data = [
            'solicitud_id' => $this->solicitud->id,
            'event' => $this->event,
            'paciente' => [
                'id' => $this->solicitud->paciente->id ?? null,
                'nombres' => $this->solicitud->paciente->nombres ?? 'N/A',
                'apellidos' => $this->solicitud->paciente->apellidos ?? 'N/A',
                'dni' => $this->solicitud->paciente->dni ?? 'N/A',
            ],
            'servicio' => [
                'id' => $this->solicitud->servicio->id ?? null,
                'nombre' => $this->solicitud->servicio->nombre ?? 'N/A',
            ],
            'doctor' => [
                'id' => $this->solicitud->user->id ?? null,
                'nombre' => $this->solicitud->user->nombre ?? 'N/A',
                'apellido' => $this->solicitud->user->apellido ?? 'N/A',
            ],
            'examenes_count' => $this->solicitud->examenes->count(),
            'fecha' => $this->solicitud->fecha,
            'hora' => $this->solicitud->hora,
            'created_at' => $this->solicitud->created_at->toISOString(),
        ];

        // Agregar información específica según el evento
        switch ($this->event) {
            case 'solicitud.created':
                $data['title'] = 'Nueva solicitud de exámenes';
                $data['message'] = "Dr. {$this->solicitud->user->nombre} {$this->solicitud->user->apellido} ha creado una nueva solicitud para {$this->solicitud->paciente->nombres} {$this->solicitud->paciente->apellidos}";
                $data['action_url'] = "/solicitudes/{$this->solicitud->id}";
                $data['icon'] = 'plus-circle';
                $data['color'] = 'blue';
                break;

            case 'solicitud.updated':
                $data['title'] = 'Solicitud actualizada';
                $data['message'] = "La solicitud #{$this->solicitud->id} de {$this->solicitud->paciente->nombres} {$this->solicitud->paciente->apellidos} ha sido actualizada";
                $data['action_url'] = "/doctor/solicitudes/{$this->solicitud->id}/resultados";
                $data['icon'] = 'refresh';
                $data['color'] = 'yellow';
                break;

            case 'solicitud.completed':
                $data['title'] = 'Resultados disponibles';
                $data['message'] = "Los resultados de la solicitud #{$this->solicitud->id} de {$this->solicitud->paciente->nombres} {$this->solicitud->paciente->apellidos} están listos";
                $data['action_url'] = "/doctor/solicitudes/{$this->solicitud->id}/resultados";
                $data['icon'] = 'check-circle';
                $data['color'] = 'green';
                break;
        }

        // Merge custom data
        return array_merge($data, $this->customData);
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return $this->event;
    }

    /**
     * Handle the notification after it's stored in the database
     */
    public function afterStore($notifiable, $notification): void
    {
        // Disparar evento WebSocket
        broadcast(new NotificationSent($notification, $notifiable->id));
    }
}
