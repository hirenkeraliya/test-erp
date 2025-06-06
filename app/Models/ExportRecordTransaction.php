<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExportRecordTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'downloaded_by_type', 'downloaded_by_id'];

    public function downloadedBy(): MorphTo
    {
        return $this->morphTo();
    }
}
