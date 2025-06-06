<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturnReason extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'reason', 'location_id', 'put_back_in_inventory'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'put_back_in_inventory' => 'boolean',
    ];

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getPutBackInInventory(): bool
    {
        return $this->put_back_in_inventory;
    }

    public function saleReturnReasonTypes(): HasMany
    {
        return $this->hasMany(SaleReturnReasonType::class);
    }
}
