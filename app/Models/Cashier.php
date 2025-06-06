<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cashier extends Authenticatable
{
    use HasFactory;
    use LogsActivity;
    use HasApiTokens;
    use CaseSensitiveConditionals;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'cashier_group_id',
        'counter_update_id',
        'username',
        'pin',
        'created_by_id',
        'created_by_type',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'cashier_group_id', 'counter_update_id', 'username', 'last_login_at'])
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

    public function cashierGroup(): BelongsTo
    {
        return $this->belongsTo(CashierGroup::class);
    }

    public function getCounterUpdateId(): ?int
    {
        return $this->counter_update_id;
    }

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function getPin(): string
    {
        return $this->pin;
    }

    public function getCounterUpdate(): ?CounterUpdate
    {
        return $this->counterUpdate;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function revokeCurrentToken(int $tokenId): void
    {
        $this->tokens()->where('id', $tokenId)->delete();
    }
}
