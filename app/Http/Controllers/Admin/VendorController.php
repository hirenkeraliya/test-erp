<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\CommonFunctions;
use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Vendor\DataObjects\VendorData;
use App\Domains\Vendor\Exports\VendorExport;
use App\Domains\Vendor\VendorQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class VendorController extends Controller
{
    public function __construct(
        protected VendorQueries $vendorQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('vendors/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('vendor'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchVendors(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->vendorQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(VendorData $vendorData): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $this->vendorQueries->addNew($vendorData, session('admin_company_id'));
            DB::commit();

            return to_route('admin.vendors.index')
            ->with('success', 'Vendor added successfully.');
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Admin Vendor Create');

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function edit(int $vendorId): Response
    {
        $vendor = $this->vendorQueries->getById($vendorId, session('admin_company_id'));

        return Inertia::render('vendors/Manage', [
            'vendor' => $vendor,
        ]);
    }

    public function update(VendorData $vendorData, int $vendorId): RedirectResponse
    {
        $this->vendorQueries->update($vendorData, $vendorId, session('admin_company_id'));

        return to_route('admin.vendors.index')
            ->with('success', 'Vendor updated successfully.');
    }

    public function getVendorsList(): array
    {
        return [
            'vendors' => $this->vendorQueries->getWithBasicColumns(session('admin_company_id')),
        ];
    }

    public function exportVendors(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $vendors = $this->vendorQueries->getVendorsExport($filterData, session('admin_company_id'));

        return Excel::download(new VendorExport($vendors), $filename);
    }

    public function resendVerificationEmail(int $vendorId): RedirectResponse
    {
        $vendor = $this->vendorQueries->getByIdForEmailVerification($vendorId, session('admin_company_id'));
        EmailVerificationJob::dispatch($vendor)->delay(now()->addSeconds(5))->onQueue('high');

        return to_route('admin.vendors.index')
            ->with('success', 'The verification mail sent successfully.');
    }
}
