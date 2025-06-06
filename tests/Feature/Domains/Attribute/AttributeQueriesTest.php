<?php

declare(strict_types=1);

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Attribute\DataObjects\AttributeData;
use App\Domains\Attribute\Enums\FieldType;
use App\Models\Attribute;
use App\Models\Company;
use App\Models\Template;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->templateA = Template::factory()->create([
        'company_id' => $this->companyA->id,
        'is_variant' => true,
    ]);

    $this->attributesToCreate = 3;
    $this->attributes = Attribute::factory($this->attributesToCreate)->create([
        'company_id' => $this->companyA->id,
        'field_type' => FieldType::SELECT->value,
    ]);
    $this->templateA->attributes()->attach($this->attributes->pluck('id')->toArray());
    $this->attributeQueries = new AttributeQueries();
});

test('attributes list can be fetched', function (): void {
    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ];

    $response = $this->attributeQueries->listQuery($filterData, $this->templateA->id, $this->companyA->id);

    expect($response->total())->toBe($this->attributesToCreate);
});

test('template attributes list can be fetched', function (): void {
    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ];

    $response = $this->attributeQueries->templateAttributeListQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe($this->attributesToCreate);
});

test('attribute can be created', function (): void {
    $attribute = [
        'default_value' => 'default text here',
        'description' => null,
        'field_type' => 4,
        'from' => null,
        'is_required' => true,
        'name' => 'Text',
        'options' => null,
        'to' => null,
    ];
    $this->attributeQueries->addNew(new AttributeData(...$attribute), $this->templateA->id, $this->companyA->id);
    $this->assertDatabaseHas('attributes', $attribute);
});

test('template attribute can be created', function (): void {
    $attribute = [
        'default_value' => 'default text here',
        'description' => null,
        'field_type' => 4,
        'from' => null,
        'is_required' => true,
        'name' => 'Text',
        'options' => null,
        'to' => null,
    ];
    $this->attributeQueries->addTemplateAttributeNew(new AttributeData(...$attribute), $this->companyA->id);
    $this->assertDatabaseHas('attributes', $attribute);
});

test('A single attribute can be fetched', function (): void {
    $attribute = $this->attributes->first();
    $response = $this->attributeQueries->getById($attribute->id, $this->companyA->id);
    expect($response->toArray())
        ->toHaveKey('id', $attribute->id)
        ->toHaveKey('name', $attribute->name);
});

test('getAllExceptCurrentTemplate method call and return proper response', function (): void {
    $attribute = $this->attributes->first();
    $template = Template::factory()->create();
    $response = $this->attributeQueries->getAllExceptCurrentTemplate($template->id, $template->company_id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $attribute->id)
        ->toHaveKey('name', $attribute->name);
});

test('attribute can be updated', function (): void {
    $attribute = $this->attributes->first()->toArray();
    unset($attribute['updated_at']);
    unset($attribute['created_at']);
    unset($attribute['deleted_at']);
    unset($attribute['company_id']);
    $attributeId = $attribute['id'];
    unset($attribute['id']);
    $this->attributeQueries->update(
        new AttributeData(...$attribute),
        $this->templateA->id,
        $attributeId,
        $this->companyA->id
    );

    $this->assertDatabaseHas('attributes', [
        'name' => $attribute['name'],
    ]);
});

test('template attribute can be updated', function (): void {
    $attribute = $this->attributes->first()->toArray();
    unset($attribute['updated_at']);
    unset($attribute['created_at']);
    unset($attribute['deleted_at']);
    unset($attribute['company_id']);
    $attributeId = $attribute['id'];
    unset($attribute['id']);
    $this->attributeQueries->updateTemplateAttribute(
        new AttributeData(...$attribute),
        $attributeId,
        $this->companyA->id
    );

    $this->assertDatabaseHas('attributes', [
        'name' => $attribute['name'],
    ]);
});

test('attribute can be deleted', function (): void {
    $attribute = $this->attributes->first();

    $this->attributeQueries->delete($this->templateA->id, $attribute->id, $this->companyA->id);

    $this->assertFalse($this->templateA->attributes()->where('id', $attribute->id)->exists());
});

test('template attribute can be deleted', function (): void {
    $attribute = $this->attributes->first();

    $this->attributeQueries->deleteTemplateAttribute($attribute->id, $this->companyA->id);

    $this->assertSoftDeleted('attributes', [
        'id' => $attribute->id,
    ]);
});

test('attribute of a specific template and company exists', function (): void {
    $attribute = $this->attributes->first();

    $response = $this->attributeQueries->doesAttributeExist($this->templateA->id, $attribute->id, $this->companyA->id);

    expect($response)->toBeTrue();
});

test('getAttributes method call and return proper response', function (): void {
    $attribute = $this->attributes->first();
    $template = Template::factory()->create([
        'company_id' => $this->companyA->id,
    ]);
    $response = $this->attributeQueries->getAttributes($template->company_id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $attribute->id)
        ->toHaveKey('name', $attribute->name);
});

test('doesAttributeExistInTemplate method call and return proper response', function (): void {
    $attribute = $this->attributes->first();

    $response = $this->attributeQueries->doesAttributeExistInTemplate($attribute->id, $this->companyA->id);

    expect($response)->toBeTrue();
});

test('getAllAttributes returns the attributes details', function (): void {
    $attributeDeleted = Attribute::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $attributeDeleted->delete();
    $attributeDeleted->save();

    $attribute = $this->attributes->first();

    $response = $this->attributeQueries->getAllAttributesByCompanyId($this->companyA->id);

    expect($response->count())->toBe($this->attributesToCreate);
    expect($response->toArray()[0])->toHaveKey('id', $attribute->id);
});
