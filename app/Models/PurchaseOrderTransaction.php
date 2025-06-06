<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PurchaseOrderTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_order_id',
        'old_status',
        'new_status',
        'user_id',
        'user_type',
        'external_username',
    ];

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
