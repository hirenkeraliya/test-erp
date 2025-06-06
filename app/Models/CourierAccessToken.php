<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierAccessToken extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['courier_id', 'access_token'];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }
}
