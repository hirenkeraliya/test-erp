<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DigitalInvoice extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'module_id',
        'module_type',
        'buyer_name',
        'buyer_tin',
        'buyer_identification_number',
        'buyer_sst_number',
        'buyer_email',
        'buyer_address',
        'buyer_contact',
    ];

    public function module(): MorphTo
    {
        return $this->morphTo();
    }
}
