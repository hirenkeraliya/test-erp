<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleChannelWebhookUrl extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_channel_id', 'webhook_url_type_id', 'url'];
}
