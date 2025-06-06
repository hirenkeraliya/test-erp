<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductChannelReferenceCategory extends Model
{
    use HasFactory;

    protected $table = 'product_channel_reference_categories';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['product_channel_references_id', 'external_category_id'];
}
