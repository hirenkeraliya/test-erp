<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\LoyaltyCampaign\DataObjects\StoreManagerApiLoyaltyCampaignData;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Domains\LoyaltyCampaign\Resources\ApplicationLoyaltyCampaignListResource;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LoyaltyCampaignController extends Controller
{
    public function getLoyaltyCampaigns(
        Request $request,
        StoreManagerApiLoyaltyCampaignData $storeManagerApiLoyaltyCampaignData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $selectedDate = $storeManagerApiLoyaltyCampaignData->selected_date ?: Carbon::now()->format('Y-m-d');

        $filterData = [
            'sort_by' => $storeManagerApiLoyaltyCampaignData->sort_by,
            'sort_direction' => $storeManagerApiLoyaltyCampaignData->sort_direction,
            'per_page' => $storeManagerApiLoyaltyCampaignData->per_page,
            'selected_date' => $selectedDate,
            'search_text' => $storeManagerApiLoyaltyCampaignData->search_text,
        ];

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);
        $loyaltyCampaignQueries = resolve(LoyaltyCampaignQueries::class);
        $loyaltyCampaigns = $loyaltyCampaignQueries->getLoyaltyCampaignsForApplication($filterData, $companyId);

        return [
            'data' => ApplicationLoyaltyCampaignListResource::collection($loyaltyCampaigns->getCollection()),
            'total_records' => $loyaltyCampaigns->total(),
            'last_page' => $loyaltyCampaigns->lastPage(),
            'current_page' => $loyaltyCampaigns->currentPage(),
            'per_page' => $loyaltyCampaigns->perPage(),
        ];
    }
}
