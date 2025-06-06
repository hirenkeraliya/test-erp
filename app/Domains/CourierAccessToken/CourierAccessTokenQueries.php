<?php

declare(strict_types=1);

namespace App\Domains\CourierAccessToken;

use App\Models\CourierAccessToken;

class CourierAccessTokenQueries
{
    public function getByCourierId(int $courierId): ?CourierAccessToken
    {
        return CourierAccessToken::select('id', 'courier_id', 'access_token')
            ->where('courier_id', $courierId)
            ->first();
    }

    public function addNew(int $courierId, string $token): string
    {
        return CourierAccessToken::updateOrCreate([
            'courier_id' => $courierId,
        ], [
            'access_token' => $token,
        ])->access_token;
    }
}
