<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class AutomatedNotification extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'type_id',
        'name',
        'description',
        'timeframe_type_id',
        'low_stock_alert_threshold',
        'sent_notification',
        'exclude_type_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sent_notification' => 'boolean',
    ];

    public function monthly(): HasMany
    {
        return $this->hasMany(AutomatedNotificationMonthDate::class);
    }

    public function weekly(): HasMany
    {
        return $this->hasMany(AutomatedNotificationWeekDay::class);
    }

    public function automatedEmailRecipients(): BelongsToMany
    {
        return $this->belongsToMany(EmailRecipient::class);
    }

    public function automatedNotificationStores(): HasMany
    {
        return $this->hasMany(AutomatedNotificationStore::class);
    }

    public function automatedNotificationProducts(): HasMany
    {
        return $this->hasMany(AutomatedNotificationProduct::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(Style::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    public function productCollections(): BelongsToMany
    {
        return $this->belongsToMany(ProductCollection::class);
    }

    public function importRecord(): MorphOne
    {
        return $this->morphOne(ImportRecord::class, 'module')->latest();
    }
}
