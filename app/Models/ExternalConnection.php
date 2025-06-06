<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ExternalConnection extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'url',
        'token',
        'approved_at',
        'rejected_at',
        'create_by_super_admin_id',
        'approve_by_super_admin_id',
        'status',
    ];

    public function externalCompanies(): HasMany
    {
        return $this->hasMany(ExternalCompany::class);
    }

    public function getExternalCompanies(): Collection
    {
        return $this->externalCompanies;
    }
}
