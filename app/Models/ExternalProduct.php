<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExternalProduct extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'external_company_id',
        'product_name',
        'upc',
        'product_details',
        'status',
        'approved_by_id',
        'approved_by_type',
        'rejected_by_id',
        'rejected_by_type',
        'approved_at',
        'rejected_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'product_details' => 'json',
    ];
}
