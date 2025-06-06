<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceivedNoteProduct extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'goods_received_note_id',
        'product_id',
        'batch_id',
        'purchase_amount_id',
        'unit_of_measure_derivative_id',
        'derivative_ratio',
        'input_quantity',
        'quantity',
        'serial_number_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function purchaseAmount(): BelongsTo
    {
        return $this->belongsTo(PurchaseAmount::class);
    }

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class);
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);
        $this->refresh();
        $goodsReceivedNoteProduct = $this->load('goodsReceivedNote:' . $goodsReceivedNoteQueries->getColumns());

        /** @var GoodsReceivedNote $goodsReceivedNote */
        $goodsReceivedNote = $goodsReceivedNoteProduct->goodsReceivedNote;

        return $goodsReceivedNote->grn_reference;
    }

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class);
    }
}
