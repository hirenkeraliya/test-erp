<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Style\DataObjects\StyleData;
use App\Domains\Style\Exports\StyleExport;
use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StyleController extends Controller
{
    public function __construct(
        protected StyleQueries $styleQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('styles/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('style'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchStyles(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->styleQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(StyleData $styleData): RedirectResponse
    {
        $this->styleQueries->addNew($styleData, session('admin_company_id'));

        return to_route('admin.styles.index')->with('success', 'Style added successfully.');
    }

    public function storeAndReturn(StyleData $styleData): array
    {
        $style = $this->styleQueries->addNew($styleData, session('admin_company_id'));

        return [
            'style' => $style,
        ];
    }

    public function edit(int $styleId): Response
    {
        return Inertia::render('styles/Manage', [
            'style' => $this->styleQueries->getById($styleId, session('admin_company_id')),
        ]);
    }

    public function update(StyleData $styleData, int $styleId): RedirectResponse
    {
        $this->styleQueries->update($styleData, $styleId, session('admin_company_id'));

        return to_route('admin.styles.index')->with('success', 'Style updated successfully.');
    }

    public function exportStyles(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $styles = $this->styleQueries->getStylesExport($filterData, session('admin_company_id'));

        return Excel::download(new StyleExport($styles), $filename);
    }

    public function getFilteredStyles(Request $request): array
    {
        return [
            'styles' => $this->styleQueries->getFilteredStylesByCompanyId(
                $request->input('search_text'),
                session('admin_company_id')
            ),
        ];
    }

    /**
     * @return array<string, Collection>
     */
    public function getStylesList(): array
    {
        return [
            'styles' => $this->styleQueries->getWithBasicColumns(session('admin_company_id')),
        ];
    }

    public function getStyleSalesSummary(Request $request): array
    {
        $filterData = $request->all();
        $filterData['type'] = (int) $filterData['type'];
        $styles = $this->styleQueries->getStyleSalesSummary($filterData, session('admin_company_id'));

        return [
            'styles' => $styles,
            'total_sales' => $styles->sum('total_sales'),
            'total_units_sold' => $styles->sum('total_units_sold'),
        ];
    }
}
