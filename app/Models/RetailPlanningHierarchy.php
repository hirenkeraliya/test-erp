<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RetailPlanningHierarchy extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id', 'company_id'];
}
