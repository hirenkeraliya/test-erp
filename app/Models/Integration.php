<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Integration\Enums\IntegrationConnections;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Integration extends Authenticatable
{
    use HasFactory;
    use HasApiTokens;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'company_id', 'secret', 'connection_type', 'url', 'status'];

    protected $casts = [
        'connection_type' => IntegrationConnections::class,
        'status' => 'boolean',
    ];

    public function integrationWebhookUrls(): HasMany
    {
        return $this->hasMany(IntegrationWebhookUrl::class);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    public function getConnectionType(): IntegrationConnections
    {
        return $this->connection_type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
