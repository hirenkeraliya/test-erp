<?php

declare(strict_types=1);

namespace App\Domains\Attribute;

use App\Domains\Attribute\DataObjects\AttributeData;
use App\Domains\Attribute\Enums\FieldType;
use App\Domains\Template\TemplateQueries;
use App\Models\Attribute;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttributeQueries
{
    public function listQuery(array $filterData, int $templateId, int $companyId): LengthAwarePaginator
    {
        $templateQueries = resolve(TemplateQueries::class);

        return Attribute::query()
            ->select('id', 'name', 'field_type')
            ->whereHas('templates', $templateQueries->filterById($templateId, $companyId))
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereAny(['name', 'field_type'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->paginate($filterData['per_page']);
    }

    public function templateAttributeListQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return Attribute::query()
            ->select('id', 'name', 'field_type')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhereIntegerInRaw('field_type', FieldType::getMatchingCases($filterData['search_text']));
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->paginate($filterData['per_page']);
    }

    public function addNew(AttributeData $attributeData, int $templateId, int $companyId): void
    {
        $data = $attributeData->all();
        $data['company_id'] = $companyId;
        if ($data['field_type'] == FieldType::MULTISELECT->value) {
            $data['default_value'] = json_encode($data['default_value']);
        }

        $attribute = Attribute::create($data);
        $attribute->templates()->attach($templateId);
    }

    public function addTemplateAttributeNew(AttributeData $attributeData, int $companyId): void
    {
        $data = $attributeData->all();
        $data['company_id'] = $companyId;
        if ($data['field_type'] == FieldType::MULTISELECT->value) {
            $data['default_value'] = json_encode($data['default_value']);
        }

        Attribute::create($data);
    }

    public function getAllExceptCurrentTemplate(int $templateId, int $companyId): Collection
    {
        $templateQueries = resolve(TemplateQueries::class);
        $template = $templateQueries->getById($templateId, $companyId);

        $attributeIds = Attribute::select('id')
            ->whereHas('templates', $templateQueries->filterById($templateId, $companyId))
            ->pluck('id');

        if ($template->is_variant) {
            return Attribute::select('id', 'name')
                ->whereIntegerNotInRaw('id', $attributeIds)
                ->where('field_type', FieldType::SELECT->value)
                ->get();
        }

        return Attribute::select('id', 'name')
            ->whereIntegerNotInRaw('id', $attributeIds)
            ->get();
    }

    public function getById(int $attributeId, int $companyId): Attribute
    {
        return Attribute::select(
            'id',
            'name',
            'description',
            'field_type',
            'default_value',
            'from',
            'to',
            'options',
            'is_required',
        )
            ->where('company_id', $companyId)
            ->findOrFail($attributeId);
    }

    public function getNameById(int $attributeId): Attribute
    {
        return Attribute::select('id', 'name')->findOrFail($attributeId);
    }

    public function getAll(int $companyId): Collection
    {
        return Attribute::select('id', 'name', 'options')
            ->where('company_id', $companyId)
            ->get();
    }

    public function attachTemplate(Attribute $attribute, int $templateId): void
    {
        $attribute->templates()->attach($templateId);
    }

    private function getOnlyModel(int $templateId, int $attributeId, int $companyId): Attribute
    {
        $templateQueries = resolve(TemplateQueries::class);

        return Attribute::whereHas('templates', function ($query) use (
            $templateId,
            $companyId,
            $templateQueries
        ): void {
            $query->select('id')
                ->where($templateQueries->filterById($templateId, $companyId));
        })
        ->findOrFail($attributeId);
    }

    public function update(AttributeData $attributeData, int $templateId, int $attributeId, int $companyId): void
    {
        $attribute = $this->getOnlyModel($templateId, $attributeId, $companyId);
        $attribute->update($attributeData->all());
    }

    public function updateTemplateAttribute(AttributeData $attributeData, int $attributeId, int $companyId): void
    {
        $attribute = $this->getById($attributeId, $companyId);
        $attribute->update($attributeData->all());
    }

    public function delete(int $templateId, int $attributeId, int $companyId): void
    {
        $templateQueries = resolve(TemplateQueries::class);
        $template = $templateQueries->getById($templateId, $companyId);

        $template->attributes()->detach($attributeId);
    }

    public function deleteTemplateAttribute(int $attributeId, int $companyId): void
    {
        $attribute = $this->getById($attributeId, $companyId);
        $attribute->delete();
    }

    public function doesAttributeExist(int $templateId, int $attributeId, int $companyId): bool
    {
        $templateQueries = resolve(TemplateQueries::class);

        return Attribute::whereHas('templates', $templateQueries->filterById($templateId, $companyId))
            ->where('id', $attributeId)
            ->exists();
    }

    public function doesAttributeExistInTemplate(int $attributeId, int $companyId): bool
    {
        return Attribute::where('id', $attributeId)
            ->where('company_id', $companyId)
            ->whereHas('templates', function ($query) use ($attributeId): void {
                $query->where('attribute_id', $attributeId);
            })
            ->exists();
    }

    public function fetchAttribute(int $templateId, int $attributeId, int $companyId): Attribute
    {
        $templateQueries = resolve(TemplateQueries::class);

        return Attribute::whereHas('templates', $templateQueries->filterById($templateId, $companyId))
            ->findOrFail($attributeId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,is_required';
    }

    public function getColumnsForProductCollection(): string
    {
        return 'id,name,options';
    }

    public function getAllColumns(): string
    {
        return 'id,name,description,field_type,options,default_value,from,to,is_required';
    }

    public function getBasicColumnsForProduct(): string
    {
        return 'id,template_id,name,field_type,default_value,from,to,options,is_required';
    }

    public function getAttributes(int $companyId): Collection
    {
        return Attribute::select('id', 'name', 'options')
            ->whereHas('templates', function ($query) use ($companyId): void {
                $query->select('id')
                    ->where('company_id', $companyId)
                    ->where('is_variant', true);
            })
            ->where('company_id', $companyId)
            ->get();
    }

    public function getByCompanyId(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        return Attribute::select('id', 'name', 'options')
            ->whereHas('templates', function ($query) use ($companyId): void {
                $query->select('id')
                    ->where('company_id', $companyId)
                    ->where('is_variant', true);
            })
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->where('company_id', $companyId)
            ->get();
    }

    public function getAttributeOptions(int $companyId, int $attributeId): Attribute
    {
        return Attribute::select('id', 'name', 'options')
            ->where('company_id', $companyId)
            ->where('id', $attributeId)
            ->firstOrFail();
    }

    public function firstOrCreate(array $attributeData, int $companyId, ?int $templateId): int
    {
        $attribute = Attribute::where('name', $attributeData['name'])
            ->where('company_id', $companyId)
            ->first();

        if (! $attribute) {
            $attribute = Attribute::create([
                'company_id' => $companyId,
                'name' => $attributeData['name'],
                'description' => $attributeData['description'],
                'field_type' => $attributeData['field_type'],
                'default_value' => $attributeData['default_value'],
                'from' => $attributeData['from'],
                'to' => $attributeData['to'],
                'options' => $attributeData['options'],
                'is_required' => $attributeData['is_required'],
            ]);
            $attribute->templates()->attach($templateId);
        }

        return $attribute->id;
    }

    public function getColumnNamesForRelation(): string
    {
        return 'id,name,field_type,default_value,from,to,options,is_required';
    }

    public function getAllAttributesByCompanyId(int $companyId): Collection
    {
        return Attribute::where('company_id', $companyId)
            ->select(
                'id',
                'company_id',
                'name',
                'description',
                'field_type',
                'default_value',
                'from',
                'to',
                'options',
                'is_required'
            )
            ->get();
    }
}
