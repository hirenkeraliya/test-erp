<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImportRecordFailedRow extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['import_record_id', 'row_data', 'fail_reasons'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'row_data' => 'json',
        'fail_reasons' => 'json',
    ];
}
