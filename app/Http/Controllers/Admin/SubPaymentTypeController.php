<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\PaymentType\Enums\PaymentTypeImages;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SubPaymentType\DataObjects\SubPaymentTypeData;
use App\Domains\SubPaymentType\Exports\SubPaymentTypeExport;
use App\Domains\SubPaymentType\SubPaymentTypeQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubPaymentTypeController extends Controller
{
    public function __construct(
        protected SubPaymentTypeQueries $subPaymentTypeQueries
    ) {
    }

    public function index(int $paymentTypeId): Response
    {
        return Inertia::render('sub_payment_types/Index', [
            'paymentTypeId' => $paymentTypeId,
            'exportPermission' => PermissionList::getExportPermissionName('payment_type'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchSubPaymentTypes(Request $request, int $paymentTypeId): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->subPaymentTypeQueries->listQuery(
            $filterData,
            $paymentTypeId,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(int $paymentTypeId): Response
    {
        return Inertia::render('sub_payment_types/Manage', [
            'paymentTypeId' => $paymentTypeId,
            'subPaymentTypeImages' => PaymentTypeImages::formattedForSelection(),
        ]);
    }

    public function store(SubPaymentTypeData $subPaymentTypeData, int $paymentTypeId): RedirectResponse
    {
        $this->subPaymentTypeQueries->addNew($subPaymentTypeData, $paymentTypeId, session('admin_company_id'));

        return to_route('admin.sub_payment_types.index', $paymentTypeId)->with(
            'success',
            'The sub payment type has been successfully added.'
        );
    }

    public function setStatus(int $paymentTypeId, int $subPaymentTypeId, bool $status): RedirectResponse
    {
        $this->subPaymentTypeQueries->setStatus($subPaymentTypeId, session('admin_company_id'), $status);

        return to_route('admin.sub_payment_types.index', $paymentTypeId)->with(
            'success',
            'Status changed successfully.'
        );
    }

    public function edit(int $paymentTypeId, int $subPaymentTypeId): Response
    {
        return Inertia::render('sub_payment_types/Manage', [
            'subPaymentType' => $this->subPaymentTypeQueries->getById(
                $paymentTypeId,
                $subPaymentTypeId,
                session('admin_company_id')
            ),
            'paymentTypeId' => $paymentTypeId,
            'subPaymentTypeImages' => PaymentTypeImages::formattedForSelection(),
        ]);
    }

    public function update(
        SubPaymentTypeData $subPaymentTypeData,
        int $paymentTypeId,
        int $subPaymentTypeId
    ): RedirectResponse {
        $this->subPaymentTypeQueries->update(
            $subPaymentTypeData,
            $paymentTypeId,
            $subPaymentTypeId,
            session('admin_company_id')
        );

        return to_route('admin.sub_payment_types.index', $paymentTypeId)->with(
            'success',
            'The sub payment type has been successfully updated.'
        );
    }

    public function exportSubPaymentTypes(Request $request, int $paymentTypeId, string $filename): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $subPaymentTypes = $this->subPaymentTypeQueries->getSubPaymentTypesExport(
            $filterData,
            $paymentTypeId,
            session('admin_company_id')
        );

        return Excel::download(new SubPaymentTypeExport($subPaymentTypes), $filename);
    }
}
