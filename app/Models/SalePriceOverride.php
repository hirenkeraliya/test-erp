<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\SaleQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalePriceOverride extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_id', 'negotiator_id', 'negotiator_type', 'override_price'];

    // It can be Director, Store Manager & Cashier
    public function negotiator(): MorphTo
    {
        return $this->morphTo();
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $saleQueries = resolve(SaleQueries::class);
        $this->refresh();
        $salePriceOverride = $this->load('sale:' . $saleQueries->getOfflineSaleId());

        /** @var Sale $sale */
        $sale = $salePriceOverride->sale;

        return $sale->offline_sale_id;
    }
}
