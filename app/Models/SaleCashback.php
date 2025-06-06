<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\SaleQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleCashback extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_id', 'cashback_id', 'cash_movement_id', 'amount', 'round_off', 'happened_at'];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function cashMovement(): BelongsTo
    {
        return $this->belongsTo(CashMovement::class);
    }

    public function cashbackConfiguration(): BelongsTo
    {
        return $this->belongsTo(Cashback::class, 'cashback_id');
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $saleQueries = resolve(SaleQueries::class);
        $this->refresh();
        $saleCashback = $this->load('sale:' . $saleQueries->getOfflineSaleId());

        /** @var Sale $sale */
        $sale = $saleCashback->sale;

        return $sale->offline_sale_id;
    }
}
