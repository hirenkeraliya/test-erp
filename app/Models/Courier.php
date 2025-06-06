<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Courier\Enums\CourierTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends Model
{
    use HasFactory;
    use CaseSensitiveConditionals;
    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'code', 'type_id', 'url', 'client_id', 'client_secret'];

    protected $casts = [
        'type_id' => CourierTypes::class,
    ];

    public function courierWebhookUrls(): HasMany
    {
        return $this->hasMany(CourierWebhookUrl::class);
    }

    // Getters
    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): CourierTypes
    {
        return $this->type_id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
