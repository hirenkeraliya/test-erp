<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductCollectionFilterType extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['type_id', 'product_collection_filter_id'];
}
