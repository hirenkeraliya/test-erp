<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Director extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'passcode',
        'price_override_type',
        'price_override_limit_percentage_for_item',
        'price_override_limit_percentage_for_cart',
        'created_by_id',
        'created_by_type',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }
}
