<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ExportRecord extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'type_id',
        'created_by_type',
        'created_by_id',
        'module_type',
        'module_id',
        'filters',
        'job_queued_at',
        'job_started_at',
        'job_ended_at',
        'status',
        'job_id',
        'headers',
        'total_records',
        'total_exported_records',
    ];

    protected $casts = [
        'filters' => 'json',
        'headers' => 'json',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('export_file')
            ->singleFile();
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }
}
