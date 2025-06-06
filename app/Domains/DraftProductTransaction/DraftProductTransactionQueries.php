<?php

declare(strict_types=1);

namespace App\Domains\DraftProductTransaction;

use App\Models\DraftProductTransaction;

class DraftProductTransactionQueries
{
    public function addNew(array $draftProductData): void
    {
        DraftProductTransaction::create($draftProductData);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,product_id,approved_by_id,approved_by_type';
    }
}
