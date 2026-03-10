<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

trait WhatsappTrait
{
     /**
     * Send WhatsApp activation notification using UltraMsg
     */
    private function sendWhatsAppActivationNotification($clinic, $user, $activationToken)
    {
        try {
            $token = $token ?? env('ULTRAMSG_TOKEN', '9txshfvych4yl31t');

            $instanceId = env('ULTRAMSG_INSTANCE_ID', '127145');
            $baseUrl = "https://api.ultramsg.com/instance{$instanceId}";

            $activationUrl = url("/activate-clinic/{$activationToken}");

            $message = "🏥 Welcome to {$clinic->name}!\n\n";
            $message .= "Dear {$user->name},\n\n";
            $message .= "Your clinic registration has been completed successfully.\n\n";
            $message .= "To activate your clinic account, please click the link below:\n";
            $message .= "🔗 {$activationUrl}\n\n";
            $message .= "This activation link will expire in 24 hours.\n\n";
            $message .= "Best regards,\n" . config('app.name') . " Team";

            $phone = '+20' . substr($clinic->phone, 1);

            $params = [
                'token' => $token,
                'to' => $phone,
                'body' => $message
            ];

            $response = Http::asForm()->post("{$baseUrl}/messages/chat", $params);

            if ($response->successful()) {
                Log::info('WhatsApp activation notification sent successfully', [
                    'clinic_id' => $clinic->id,
                    'phone' => $clinic->phone,
                    'response' => $response->body()
                ]);
            } else {
                Log::error('Failed to send WhatsApp activation notification', [
                    'clinic_id' => $clinic->id,
                    'phone' => $clinic->phone,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Error sending WhatsApp activation notification', [
                'clinic_id' => $clinic->id,
                'phone' => $clinic->phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send WhatsApp activation notification for Medical Laboratory using UltraMsg
     */
    private function sendWhatsAppMedicalLabActivationNotification($medicalLab, $user, $activationToken)
    {
        try {
            $token = $token ?? env('ULTRAMSG_TOKEN', '9txshfvych4yl31t');

            $instanceId = env('ULTRAMSG_INSTANCE_ID', '127145');
            $baseUrl = "https://api.ultramsg.com/instance{$instanceId}";

            $activationUrl = url("/activate-medical-laboratory/{$activationToken}");

            $message = "🔬 Welcome to {$medicalLab->name}!\n\n";
            $message .= "Dear {$user->name},\n\n";
            $message .= "Your medical laboratory registration has been completed successfully.\n\n";
            $message .= "To activate your medical laboratory account, please click the link below:\n";
            $message .= "🔗 {$activationUrl}\n\n";
            $message .= "This activation link will expire in 24 hours.\n\n";
            $message .= "Best regards,\n" . config('app.name') . " Team";

            $phone = '+20' . substr($medicalLab->phone, 1);

            $params = [
                'token' => $token,
                'to' => $phone,
                'body' => $message
            ];

            $response = Http::asForm()->post("{$baseUrl}/messages/chat", $params);

            if ($response->successful()) {
                Log::info('WhatsApp activation notification sent successfully', [
                    'medical_laboratory_id' => $medicalLab->id,
                    'phone' => $medicalLab->phone,
                    'response' => $response->body()
                ]);
            } else {
                Log::error('Failed to send WhatsApp activation notification', [
                    'medical_laboratory_id' => $medicalLab->id,
                    'phone' => $medicalLab->phone,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Error sending WhatsApp activation notification', [
                'medical_laboratory_id' => $medicalLab->id,
                'phone' => $medicalLab->phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send WhatsApp activation notification for Radiology Center using UltraMsg
     */
    private function sendWhatsAppRadiologyCenterActivationNotification($radiologyCenter, $user, $activationToken)
    {
        try {
            $token = $token ?? env('ULTRAMSG_TOKEN', '9txshfvych4yl31t');

            $instanceId = env('ULTRAMSG_INSTANCE_ID', '127145');
            $baseUrl = "https://api.ultramsg.com/instance{$instanceId}";

            $activationUrl = url("/activate-radiology-center/{$activationToken}");

            $message = "🏥 Welcome to {$radiologyCenter->name}!\n\n";
            $message .= "Dear {$user->name},\n\n";
            $message .= "Your radiology center registration has been completed successfully.\n\n";
            $message .= "To activate your radiology center account, please click the link below:\n";
            $message .= "🔗 {$activationUrl}\n\n";
            $message .= "This activation link will expire in 24 hours.\n\n";
            $message .= "Best regards,\n" . config('app.name') . " Team";

            $phone = '+20' . substr($radiologyCenter->phone, 1);

            $params = [
                'token' => $token,
                'to' => $phone,
                'body' => $message
            ];

            $response = Http::asForm()->post("{$baseUrl}/messages/chat", $params);

            if ($response->successful()) {
                Log::info('WhatsApp activation notification sent successfully', [
                    'radiology_center_id' => $radiologyCenter->id,
                    'phone' => $radiologyCenter->phone,
                    'response' => $response->body()
                ]);
            } else {
                Log::error('Failed to send WhatsApp activation notification', [
                    'radiology_center_id' => $radiologyCenter->id,
                    'phone' => $radiologyCenter->phone,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Error sending WhatsApp activation notification', [
                'radiology_center_id' => $radiologyCenter->id,
                'phone' => $radiologyCenter->phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

}
