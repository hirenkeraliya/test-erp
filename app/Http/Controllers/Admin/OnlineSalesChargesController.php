<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\OnlineSalesCharges\DataObjects\OnlineSalesChargesData;
use App\Domains\OnlineSalesCharges\Enums\ShippingChargeTypes;
use App\Domains\OnlineSalesCharges\OnlineSalesChargesQueries;
use App\Domains\OnlineSalesCharges\Resources\OnlineSalesChargesListResource;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\ShippingZone\ShippingZoneQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OnlineSalesChargesController extends Controller
{
    public function __construct(
        protected OnlineSalesChargesQueries $onlineSalesChargesQueries
    ) {
    }

    public function index(): Response
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->isAvailable(session('admin_company_id'));

        return Inertia::render('online_sales_charges/Index', [
            'saleChannel' => $saleChannel,
        ]);
    }

    public function fetchOnlineSalesCharges(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->onlineSalesChargesQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => OnlineSalesChargesListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('online_sales_charges/Manage', $this->commonData());
    }

    public function store(OnlineSalesChargesData $onlineSalesChargesData): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $this->onlineSalesChargesQueries->addNew($onlineSalesChargesData, session('admin_company_id'));

            DB::commit();

            return to_route('admin.online_sales_charges.index')->with(
                'success',
                'The online sales charge has been added successfully.'
            );
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Online Sales charge create error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);

            DB::rollBack();

            return to_route('admin.online_sales_charges.index')->with('error', 'Something went wrong.');
        }
    }

    public function edit(int $onlineSaleChargeId): Response
    {
        $commonData = $this->commonData();
        $commonData['onlineSalesCharge'] = $this->onlineSalesChargesQueries->getById(
            $onlineSaleChargeId,
            session('admin_company_id')
        );

        return Inertia::render('online_sales_charges/Manage', $commonData);
    }

    public function update(OnlineSalesChargesData $onlineSalesChargesData, int $onlineSaleChargeId): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $this->onlineSalesChargesQueries->update(
                $onlineSalesChargesData,
                $onlineSaleChargeId,
                session('admin_company_id')
            );

            DB::commit();

            return to_route('admin.online_sales_charges.index')->with(
                'success',
                'The online sales charge has been updated successfully.'
            );
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error([
                'error_name' => 'Online Sales charge update error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);

            return to_route('admin.online_sales_charges.index')->with('error', 'Something went wrong.');
        }
    }

    public function delete(int $onlineSaleChargeId): RedirectResponse
    {
        $this->onlineSalesChargesQueries->delete($onlineSaleChargeId, session('admin_company_id'));

        return to_route('admin.online_sales_charges.index')->with(
            'success',
            'Online sales charge deleted successfully.'
        );
    }

    public function toggleStatus(Request $request): void
    {
        $this->onlineSalesChargesQueries->toggleStatus($request->onlineSalesChargeId, session('admin_company_id'));
    }

    public function syncData(): void
    {
        // ToDo: Add Job For sync data
    }

    private function commonData(): array
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $shippingZoneQueries = resolve(ShippingZoneQueries::class);
        $saleChannels = $saleChannelQueries->getAllByCompanyId(session('admin_company_id'));

        return [
            'shippingChargeTypes' => ShippingChargeTypes::formattedForSelection(),
            'staticWeightType' => ShippingChargeTypes::WEIGHT->value,
            'saleChannels' => $saleChannels,
            'shippingZones' => $shippingZoneQueries->getAll(),
        ];
    }
}
