<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ImportRecord extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id', 'type_id', 'header_columns', 'status', 'records_in_file', 'records_imported', 'records_failed', 'created_by_id', 'created_by_type', 'module_id', 'module_type',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'header_columns' => 'json',
    ];

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('upload_file')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.oasis.opendocument.spreadsheet',
                'application/vnd.ms-excel',
                'application/zip',
                'application/x-zip-compressed',
            ]);

        $this->addMediaCollection('failed_rows_file')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.oasis.opendocument.spreadsheet',
                'application/vnd.ms-excel',
                'application/zip',
                'application/x-zip-compressed',
            ]);
    }

    public function failedRows(): HasMany
    {
        return $this->hasMany(ImportRecordFailedRow::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
