<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreManagerAuthorizationCodeUsage extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['store_manager_authorization_code_id', 'usage_type_id', 'reference_id', 'reference_type'];
}
