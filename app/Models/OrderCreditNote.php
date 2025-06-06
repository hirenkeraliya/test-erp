<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCreditNote extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'store_manager_id',
        'location_id',
        'order_return_id',
        'member_id',
        'expiry_date',
        'total_amount',
        'available_amount',
        'status',
        'digital_invoice_number',
        'digital_invoice_submitted',
    ];

    protected $casts = [
        'digital_invoice_submitted' => 'boolean',
    ];

    public function storeManager(): BelongsTo
    {
        return $this->belongsTo(StoreManager::class)
            ->select('id', 'employee_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)
            ->select('id', 'name', 'code', 'type_id');
    }

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
