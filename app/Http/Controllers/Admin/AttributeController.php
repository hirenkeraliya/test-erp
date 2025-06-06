<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Attribute\DataObjects\AttributeData;
use App\Domains\Attribute\DataObjects\AttributeOldData;
use App\Domains\Attribute\Enums\FieldType;
use App\Domains\Attribute\Resources\AttributeListResource;
use App\Domains\Attribute\Resources\AttributeResource;
use App\Domains\Template\TemplateQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class AttributeController extends Controller
{
    public function __construct(
        protected AttributeQueries $attributeQueries
    ) {
    }

    public function index(int $templateId): Response
    {
        $template = $this->getTemplateInformation($templateId, session('admin_company_id'));

        return Inertia::render('attributes/Index', [
            'templateId' => $templateId,
            'templateName' => $template['name'],
        ]);
    }

    private function getTemplateInformation(int $templateId, int $companyId): array
    {
        $templateQueries = resolve(TemplateQueries::class);

        $template = $templateQueries->selectTemplateName($templateId, $companyId);

        return $template->toArray();
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchAttributes(Request $request, int $templateId): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->attributeQueries->listQuery(
            $filterData,
            $templateId,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AttributeListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function fetchAttributeOptions(int $attributeId): array
    {
        $attribute = $this->attributeQueries->getAttributeOptions(session('admin_company_id'), $attributeId);

        $formattedAttribute = [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'options' => collect($attribute->options)->map(fn ($option): array => [
                'id' => $option,
                'name' => $option,
            ])->values(),
        ];

        return [
            'attributeOptions' => $formattedAttribute,
        ];
    }

    public function create(int $templateId): Response
    {
        $attributes = $this->attributeQueries->getAllExceptCurrentTemplate($templateId, session('admin_company_id'));
        $template = $this->getTemplateInformation($templateId, session('admin_company_id'));

        return Inertia::render('attributes/Manage', [
            'templateId' => $templateId,
            'templateName' => $template['name'],
            'isVariant' => $template['is_variant'],
            'fieldTypes' => FieldType::formattedForSelection(),
            'fieldTypeCases' => FieldType::getFormattedArrayForStaticUse(),
            'attributes' => $attributes,
        ]);
    }

    public function store(AttributeData $attributeData, int $templateId): RedirectResponse
    {
        $this->validateTemplateWithCompany($templateId, session('admin_company_id'));

        $this->attributeQueries->addNew($attributeData, $templateId, session('admin_company_id'));

        return to_route('admin.attributes.index', $templateId)
            ->with('success', 'Attribute added successfully.');
    }

    public function storeOld(AttributeOldData $attributeOldData, int $templateId): RedirectResponse
    {
        $attribute = $this->attributeQueries->getById($attributeOldData->attribute_id, session('admin_company_id'));

        $this->attributeQueries->attachTemplate($attribute, $templateId);

        return to_route('admin.attributes.index', $templateId)
            ->with('success', 'Attribute added successfully.');
    }

    public function edit(int $templateId, int $attributeId): Response
    {
        $attribute = $this->attributeQueries->getById($attributeId, session('admin_company_id'));

        $templateQueries = resolve(TemplateQueries::class);
        $template = $templateQueries->getById($templateId, session('admin_company_id'));

        return Inertia::render('attributes/Manage', [
            'attribute' => new AttributeResource($attribute),
            'templateId' => $template->id,
            'templateName' => $template->name,
            'fieldTypes' => FieldType::formattedForSelection(),
            'fieldTypeCases' => FieldType::getFormattedArrayForStaticUse(),
        ]);
    }

    public function update(AttributeData $attributeData, int $templateId, int $attributeId): RedirectResponse
    {
        $this->validateTemplateWithCompany($templateId, session('admin_company_id'));

        $this->attributeQueries->update($attributeData, $templateId, $attributeId, session('admin_company_id'));

        return to_route('admin.attributes.index', $templateId)
            ->with('success', 'Attribute updated successfully.');
    }

    public function delete(int $templateId, int $attributeId): RedirectResponse
    {
        $this->attributeQueries->delete($templateId, $attributeId, session('admin_company_id'));

        return to_route('admin.attributes.index', $templateId)->with('success', 'Attribute deleted successfully.');
    }

    private function validateTemplateWithCompany(int $templateId, int $companyId): void
    {
        $templateQueries = resolve(TemplateQueries::class);
        $templateQueries->selectTemplateId($templateId, $companyId);
    }
}
