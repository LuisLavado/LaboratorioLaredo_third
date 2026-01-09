<?php

namespace App\Events;

use App\Models\Solicitud;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SolicitudCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $solicitud;

    /**
     * Create a new event instance.
     */
    public function __construct(Solicitud $solicitud)
    {
        $this->solicitud = $solicitud->load(['paciente', 'examenes', 'user', 'servicio']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Enviar solo al canal de laboratorio (todos los usuarios de laboratorio)
        // El doctor NO necesita notificación de su propia solicitud
        return [
            new Channel('laboratory'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'new_request';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
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
                    'name' => $this->solicitud->user->name ?? 
                             ($this->solicitud->user->nombres . ' ' . $this->solicitud->user->apellidos),
                ],
                'examenes_count' => $this->solicitud->examenes->count(),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
