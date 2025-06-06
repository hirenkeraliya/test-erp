<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Http\Traits\EmailVerifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SuperAdmin extends Authenticatable
{
    use CanResetPassword;
    use Notifiable;
    use HasFactory;
    use LogsActivity;
    use CaseSensitiveConditionals;
    use EmailVerifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'is_email_verified',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['username'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($superAdmin): void {
            if ($superAdmin->isDirty('email')) {
                $superAdmin->updateQuietly([
                    'is_email_verified' => false,
                ]);
                EmailVerificationJob::dispatch($superAdmin->fresh())->delay(now()->addSeconds(10))->onQueue('high');
            }
        });

        static::created(function ($superAdmin): void {
            EmailVerificationJob::dispatch($superAdmin)->delay(now()->addSeconds(10))->onQueue('high');
        });
    }
}
