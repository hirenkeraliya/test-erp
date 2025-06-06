<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Vendor\VendorQueries;
use App\Http\Controllers\Controller;

class VendorController extends Controller
{
    public function __construct(
        protected VendorQueries $vendorQueries
    ) {
    }

    public function getVendorsList(): array
    {
        return [
            'vendors' => $this->vendorQueries->getWithBasicColumns(
                session('warehouse_manager_selected_location_company_id')
            ),
        ];
    }
}
