<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssemblyChildProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['product_id', 'child_product_id', 'units'];

    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'id', 'child_product_id');
    }
}
