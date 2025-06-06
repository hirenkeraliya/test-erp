<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Vendor\VendorQueries;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function getAllVendors(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        /** @var VendorQueries $vendorQueries */
        $vendorQueries = resolve(VendorQueries::class);

        return [
            'vendors' => $vendorQueries->getAllVendorByCompanyId($companyId),
        ];
    }
}
