<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderInvoiceTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['purchase_order_invoice_id', 'old_status', 'new_status', 'user_id', 'user_type'];
}
