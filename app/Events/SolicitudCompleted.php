<?php

namespace App\Events;

use App\Models\Solicitud;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SolicitudCompleted implements ShouldBroadcast
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
        // Enviar al doctor específico que creó la solicitud
        return [
            new PrivateChannel('doctor.' . $this->solicitud->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'result_ready';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'solicitud.completed',
            'solicitud_id' => $this->solicitud->id,
            'user_id' => $this->solicitud->user_id, // ID del doctor que hizo la solicitud
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
                'examenes_count' => $this->solicitud->examenes->count(),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
