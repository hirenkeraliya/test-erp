<?php

declare(strict_types=1);

namespace App\Http\Notifications;

use App\Models\Company;
use App\Models\EmailRecipient;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Region;
use App\Models\SuperAdmin;
use App\Models\Vendor;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class VerifyEmailNotification extends Notification
{
    public function via(Employee|Member|Vendor|Region|Location|Company|SuperAdmin|EmailRecipient $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(
        Employee|Member|Vendor|Region|Location|Company|SuperAdmin|EmailRecipient $notifiable
    ): MailMessage {
        return (new MailMessage())
            ->subject('Verify Your Email Address')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email', route('front.email_verify.verify', [
                'token' => Crypt::encryptString(json_encode([
                    'model' => $notifiable::class,
                    'id' => (string) $notifiable->id,
                    'hash' => sha1((string) $notifiable->email),
                ]) ?: ''),
            ]))
            ->line('If you did not verify an email, no further action is required.');
    }
}
