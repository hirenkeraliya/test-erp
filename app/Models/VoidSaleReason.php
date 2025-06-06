<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoidSaleReason extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'reason'];

    public function getReason(): string
    {
        return $this->reason;
    }

    public function voidSaleReasonTypes(): HasMany
    {
        return $this->hasMany(VoidSaleReasonType::class);
    }
}
