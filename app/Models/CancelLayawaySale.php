<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\SaleQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CancelLayawaySale extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_id', 'store_manager_id', 'reason'];

    public function storeManager(): BelongsTo
    {
        return $this->belongsTo(StoreManager::class);
    }

    public function creditNote(): HasOne
    {
        return $this->hasOne(CreditNote::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $saleQueries = resolve(SaleQueries::class);
        $this->refresh();
        $cancelLayawaySale = $this->load('sale:' . $saleQueries->getOfflineSaleId());

        /** @var Sale $sale */
        $sale = $cancelLayawaySale->sale;

        return $sale->offline_sale_id;
    }
}
