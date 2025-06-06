<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Tag\DataObjects\TagData;
use App\Domains\Tag\DataObjects\TagExport;
use App\Domains\Tag\TagQueries;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TagController extends Controller
{
    public function __construct(
        protected TagQueries $tagQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('tags/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('tag'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function store(TagData $tagData): array
    {
        $tag = $this->tagQueries->addNew($tagData, session('admin_company_id'));

        return [
            'id' => $tag->getKey(),
            'name' => $tag->getName(),
        ];
    }

    public function getFilteredTags(Request $request): array
    {
        return [
            'tags' => $this->tagQueries->getFilteredTagsByCompanyId(
                $request->input('search_text'),
                session('admin_company_id')
            ),
        ];
    }

    public function fetchTags(Request $request): array
    {
        $filterData = [
            'per_page' => $request->get('per_page'),
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
        ];

        $lengthAwarePaginator = $this->tagQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function edit(int $tagId): Response
    {
        return Inertia::render('tags/Manage', [
            'tag' => $this->tagQueries->getById($tagId, session('admin_company_id')),
        ]);
    }

    public function update(TagData $tagData, int $tagId): RedirectResponse
    {
        $this->tagQueries->update($tagData, $tagId, session('admin_company_id'));

        return to_route('admin.tags.index')->with('success', 'Tag updated successfully.');
    }

    public function exportTags(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'per_page' => $request->get('per_page'),
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
        ];

        $tags = $this->tagQueries->getTagsExport($filterData, session('admin_company_id'));

        return Excel::download(new TagExport($tags), $filename);
    }

    /**
     * @return array<string, Collection>
     */
    public function getTagsList(): array
    {
        return [
            'tags' => $this->tagQueries->getWithBasicColumns(session('admin_company_id')),
        ];
    }
}
