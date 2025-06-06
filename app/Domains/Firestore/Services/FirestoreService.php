<?php

declare(strict_types=1);

namespace App\Domains\Firestore\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

class FirestoreService
{
    public function push(string $messageTitle, ?string $message, string $token, ?array $payload = null): void
    {
        if (! $this->isFirebaseEnabled()) {
            return;
        }

        try {
            $messaging = Firebase::messaging();

            /** @phpstan-ignore-next-line */
            $cloudMessage = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => $messageTitle,
                    'body' => $message,
                ],
                'data' => $payload,
            ]);

            $response = $messaging->send($cloudMessage);

            Log::channel('firebase_notification')->error('Firebase Push Notification Response', [$response]);
        } catch (Throwable $throwable) {
            Log::channel('firebase_notification')->error('Firebase Push Notification Error', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }
    }

    public function isFirebaseEnabled(): bool
    {
        return config('services.firebase.enabled');
    }
}
