<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderLoyaltyPoint extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['order_id', 'loyalty_points', 'amount'];

    public function getName(): string
    {
        return 'Order Loyalty Point';
    }
}
