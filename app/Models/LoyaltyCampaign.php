<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LoyaltyCampaign extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'minimum_spend_amount',
        'loyalty_points',
        'start_date',
        'end_date',
        'created_by_id',
        'created_by_type',
        'loyalty_point_expiration_days',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    public function excludedBrands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class);
    }
}
