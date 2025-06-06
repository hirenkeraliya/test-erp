<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;
use App\Models\Director;
use App\Models\StoreManager;

enum AuthorizerTypes: string
{
    use PrepareEnumDataMethods;

    case DIRECTOR = 'Director';
    case STORE_MANAGER = 'Store Manager';

    public static function getAuthorizerTypeClass(string $authorizerType): string
    {
        if ($authorizerType === self::DIRECTOR->value) {
            return Director::class;
        }

        return StoreManager::class;
    }
}
