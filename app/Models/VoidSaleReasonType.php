<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoidSaleReasonType extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['void_sale_reason_id', 'type_id'];
}
