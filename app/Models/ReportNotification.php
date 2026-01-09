<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'report_type',
        'recipient_phone',
        'file_path',
        'file_type',
        'status',
        'message',
        'report_params',
        'twilio_sid',
        'sent_at',
        'error_message'
    ];

    protected $casts = [
        'report_params' => 'array',
        'sent_at' => 'datetime'
    ];

    /**
     * Relación con el usuario que creó la notificación
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes para filtrar por estado
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Marcar como enviado
     */
    public function markAsSent($twilioSid = null)
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'twilio_sid' => $twilioSid
        ]);
    }

    /**
     * Marcar como fallido
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage
        ]);
    }
}
