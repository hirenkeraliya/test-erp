<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class IntegrationSyncUpdate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['integration_id', 'module_type', 'last_sync_date'];
}
