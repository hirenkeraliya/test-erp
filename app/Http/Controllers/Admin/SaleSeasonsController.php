<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\SaleSeason\DataObjects\SaleSeasonData;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleSeasonsController extends Controller
{
    public function __construct(
        protected SaleSeasonQueries $saleSeasonQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('sale_seasons/Index');
    }

    public function fetchSaleSeasons(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->saleSeasonQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('sale_seasons/Manage');
    }

    public function store(SaleSeasonData $saleSeasonData): RedirectResponse
    {
        $this->saleSeasonQueries->addNew($saleSeasonData, session('admin_company_id'));

        return to_route('admin.sale_seasons.index')->with('success', 'The sale season has been added successfully.');
    }

    public function edit(int $saleSeasonId): Response
    {
        return Inertia::render('sale_seasons/Manage', [
            'saleSeason' => $this->saleSeasonQueries->getById($saleSeasonId, session('admin_company_id')),
        ]);
    }

    public function update(SaleSeasonData $saleSeasonData, int $saleSeasonId): RedirectResponse
    {
        $this->saleSeasonQueries->update($saleSeasonData, $saleSeasonId, session('admin_company_id'));

        return to_route('admin.sale_seasons.index')->with('success', 'The sale season has been updated successfully.');
    }

    public function delete(int $saleSeasonId): RedirectResponse
    {
        $this->saleSeasonQueries->delete($saleSeasonId, session('admin_company_id'));

        return to_route('admin.sale_seasons.index')->with('success', 'Sale Season deleted successfully.');
    }
}
