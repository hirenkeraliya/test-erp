<?php

declare(strict_types=1);

namespace App\Domains\MergeProductTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\MergeProductTransaction;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;

class MergeProductTransactionQueries
{
    public function addNew(User $user, int $oldProductId, int $newProductId): MergeProductTransaction
    {
        return MergeProductTransaction::create([
            'user_id' => $user->id,
            'user_type' => ModelMapping::getCaseName($user::class),
            'old_product_id' => $oldProductId,
            'new_product_id' => $newProductId,
        ]);
    }

    public function getBasicColumnsName(): string
    {
        return 'id,user_id,user_type,old_product_id,new_product_id';
    }

    public function getByOldProductId(array $productIds): Collection
    {
        return MergeProductTransaction::query()
            ->select('id', 'old_product_id', 'new_product_id')
            ->whereIntegerInRaw('old_product_id', $productIds)
            ->get();
    }
}
