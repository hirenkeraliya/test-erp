<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\PaymentType\DataObjects\PaymentTypeData;
use App\Domains\PaymentType\Enums\PaymentRestrictionTypes;
use App\Domains\PaymentType\Enums\PaymentTypeImages;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\Exports\PaymentTypeBulkUpdateExport;
use App\Domains\PaymentType\Exports\PaymentTypeExport;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\ShippingZone\ShippingZoneQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentTypeController extends Controller
{
    public function __construct(
        protected PaymentTypeQueries $paymentTypeQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('payment_types/Index', [
            'staticPaymentTypes' => collect(StaticPaymentTypes::formattedForSelection())->pluck('id')->toArray(),
            'exportPermission' => PermissionList::getExportPermissionName('payment_type'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchPaymentTypes(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->paymentTypeQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('payment_types/Manage', $this->commonData());
    }

    public function store(PaymentTypeData $paymentTypeData): RedirectResponse
    {
        $this->paymentTypeQueries->addNew($paymentTypeData, session('admin_company_id'));

        return to_route('admin.payment_types.index')->with('success', 'Payment Type added successfully.');
    }

    public function setStatus(int $paymentTypeId, bool $status): RedirectResponse
    {
        $isStaticPaymentType = $this->checkForStaticPaymentType($paymentTypeId);

        if ($isStaticPaymentType) {
            return to_route('admin.payment_types.index')->with(
                'error',
                'The status of static payment types cannot be changed.'
            );
        }

        $this->paymentTypeQueries->setStatus($paymentTypeId, session('admin_company_id'), $status);

        return to_route('admin.payment_types.index')->with('success', 'Status changed successfully.');
    }

    public function edit(int $paymentTypeId): Response
    {
        $commonData = $this->commonData();
        $commonData['paymentType'] = $this->paymentTypeQueries->getById($paymentTypeId, session('admin_company_id'));

        return Inertia::render('payment_types/Manage', $commonData);
    }

    public function update(PaymentTypeData $paymentTypeData, int $paymentTypeId): RedirectResponse
    {
        $this->paymentTypeQueries->update($paymentTypeData, $paymentTypeId, session('admin_company_id'));

        return to_route('admin.payment_types.index')->with('success', 'Payment Type updated successfully.');
    }

    public function paymentTypesExport(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $paymentTypes = $this->paymentTypeQueries->getPaymentTypesExport($filterData, session('admin_company_id'));

        return Excel::download(new PaymentTypeExport($paymentTypes), $filename);
    }

    public function exportBulkUpdatePaymentTypes(): BinaryFileResponse
    {
        $paymentTypes = $this->paymentTypeQueries->getActivePaymentTypesForBulkUpdate(session('admin_company_id'));

        return Excel::download(new PaymentTypeBulkUpdateExport($paymentTypes), 'payment-type-bulk-update.xlsx');
    }

    private function checkForStaticPaymentType(int $staticPaymentTypeId): bool
    {
        return StaticPaymentTypes::getCasesValue()->contains($staticPaymentTypeId);
    }

    private function commonData(): array
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $shippingZoneQueries = resolve(ShippingZoneQueries::class);

        $saleChannels = $saleChannelQueries->getAllByCompanyId(session('admin_company_id'));
        $shippingZones = $shippingZoneQueries->getAll();

        return [
            'paymentTypeImages' => PaymentTypeImages::formattedForSelection(),
            'saleChannels' => $saleChannels,
            'shippingZones' => $shippingZones,
            'paymentRestrictionTypes' => PaymentRestrictionTypes::formattedForSelection(),
        ];
    }
}
