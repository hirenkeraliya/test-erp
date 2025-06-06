<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CounterUpdateDeclarationAttempt extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['counter_update_id', 'offline_id', 'happened_at'];

    public function counterUpdateDeclarationAttemptPayments(): HasMany
    {
        return $this->hasMany(CounterUpdateDeclarationAttemptPayment::class);
    }

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }
}
