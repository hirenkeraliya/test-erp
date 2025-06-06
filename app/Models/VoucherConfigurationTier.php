<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoucherConfigurationTier extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['voucher_configuration_id', 'minimum_spend_amount', 'maximum_spend_amount', 'get_value'];
}
