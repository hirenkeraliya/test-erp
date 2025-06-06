<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsHistory extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['mobile_number', 'message', 'status', 'sending_date', 'response_data'];

    protected $casts = [
        'response_data' => 'json',
    ];
}
