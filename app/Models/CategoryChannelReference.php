<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryChannelReference extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_channel_id', 'category_id', 'external_category_id'];

    public function saleChannel(): BelongsTo
    {
        return $this->belongsTo(SaleChannel::class);
    }
}
