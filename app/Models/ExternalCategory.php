<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalCategory extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['parent_category_id', 'name', 'company_id', 'sale_channel_id', 'external_category_id'];

    public function saleChannel(): BelongsTo
    {
        return $this->belongsTo(SaleChannel::class);
    }
}
