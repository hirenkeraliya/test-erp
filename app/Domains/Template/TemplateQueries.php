<?php

declare(strict_types=1);

namespace App\Domains\Template;

use App\Domains\Attribute\Enums\FieldType;
use App\Domains\Template\DataObjects\TemplateData;
use App\Models\Attribute;
use App\Models\Template;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TemplateQueries
{
    public function getColumnNamesForRelation(): string
    {
        return 'id,name,description,is_variant';
    }

    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return Template::query()
            ->select('id', 'name', 'description')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->whereAny(['name', 'description'], 'Like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->paginate($filterData['per_page']);
    }

    public function addNew(TemplateData $templateData, int $companyId): Template
    {
        $data = $templateData->all();
        $data['company_id'] = $companyId;

        return Template::create($data);
    }

    public function getById(int $templateId, int $companyId): Template
    {
        return Template::where('company_id', $companyId)
            ->select('id', 'name', 'description', 'is_variant')
            ->findOrFail($templateId);
    }

    public function selectTemplateId(int $templateId, int $companyId): Template
    {
        return Template::where('company_id', $companyId)
            ->select('id', 'company_id', 'name', 'description', 'is_variant')
            ->findOrFail($templateId);
    }

    public function filterById(int $templateId, int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('id', $templateId)->where('company_id', $companyId);
    }

    public function filterByIsVariant(bool $isVariant, int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('is_variant', $isVariant)->where('company_id', $companyId);
    }

    public function selectTemplateName(int $templateId, int $companyId): Template
    {
        return Template::where('company_id', $companyId)
            ->select('name', 'is_variant')
            ->findOrFail($templateId);
    }

    public function update(TemplateData $templateData, int $templateId, int $companyId): void
    {
        $template = $this->selectTemplateId($templateId, $companyId);
        $template->update($templateData->all());
    }

    public function delete(int $templateId, int $companyId): void
    {
        $template = $this->selectTemplateId($templateId, $companyId);
        $template->delete();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function fetchForDropdown(int $companyId): Collection
    {
        return Template::where('company_id', $companyId)
            ->select('id', 'name')
            ->where('is_variant', false)
            ->get();
    }

    public function fetchForVariantDropdown(int $companyId): Collection
    {
        return Template::where('company_id', $companyId)
            ->select('id', 'name')
            ->where('is_variant', true)
            ->get();
    }

    public function fetchAttributesByTemplate(int $templateId, int $companyId): ?Template
    {
        return Template::with([
            'attributes' => function ($query): void {
                $query
                ->select('id', 'name', 'field_type', 'default_value', 'from', 'to', 'options', 'is_required');
            },
        ])
          ->select('id', 'name')
          ->where('company_id', $companyId)
          ->findOrFail($templateId);
    }

    public function getIdByNameAndCompanyId(array $templateData, int $companyId): int
    {
        return Template::firstOrCreate(
            [
                'name' => $templateData['name'],
                'company_id' => $companyId,
            ],
            [
                'description' => $templateData['description'],
                'is_variant' => true,
            ]
        )->id;
    }

    public function getAllTemplatesByCompanyId(int $companyId): Collection
    {
        return Template::where('company_id', $companyId)
            ->select('id', 'company_id', 'name', 'description', 'is_variant')
            ->with('attributes:id')
            ->get();
    }

    public function getDefaultTemplateWithAttribute(int $companyId): ?Template
    {
        return Template::with([
            'attributes' => function ($query): void {
                $query
                    ->select('id', 'name', 'default_value', 'is_required');
            },
        ])
            ->select('id', 'name')
            ->where('company_id', $companyId)
            ->first();
    }

    public function createDefaultTemplateAndAttributes(int $companyId): Template
    {
        $template = Template::firstOrCreate([
            'company_id' => $companyId,
            'name' => 'Color & Size & Style',
            'is_variant' => true,
        ], [
            'description' => 'Default Template',
        ]);

        $colorAttribute = Attribute::firstOrCreate([
            'name' => 'Color',
            'company_id' => $companyId,
        ], [
            'description' => 'Color Attribute',
            'field_type' => FieldType::SELECT->value,
            'options' => ['NO COLOR'],
            'is_required' => true,
        ]);

        $sizeAttribute = Attribute::firstOrCreate([
            'name' => 'Size',
            'company_id' => $companyId,
        ], [
            'description' => 'Size Attribute',
            'field_type' => FieldType::SELECT->value,
            'options' => ['NO SIZE'],
            'is_required' => true,
        ]);

        $styleAttribute = Attribute::firstOrCreate([
            'name' => 'Style',
            'company_id' => $companyId,
        ], [
            'description' => 'Style Attribute',
            'field_type' => FieldType::SELECT->value,
            'options' => ['NO STYLE'],
            'is_required' => true,
        ]);

        $template->attributes()->sync([$colorAttribute->id, $sizeAttribute->id, $styleAttribute->id]);

        $template->fresh();

        return $template->load([
            'attributes' => function ($query): void {
                $query->select('id', 'name', 'default_value', 'options', 'is_required');
            },
        ]);
    }
}
