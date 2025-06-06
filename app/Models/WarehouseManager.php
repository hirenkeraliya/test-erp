<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class WarehouseManager extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use LogsActivity;
    use Notifiable;
    use HasPermissions;
    use HasRoles;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'username',
        'password',
        'remember_token',
        'forgot_password_token',
        'forgot_password_token_expiration_at',
        'fcm_token',
        'external_login_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token', 'forgot_password_token'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'username'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function sendPasswordResetNotification($token): bool
    {
        /** @var Employee $employee */
        $employee = $this->employee;

        if (null !== $employee->email) {
            $url = route('warehouse_manager.reset_password', [
                'token' => $token,
            ]);

            Notification::route('mail', [
                $employee->email => $employee->getFullName(),
            ])->notify(new ResetPassword($url));

            return true;
        }

        return false;
    }

    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function revokeCurrentToken(int $tokenId): void
    {
        $this->tokens()->where('id', $tokenId)->delete();
    }
}
