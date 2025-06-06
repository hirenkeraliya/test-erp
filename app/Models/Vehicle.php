<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'plate_no',
        'type_of_vehicle',
        'status',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
