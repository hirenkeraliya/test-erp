<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\SaleItemDiscount\Interfaces\SaleItemDiscountInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ComplimentaryItemReason extends Model implements SaleItemDiscountInterface
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'reason'];

    public function getName(): string
    {
        return $this->reason;
    }

    public function saleDiscountComplimentaryItemReason(): MorphMany
    {
        return $this->MorphMany(SaleDiscount::class, 'discountable');
    }

    public function saleItemDiscountComplimentaryItemReason(): MorphMany
    {
        return $this->MorphMany(SaleItemDiscount::class, 'discountable');
    }
}
