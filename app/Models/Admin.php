<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasFactory;
    use LogsActivity;
    use Notifiable;
    use CaseSensitiveConditionals;
    use HasPermissions;
    use HasRoles;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'employee_id',
        'external_login_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['username', 'employee_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token): bool
    {
        /** @var Employee $employee */
        $employee = $this->employee;

        if (null !== $employee->email) {
            $url = route('admin.reset_password', [
                'token' => $token,
            ]);

            Notification::route('mail', [
                $employee->email => $employee->getFullName(),
            ])->notify(new ResetPassword($url));

            return true;
        }

        return false;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getEmployeeId(): int
    {
        return $this->employee_id;
    }
}
