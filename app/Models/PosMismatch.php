<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class PosMismatch extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['module_id', 'module_type', 'message', 'remarks', 'causer', 'resolved_at'];

    // Can be Sale, SaleReturn, BookingPayment, CreditNote, CashMovement, Voucher
    public function location(): MorphTo
    {
        return $this->morphTo();
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return mixed[]
     */
    public static function getPreparedMismatches(?Collection $mismatches): array
    {
        if (! $mismatches instanceof Collection) {
            return [];
        }

        return $mismatches->map(fn ($mismatch): array => [
            'message' => $mismatch->getMessage(),
        ])->toArray();
    }
}
