<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Http\Traits\EmailVerifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailRecipient extends Model
{
    use HasFactory;
    use EmailVerifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'email_type_id', 'receiver_name', 'receiver_email', 'is_email_verified'];

    public function getEmailAttribute(): string
    {
        return $this->receiver_email;
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($emailRecipient): void {
            if ($emailRecipient->isDirty('receiver_email')) {
                $emailRecipient->updateQuietly([
                    'is_email_verified' => false,
                ]);
                EmailVerificationJob::dispatch($emailRecipient->fresh())->delay(now()->addSeconds(10))->onQueue('high');
            }
        });

        static::created(function ($emailRecipient): void {
            EmailVerificationJob::dispatch($emailRecipient)->delay(now()->addSeconds(10))->onQueue('high');
        });
    }
}
