<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitOfMeasure extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'name', 'allow_decimal_qty'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'allow_decimal_qty' => 'boolean',
    ];

    public function derivatives(): HasMany
    {
        return $this->hasMany(UnitOfMeasureDerivative::class);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
