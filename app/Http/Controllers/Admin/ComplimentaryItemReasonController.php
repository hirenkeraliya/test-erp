<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use App\Domains\ComplimentaryItemReason\DataObjects\ComplimentaryItemReasonData;
use App\Domains\ComplimentaryItemReason\Exports\ComplimentaryItemReasonExport;
use App\Domains\ComplimentaryItemReason\Resources\AdminComplimentaryItemReasonListResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ComplimentaryItemReasonController extends Controller
{
    public function __construct(
        protected ComplimentaryItemReasonQueries $complimentaryItemReasonQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('complimentary_item_reasons/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('complimentary_setup'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchComplimentaryItemReasons(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->complimentaryItemReasonQueries->listQuery(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminComplimentaryItemReasonListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function store(ComplimentaryItemReasonData $complimentaryItemReasonData): RedirectResponse
    {
        $this->complimentaryItemReasonQueries->addNew($complimentaryItemReasonData, session('admin_company_id'));

        return to_route('admin.complimentary_item_reasons.index')
            ->with('success', 'The reason for adding the complimentary item has been successfully added.');
    }

    public function edit(int $complimentaryItemReasonId): Response
    {
        $complimentaryItemReason = $this->complimentaryItemReasonQueries->getById(
            $complimentaryItemReasonId,
            session('admin_company_id')
        );

        return Inertia::render('complimentary_item_reasons/Manage', [
            'complimentaryItemReason' => $complimentaryItemReason,
        ]);
    }

    public function update(
        ComplimentaryItemReasonData $complimentaryItemReasonData,
        int $complimentaryItemReasonId
    ): RedirectResponse {
        $this->complimentaryItemReasonQueries->update(
            $complimentaryItemReasonData,
            $complimentaryItemReasonId,
            session('admin_company_id')
        );

        return to_route('admin.complimentary_item_reasons.index')
            ->with('success', 'The reason for the complimentary item has been updated successfully.');
    }

    public function exportComplimentaryItemReasons(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $complimentaryItemReasons = $this->complimentaryItemReasonQueries->getComplimentaryItemReasonsExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new ComplimentaryItemReasonExport($complimentaryItemReasons), $filename);
    }
}
