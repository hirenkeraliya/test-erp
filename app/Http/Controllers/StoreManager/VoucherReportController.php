<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Domains\Voucher\Exports\VoucherExport;
use App\Domains\Voucher\Resources\VoucherListResource;
use App\Domains\Voucher\Resources\VoucherTransactionDetailResource;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoucherReportController extends Controller
{
    public function __construct(
        protected VoucherQueries $voucherQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('reports/voucher/Index', [
            'discountTypePercentage' => DiscountTypes::PERCENTAGE->value,
            'voucherStatusTypes' => VoucherStatusTypes::formattedForSelection(),
            'exportPermission' => PermissionList::getExportPermissionName('voucher'),
            'voucherStatusStaticArray' => [
                'active' => VoucherStatusTypes::getFormattedCaseName(VoucherStatusTypes::ACTIVE->value),
                'used' => VoucherStatusTypes::getFormattedCaseName(VoucherStatusTypes::USED->value),
                'expired' => VoucherStatusTypes::getFormattedCaseName(VoucherStatusTypes::EXPIRED->value),
            ],
            'helpCenterMessages' => 'The vouchers report offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchVouchers(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'member_id' => $request->get('member_id'),
            'status_type' => $request->get('status_type'),
        ];

        $lengthAwarePaginator = $this->voucherQueries->getPaginatedVoucherListForStoreManager(
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => VoucherListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportVouchers(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'date_range' => $request->get('date_range'),
            'member_id' => $request->get('member_id'),
            'status_type' => $request->get('status_type'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $vouchers = $this->voucherQueries->getVouchersForExportStoreManager(
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        return Excel::download(new VoucherExport($vouchers, $filteredColumns), $filename);
    }

    /**
     * @return array<string, VoucherTransactionDetailResource>
     */
    public function fetchVoucherTransactionDetails(int $voucherId): array
    {
        $voucherTransactionDetails = $this->voucherQueries->fetchVoucherTransactionDetails(
            $voucherId,
            session('store_manager_selected_location_company_id')
        );

        return [
            'voucherTransactionDetails' => new VoucherTransactionDetailResource($voucherTransactionDetails),
        ];
    }

    public function printVoucherTransactionDetails(int $voucherId): string
    {
        $companyId = session('store_manager_selected_location_company_id');
        /** @var Voucher $voucherDetails */
        $voucherDetails = $this->voucherQueries->fetchVoucherTransactionDetails($voucherId, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $columns = ['Date', 'Receipt Number', 'Location ( Code )', 'Action Type'];

        $voucherDetails = $this->prepareVoucherDetailsData($voucherDetails);

        return view('prints.voucher_details', [
            'voucherDetails' => $voucherDetails,
            'columns' => $columns,
            'company' => $company,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }

    private function prepareVoucherDetailsData(Voucher $voucher): array
    {
        /** @var Collection $voucherTransactions */
        $voucherTransactions = $voucher->voucherTransactions;

        $voucherDetails = $voucherTransactions->map(function (VoucherTransaction $voucherTransaction): array {
            $voucherTransactionStore = $voucherTransaction->location;
            $voucherSale = $voucherTransaction->sale;
            $voucherOrder = $voucherTransaction->order;
            $offlineId = 'N/A';

            if ($voucherSale) {
                $offlineId = $voucherSale->offline_sale_id . ' (' . SaleStatus::getFormattedCaseName(
                    $voucherSale->status
                ) . ')';
            }

            if ($voucherOrder) {
                $offlineId = $voucherOrder->receipt_number. ' (' . $voucherOrder->status?->name . ')';
            }

            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $voucherTransaction->happened_at);

            return [
                'date' => $date->format('d-m-Y h:i:s A'),
                'offline_sale_id' => $offlineId,
                'location' => $voucherTransactionStore ? $voucherTransactionStore->name . ' (' . $voucherTransactionStore->code . ')' : 'N/A',
                'action_type' => $voucherTransaction->action_type_id ? VoucherTransactionActionTypes::getFormattedCaseName(
                    $voucherTransaction->action_type_id
                ) : null,
            ];
        });

        return $voucherDetails->toArray();
    }
}
