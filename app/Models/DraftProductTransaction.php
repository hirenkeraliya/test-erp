<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DraftProductTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'approved_by_id',
        'approved_by_type',
        'rejected_by_id',
        'rejected_by_type',
        'approved_at',
        'rejected_at',
    ];

    public function approvedBy(): MorphTo
    {
        return $this->morphTo();
    }
}
