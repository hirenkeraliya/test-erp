<?php

declare(strict_types=1);

namespace App\Http\Traits;

use App\Http\Notifications\VerifyEmailNotification;
use Illuminate\Notifications\Notifiable;

trait EmailVerifiable
{
    use Notifiable;

    /**
     * Mark the given entity's email as verified.
     */
    public function markEmailAsVerified(): mixed
    {
        return $this->forceFill([
            'is_email_verified' => true,
        ])->save();
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }
}
