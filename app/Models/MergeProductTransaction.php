<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MergeProductTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['user_id', 'user_type', 'old_product_id', 'new_product_id'];

    public function oldProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'old_product_id')->withTrashed();
    }
}
