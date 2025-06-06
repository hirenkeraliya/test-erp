<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Template\DataObjects\TemplateData;
use App\Domains\Template\TemplateQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function __construct(
        protected TemplateQueries $templateQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('templates/Index');
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchTemplates(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->templateQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(TemplateData $templateData): RedirectResponse
    {
        $this->templateQueries->addNew($templateData, session('admin_company_id'));

        return to_route('admin.templates.index')->with('success', 'Template added successfully.');
    }

    public function edit(int $templateId): Response
    {
        return Inertia::render('templates/Manage', [
            'template' => $this->templateQueries->getById($templateId, session('admin_company_id')),
        ]);
    }

    public function update(TemplateData $templateData, int $templateId): RedirectResponse
    {
        $this->templateQueries->update($templateData, $templateId, session('admin_company_id'));

        return to_route('admin.templates.index')->with('success', 'Template updated successfully.');
    }

    public function delete(int $templateId): RedirectResponse
    {
        $this->templateQueries->delete($templateId, session('admin_company_id'));

        return to_route('admin.templates.index')->with('success', 'Template deleted successfully.');
    }
}
