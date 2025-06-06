<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CelcomSmsService
{
    public function isEnabled(): bool
    {
        return config('services.celcom_sms.url')
            && config('services.celcom_sms.username')
            && config('services.celcom_sms.password')
            && config('services.celcom_sms.sender_id')
            && config('services.celcom_sms.enabled');
    }

    public function sendSms(string $smsTo, string $message): array
    {
        Log::channel('member_app')->info('Sms Service Start');
        $client = new Client();
        $responseData = [
            'status' => false,
            'status_code' => null,
            'response_data' => null,
        ];

        $url = config('services.celcom_sms.url');

        $data = [
            'outboundSMSMessageRequest' => [
                'address' => ['tel:+' . $smsTo],
                'senderAddress' => 'tel:821418175',
                'outboundSMSTextMessage' => [
                    'message' => $message,
                ],
                'clientCorrelator' => (string) Str::ulid(),
                'receiptRequest' => [
                    'notifyURL' => 'http://apps.com/notifications/DeliveryInfoNotification',
                    'callbackData' => 'some message id for the requestor',
                ],
                'senderName' => config('services.celcom_sms.sender_id'),
            ],
        ];

        try {
            $response = $client->post($url, [
                'auth' => [config('services.celcom_sms.username'), config('services.celcom_sms.password')],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Host' => 'ems.celcom.com.my:2688',
                ],
                'json' => $data,
            ]);

            $responseData['status'] = true;
            $responseData['status_code'] = $response->getStatusCode();

            $responseData['response_data'] = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            Log::channel('member_app')->info('Member OTP SMS Response', [
                'Member OTP SMS Response' => $response,
            ]);
        } catch (Throwable $throwable) {
            Log::channel('member_app')->error('member_app', [
                'Member OTP SMS Failed Response' => $throwable->getMessage(),
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        return $responseData;
    }
}
