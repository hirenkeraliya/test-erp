<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\PosAdvertisement\DataObjects\PosAdvertisementData;
use App\Domains\PosAdvertisement\Enums\PosAdvertisementTypes;
use App\Domains\PosAdvertisement\Exports\PosAdvertisementExport;
use App\Domains\PosAdvertisement\PosAdvertisementQueries;
use App\Domains\PosAdvertisement\Resources\AdminPosAdvertisementListResource;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PosAdvertisementController extends Controller
{
    public function __construct(
        protected PosAdvertisementQueries $posAdvertisementQueries
    ) {
    }

    public function index(): Response
    {
        $locationQueries = resolve(LocationQueries::class);

        return Inertia::render('pos_advertisement/Index', [
            'locations' => $locationQueries->getStoreWithBasicColumns(session('admin_company_id')),
            'posAdvertisementTypes' => PosAdvertisementTypes::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('pos_advertisement'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchPosAdvertisement(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->posAdvertisementQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminPosAdvertisementListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $locationQueries = resolve(LocationQueries::class);

        return Inertia::render('pos_advertisement/Manage', [
            'locations' => $locationQueries->getStoreWithBasicColumns(session('admin_company_id')),
            'posAdvertisementTypes' => PosAdvertisementTypes::getList(),
            'advertisementTypeImage' => PosAdvertisementTypes::IMAGE->value,
            'advertisementTypeVideo' => PosAdvertisementTypes::VIDEO->value,
        ]);
    }

    public function store(PosAdvertisementData $posAdvertisementData): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $this->posAdvertisementQueries->addNew($posAdvertisementData, session('admin_company_id'));

            DB::commit();

            return to_route('admin.pos_advertisements.index')
                ->with('success', 'Advertisement added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Pos Advertisement', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function edit(int $posAdvertisementId): Response
    {
        $locationQueries = resolve(LocationQueries::class);

        $posAdvertisement = $this->posAdvertisementQueries->getById(
            $posAdvertisementId,
            session('admin_company_id')
        );
        $posAdvertisement['photo_url'] = $posAdvertisement->type_id === PosAdvertisementTypes::IMAGE->value ? $posAdvertisement->getDiskBasedFirstMediaUrl(
            'photo'
        ) : null;

        $posAdvertisement['video_url'] = $posAdvertisement->type_id === PosAdvertisementTypes::VIDEO->value ? $posAdvertisement->getDiskBasedFirstMediaUrl(
            'video'
        ) : null;

        return Inertia::render('pos_advertisement/Manage', [
            'posAdvertisement' => $posAdvertisement,
            'locations' => $locationQueries->getStoreWithBasicColumns(session('admin_company_id')),
            'posAdvertisementTypes' => PosAdvertisementTypes::getList(),
            'advertisementTypeImage' => PosAdvertisementTypes::IMAGE->value,
            'advertisementTypeVideo' => PosAdvertisementTypes::VIDEO->value,
        ]);
    }

    public function update(PosAdvertisementData $posAdvertisementData, int $posAdvertisementId): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $this->posAdvertisementQueries->update(
                $posAdvertisementData,
                $posAdvertisementId,
                session('admin_company_id')
            );

            DB::commit();

            return to_route('admin.pos_advertisements.index')
                ->with('success', 'Advertisement updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Pos Advertisement', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function setStatus(int $posAdvertisementId, bool $status): RedirectResponse
    {
        $this->posAdvertisementQueries->adminSetStatus($posAdvertisementId, session('admin_company_id'), $status);

        return to_route('admin.pos_advertisements.index')->with('success', 'Status changed successfully.');
    }

    public function exportPosAdvertisement(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $posAdvertisements = $this->posAdvertisementQueries->getPosAdvertisementExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new PosAdvertisementExport($posAdvertisements), $filename);
    }
}
