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

class StoreManager extends Authenticatable
{
    use HasFactory;
    use LogsActivity;
    use Notifiable;
    use CaseSensitiveConditionals;
    use HasApiTokens;
    use HasPermissions;
    use HasRoles;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'username',
        'password',
        'passcode',
        'price_override_type',
        'remember_token',
        'forgot_password_token',
        'forgot_password_token_expiration_at',
        'price_override_limit_percentage_for_item',
        'price_override_limit_percentage_for_cart',
        'can_manage_wholesale',
        'fcm_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'can_manage_wholesale' => 'boolean',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token', 'forgot_password_token'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(
                [
                    'employee_id',
                    'username',
                    'price_override_type',
                    'price_override_limit_percentage_for_item',
                    'price_override_limit_percentage_for_cart',
                ]
            )
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class);
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function sendPasswordResetNotification($token): bool
    {
        /** @var Employee $employee */
        $employee = $this->employee;

        if (null !== $employee->email) {
            $url = route('store_manager.reset_password', [
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

    public function getEmployeeId(): int
    {
        return $this->employee_id;
    }

    public function revokeCurrentToken(int $tokenId): void
    {
        $this->tokens()->where('id', $tokenId)->delete();
    }
}
