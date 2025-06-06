<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['voucher_id', 'action_type_id', 'sale_id', 'order_id', 'location_id', 'happened_at'];

    public function voucher(): BelongsTo
    {
        return $this->BelongsTo(Voucher::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
