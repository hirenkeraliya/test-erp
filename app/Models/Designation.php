<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Designation extends Model
{
    use HasFactory;
    use CaseSensitiveConditionals;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'name', 'code', 'created_by_type', 'created_by_id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
