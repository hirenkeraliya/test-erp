<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\VoucherConfiguration\DataObjects\StoreManagerApiVoucherConfigurationData;
use App\Domains\VoucherConfiguration\Resources\ApplicationVoucherConfigurationListResource;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VoucherConfigurationController extends Controller
{
    public function getVouchersConfiguration(
        Request $request,
        StoreManagerApiVoucherConfigurationData $storeManagerApiVoucherConfigurationData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $selectedDate = $storeManagerApiVoucherConfigurationData->selected_date ?: Carbon::now()->format('Y-m-d');

        $filterData = [
            'sort_by' => $storeManagerApiVoucherConfigurationData->sort_by,
            'sort_direction' => $storeManagerApiVoucherConfigurationData->sort_direction,
            'per_page' => $storeManagerApiVoucherConfigurationData->per_page,
            'selected_date' => $selectedDate,
            'search_text' => $storeManagerApiVoucherConfigurationData->search_text,
        ];

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherConfiguration = $voucherConfigurationQueries->getVouchersConfigurationForApplication(
            $filterData,
            $companyId
        );

        return [
            'data' => ApplicationVoucherConfigurationListResource::collection($voucherConfiguration->getCollection()),
            'total_records' => $voucherConfiguration->total(),
            'last_page' => $voucherConfiguration->lastPage(),
            'current_page' => $voucherConfiguration->currentPage(),
            'per_page' => $voucherConfiguration->perPage(),
        ];
    }
}
