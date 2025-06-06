<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GiftCardTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['gift_card_id', 'affected_by_id', 'affected_by_type', 'type_id', 'amount'];

    // SalePayment, VoidSale
    public function affectedBy(): MorphTo
    {
        return $this->morphTo();
    }
}
