<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Attribute\DataObjects\AttributeData;
use App\Domains\Attribute\Enums\FieldType;
use App\Domains\Attribute\Resources\AttributeListResource;
use App\Domains\Attribute\Resources\AttributeResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class TemplateAttributeController extends Controller
{
    public function __construct(
        protected AttributeQueries $attributeQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('template_attributes/Index');
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchTemplateAttributes(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->attributeQueries->templateAttributeListQuery(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AttributeListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('template_attributes/Manage', [
            'fieldTypes' => FieldType::formattedForSelection(),
            'fieldTypeCases' => FieldType::getFormattedArrayForStaticUse(),
        ]);
    }

    public function store(AttributeData $attributeData): RedirectResponse
    {
        $this->attributeQueries->addTemplateAttributeNew($attributeData, session('admin_company_id'));

        return to_route('admin.template_attributes.index')->with('success', 'Attribute added successfully.');
    }

    public function edit(int $attributeId): Response
    {
        $attribute = $this->attributeQueries->getById($attributeId, session('admin_company_id'));

        return Inertia::render('template_attributes/Manage', [
            'attribute' => new AttributeResource($attribute),
            'fieldTypes' => FieldType::formattedForSelection(),
            'fieldTypeCases' => FieldType::getFormattedArrayForStaticUse(),
        ]);
    }

    public function update(AttributeData $attributeData, int $attributeId): RedirectResponse
    {
        $this->attributeQueries->updateTemplateAttribute($attributeData, $attributeId, session('admin_company_id'));

        return to_route('admin.template_attributes.index')->with('success', 'Attribute updated successfully.');
    }

    public function delete(int $attributeId): RedirectResponse
    {
        $attribute = $this->attributeQueries->doesAttributeExistInTemplate($attributeId, session('admin_company_id'));
        if ($attribute) {
            return back()->with(
                'error',
                'Cannot delete this attribute because it is associated with one or more templates.'
            );
        }

        $this->attributeQueries->deleteTemplateAttribute($attributeId, session('admin_company_id'));

        return to_route('admin.template_attributes.index')->with('success', 'Attribute deleted successfully.');
    }
}
