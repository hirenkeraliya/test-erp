<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssemblyChildMasterProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['master_product_id', 'child_master_product_id', 'units'];

    public function item(): HasOne
    {
        return $this->hasOne(MasterProduct::class, 'id', 'child_master_product_id');
    }
}
