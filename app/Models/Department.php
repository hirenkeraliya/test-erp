<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'name', 'code', 'commission_percentage', 'flat_commission', 'discount_type'];

    public function getName(): string
    {
        return $this->name;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
