<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromoterCommissionRegeneration extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['period', 'admin_id', 'super_admin_id', 'reason', 'started_at', 'completed_at'];
}
