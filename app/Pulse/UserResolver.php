<?php

declare(strict_types=1);

namespace App\Pulse;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\Cashier;
use App\Models\Member;
use App\Models\Promoter;
use App\Models\SaleChannel;
use App\Models\StoreManager;
use App\Models\SuperAdmin;
use App\Models\WarehouseManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Laravel\Pulse\Contracts\ResolvesUsers;

class UserResolver implements ResolvesUsers
{
    protected array $resolvedUsers;

    public function load(Collection $keys): self
    {
        $keys = $keys->map(fn ($key): array => explode(':', (string) $key));

        $this->resolvedUsers = [
            ModelMapping::ADMIN->value => Admin::findMany($keys
                ->filter(fn ($key): bool => $key[0] === ModelMapping::ADMIN->value)
                ->map(fn ($key): string => $key[1])->values()),
            ModelMapping::SUPER_ADMIN->value => SuperAdmin::findMany($keys
                ->filter(fn ($key): bool => $key[0] === ModelMapping::SUPER_ADMIN->value)
                ->map(fn ($key): string => $key[1])->values()),
            ModelMapping::STORE_MANAGER->value => StoreManager::findMany($keys
                ->filter(fn ($key): bool => $key[0] === ModelMapping::STORE_MANAGER->value)
                ->map(fn ($key): string => $key[1])->values()),
            ModelMapping::WAREHOUSE_MANAGER->value => WarehouseManager::findMany($keys
                ->filter(fn ($key): bool => $key[0] === ModelMapping::WAREHOUSE_MANAGER->value)
                ->map(fn ($key): string => $key[1])->values()),
            ModelMapping::CASHIER->value => Cashier::findMany($keys
                ->filter(fn ($key): bool => $key[0] === ModelMapping::CASHIER->value)
                ->map(fn ($key): string => $key[1])->values()),
            ModelMapping::MEMBER->value => Member::findMany($keys
                ->filter(fn ($key): bool => $key[0] === ModelMapping::MEMBER->value)
                ->map(fn ($key): string => $key[1])->values()),
            ModelMapping::PROMOTER->value => Promoter::findMany($keys
                ->filter(fn ($key): bool => $key[0] === ModelMapping::PROMOTER->value)
                ->map(fn ($key): string => $key[1])->values()),
            ModelMapping::SALE_CHANNEL->value => SaleChannel::findMany($keys
                ->filter(fn ($key): bool => $key[0] === ModelMapping::SALE_CHANNEL->value)
                ->map(fn ($key): string => $key[1])->values()),
        ];

        return $this;
    }

    public function find(int|string|null $key): object
    {
        [$class, $id] = explode(':', (string) $key);

        $user = $this->resolvedUsers[$class]->first(fn ($user): bool => (int) $id === $user->id);

        return match ($user::class) {
            $user::class => (object) [
                'name' => property_exists($user, 'username') && $user->username ? $user->username : $user->name,
                'extra' => ModelMapping::getFormattedCaseName($user::class),
                'avatar' => '',
            ],
            default => (object) [
                'name' => '',
                'extra' => '',
                'avatar' => '',
            ],
        };
    }

    public function key(Authenticatable $user): int|string|null
    {
        return $user::class.':'.$user->getAuthIdentifier();
    }
}
