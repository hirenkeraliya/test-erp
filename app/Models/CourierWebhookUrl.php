<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourierWebhookUrl extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['courier_id', 'webhook_url_type_id', 'url'];
}
