<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreManagerAuthorizationCode extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['store_manager_id', 'code', 'expiry_date', 'status'];

    protected $casts = [
        'status' => StoreManagerAuthorizationCodeStatuses::class,
    ];

    public function getStoreManagerId(): int
    {
        return $this->store_manager_id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getExpiryDate(): ?Carbon
    {
        $expiryDate = Carbon::createFromFormat('Y-m-d H:i:s', $this->expiry_date);

        return $expiryDate ?: null;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->created_at;
    }

    public function getStatus(): StoreManagerAuthorizationCodeStatuses
    {
        return $this->status;
    }
}
