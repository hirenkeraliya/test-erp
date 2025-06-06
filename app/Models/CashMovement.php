<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashMovement extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'offline_id',
        'counter_update_id',
        'cash_movement_type_id',
        'cash_movement_reason_id',
        'other_reason',
        'remarks',
        'authorizer_id',
        'authorizer_type',
        'amount',
        'happened_at',
    ];

    public function authorizer(): MorphTo
    {
        return $this->morphTo();
    }

    public function cashMovementReason(): BelongsTo
    {
        return $this->belongsTo(CashMovementReason::class);
    }

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function getCashMovementReason(): ?CashMovementReason
    {
        return $this->cashMovementReason;
    }

    public function getCounterUpdate(): ?CounterUpdate
    {
        return $this->counterUpdate;
    }

    public function getAuthorizer(): StoreManager|Director|null
    {
        return $this->authorizer;
    }

    public function getKey(): int
    {
        return $this->id;
    }

    public function getOtherReason(): ?string
    {
        return $this->other_reason;
    }

    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    public function getCashMovementTypeId(): int
    {
        return $this->cash_movement_type_id;
    }

    public function getCashMovementReasonId(): ?int
    {
        return $this->cash_movement_reason_id;
    }

    public function mismatches(): MorphMany
    {
        return $this->morphMany(PosMismatch::class, 'module');
    }

    public function getHappenedAt(): ?string
    {
        return $this->happened_at;
    }
}
