<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    private $twilio;
    private $from;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->from = config('services.twilio.whatsapp_from');
    }

    /**
     * Enviar mensaje de WhatsApp con archivo adjunto
     */
    public function sendReportFile(string $to, string $message, string $fileUrl): array
    {
        try {
            // Formatear número de teléfono
            $formattedTo = $this->formatPhoneNumber($to);
            
            Log::info('Enviando WhatsApp', [
                'to' => $formattedTo,
                'from' => $this->from,
                'message' => $message,
                'file_url' => $fileUrl
            ]);

            $messageData = [
                'from' => $this->from,
                'body' => $message
            ];

            // Agregar archivo si se proporciona
            if ($fileUrl) {
                $messageData['mediaUrl'] = [$fileUrl];
            }

            $twilioMessage = $this->twilio->messages->create(
                $formattedTo,
                $messageData
            );

            Log::info('WhatsApp enviado exitosamente', [
                'sid' => $twilioMessage->sid,
                'status' => $twilioMessage->status
            ]);

            return [
                'success' => true,
                'message_sid' => $twilioMessage->sid,
                'status' => $twilioMessage->status
            ];

        } catch (Exception $e) {
            Log::error('Error enviando WhatsApp', [
                'error' => $e->getMessage(),
                'to' => $to,
                'file_url' => $fileUrl
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Formatear número de teléfono para WhatsApp
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remover espacios y caracteres especiales
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Si no empieza con +, agregar código de país por defecto (Perú)
        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '51')) {
                $phone = '+' . $phone;
            } else {
                $phone = '+51' . $phone;
            }
        }

        return 'whatsapp:' . $phone;
    }

    /**
     * Verificar estado de un mensaje
     */
    public function getMessageStatus(string $messageSid): array
    {
        try {
            $message = $this->twilio->messages($messageSid)->fetch();
            
            return [
                'success' => true,
                'status' => $message->status,
                'error_code' => $message->errorCode,
                'error_message' => $message->errorMessage
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
