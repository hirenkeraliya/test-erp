<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeGroup extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'item_purchase_limit',
        'purchase_limit_type_id',
        'limit_reset_type_id',
        'limit_reset',
        'created_by_id',
        'created_by_type',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
