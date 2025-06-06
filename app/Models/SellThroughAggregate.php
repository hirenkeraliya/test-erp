<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellThroughAggregate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'location_id',
        'product_id',
        'goods_receive_note_in',
        'goods_receive_note_out',
        'stock_adjustment_in',
        'stock_adjustment_out',
        'stock_transfer_in',
        'stock_transfer_out',
        'delivery_order_in',
        'delivery_order_out',
        'foc_sold',
        'sold',
        'sale_amount',
        'sold_online',
        'foc_sold_online',
        'total_online_sold_amount',
        'return',
        'sale_return_amounts',
        'balance',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function getdate(): string
    {
        return $this->date;
    }

    public function getLocationId(): int
    {
        return $this->location_id;
    }

    public function getProductId(): int
    {
        return $this->product_id;
    }

    public function getGoodsReceiveNoteIn(): float
    {
        return $this->goods_receive_note_in;
    }

    public function getGoodsReceiveNoteOut(): float
    {
        return $this->goods_receive_note_out;
    }

    public function getStockAdjustmentIn(): float
    {
        return $this->stock_adjustment_in;
    }

    public function getStockAdjustmentOut(): float
    {
        return $this->stock_adjustment_out;
    }

    public function getStockTransferIn(): float
    {
        return $this->stock_transfer_in;
    }

    public function getStockTransferOut(): float
    {
        return $this->stock_transfer_out;
    }

    public function getDeliveryOrderIn(): float
    {
        return $this->delivery_order_in;
    }

    public function getDeliveryOrderOut(): float
    {
        return $this->delivery_order_out;
    }

    public function getFocSold(): float
    {
        return $this->foc_sold;
    }

    public function getSold(): float
    {
        return $this->sold;
    }

    public function getReturn(): float
    {
        return $this->return;
    }
}
