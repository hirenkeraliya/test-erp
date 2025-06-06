<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_channel_id', 'type_id', 'user_id', 'user_type', 'status'];

    public function saleChannel(): BelongsTo
    {
        return $this->belongsTo(SaleChannel::class);
    }
}
