<?php

declare(strict_types=1);

namespace App\Domains\BoxProductLoyaltyPoint;

use App\Models\BoxProduct;
use App\Models\BoxProductLoyaltyPoint;

class BoxProductLoyaltyPointQueries
{
    public function addNew(array $boxProductLoyaltyPointRecords): void
    {
        BoxProductLoyaltyPoint::create($boxProductLoyaltyPointRecords);
    }

    public function deleteBoxProductLoyaltyPoints(BoxProduct $boxProduct): void
    {
        $boxProduct->boxProductLoyaltyPoints()->delete();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,box_product_id,membership_id,points';
    }
}
