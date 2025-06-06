<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderCreditNoteTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'order_credit_note_id',
        'type_id',
        'store_manager_id',
        'location_id',
        'order_payment_id',
        'amount',
    ];
}
