<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalLocation extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'external_company_id',
        'external_location_id',
        'name',
        'code',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'area_code',
        'fax',
        'type_id',
    ];

    public function externalCompany(): BelongsTo
    {
        return $this->belongsTo(ExternalCompany::class);
    }

    public function getNameWithCode(): string
    {
        return $this->name . ' (' . $this->code . ')';
    }
}
