<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomationService
{
    public function isEnabled(): bool
    {
        return config('services.automation.enabled');
    }

    public function sendOrderDetails(Member $member): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $data = [
            'service' => 'Online',
            'selectFeedbackMode' => 'Triggered',
            'feedbackChannel' => 'WhatsApp',
            'enterMrn' => $member->getFullName(),
            'enterPatientName' => $member->getFullName(),
            'selectServiceAvailed' => 'Online',
            'overallExperience' => [
                'rating' => (string) random_int(1, 5),
            ],
            'nps' => [
                'rating' => (string) random_int(1, 10),
            ],
            'mrn' => $member->getFullName(),
            'overallRating' => (string) random_int(1, 5),
            'feedbackMain' => 'NPS',
            'patientName' => $member->getFullName(),
            'serviceAvailed' => 'Online',
            'feedbackMode' => 'Triggered',
            'buName' => 'Pulse',
            'whatsAppNumber' => $member->getMobileNumber(),
            'detailedFeedback' => false,
        ];

        try {
            $response = Http::withToken(config('services.automation.token'))
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post(config('services.automation.url'), $data);

            Log::channel('automation')->error('Order Automation', [
                'Request Details' => $data,
                'Response' => $response,
            ]);
        } catch (Throwable $throwable) {
            Log::channel('automation')->error('Order Automation', [
                'Automation Send Failed Response' => $throwable->getMessage(),
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }
    }
}
