<?php

declare(strict_types=1);

use App\Domains\Template\DataObjects\TemplateData;
use App\Domains\Template\TemplateQueries;
use App\Models\Attribute;
use App\Models\Company;
use App\Models\Template;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->templatesToCreate = 3;

    $this->templates = Template::factory($this->templatesToCreate)->create([
        'company_id' => $this->companyA->id,
        'is_variant' => false,
    ]);

    $this->templateQueries = new TemplateQueries();
});

test('templates list can be fetched', function (): void {
    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ];

    $response = $this->templateQueries->listQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe($this->templatesToCreate);
});

test('template can be created', function (): void {
    $template = [
        'name' => 'this is a template name',
        'description' => 'this is a template descr',
        'is_variant' => false,
    ];
    $this->templateQueries->addNew(new TemplateData(...$template), $this->companyA->id);
    $this->assertDatabaseHas('templates', $template);
});

test('A single template can be fetched', function (): void {
    $template = $this->templates->first();
    $response = $this->templateQueries->getById($template->id, $this->companyA->id);
    expect($response->toArray())
        ->toHaveKey('id', $template->id)
        ->toHaveKey('name', $template->name)
        ->toHaveKey('description', $template->description);
});

test('template can be updated', function (): void {
    $template = Template::factory()->makeOne()->toArray();
    $templateOne = $this->templates->first();
    unset($template['company_id']);

    $this->templateQueries->update(new TemplateData(...$template), $templateOne->id, $this->companyA->id);

    $this->assertDatabaseHas('templates', [
        'name' => $template['name'],
        'company_id' => $this->companyA->id,
    ]);
});

test('template can be deleted', function (): void {
    $templateOne = $this->templates->first();

    $this->templateQueries->delete($templateOne->id, $this->companyA->id);

    $this->assertSoftDeleted('templates', [
        'id' => $templateOne->id,
    ]);
});

test('A single template with only Id can be fetched', function (): void {
    $template = $this->templates->first();
    $response = $this->templateQueries->selectTemplateId($template->id, $this->companyA->id);
    expect($response->toArray())->toHaveKey('id', $template->id);
});

test('A single template with only name can be fetched', function (): void {
    $template = $this->templates->first();
    $response = $this->templateQueries->selectTemplateName($template->id, $this->companyA->id);
    expect($response->toArray())->toHaveKey('name', $template->name);
});

test('fetch templates for dropdown', function (): void {
    $template = $this->templates->first();
    $response = $this->templateQueries->fetchForDropdown($this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $template->id)
        ->toHaveKey('name', $template->name);
});

test('fetches attributes by template', function (): void {
    $template = $this->templates->first();
    $attributes = Attribute::factory(3)->create([]);
    $template->attributes()->attach($attributes->pluck('id')->toArray());
    setCompanyIdInSession($this->companyA->id);
    $response = $this->templateQueries->fetchAttributesByTemplate($template->id, $this->companyA->id);

    $firstAttribute = $response['attributes'][0];
    $expectedFirstAttribute = $attributes[0];

    expect($response)
        ->toHaveKey('id', $template->id)
        ->toHaveKey('name', $template->name)
        ->toHaveKey('attributes');

    expect($firstAttribute)
        ->toHaveKey('id', $expectedFirstAttribute->id)
        ->toHaveKey('name', $expectedFirstAttribute->name);
});

test('getAllTemplates returns the templates details', function (): void {
    $templateDeleted = Template::factory()->create([
        'company_id' => $this->companyA->id,
        'is_variant' => false,
    ]);

    $templateDeleted->delete();
    $templateDeleted->save();

    $template = $this->templates->first();

    $response = $this->templateQueries->getAllTemplatesByCompanyId($this->companyA->id);

    expect($response->count())->toBe($this->templatesToCreate);
    expect($response->toArray()[0])->toHaveKey('id', $template->id);
});

test('createDefaultTemplateAndAttributes creates default template with attributes', function (): void {
    $response = $this->templateQueries->createDefaultTemplateAndAttributes($this->companyA->id);

    expect($response)
        ->toHaveKey('id')
        ->toHaveKey('name', 'Color & Size & Style')
        ->toHaveKey('is_variant', true);

    expect($response->attributes)->toHaveCount(3);

    $attributes = $response->attributes->pluck('name')->toArray();
    expect($attributes)->toContain('Color', 'Size', 'Style');

    $colorAttribute = $response->attributes->firstWhere('name', 'Color');
    expect($colorAttribute)
        ->toHaveKey('options', ['NO COLOR'])
        ->toHaveKey('is_required', true);

    $this->assertDatabaseHas('templates', [
        'company_id' => $this->companyA->id,
        'name' => 'Color & Size & Style',
        'is_variant' => true,
        'description' => 'Default Template',
    ]);

    $this->assertDatabaseHas('attributes', [
        'company_id' => $this->companyA->id,
        'name' => 'Color',
        'description' => 'Color Attribute',
        'is_required' => true,
    ]);

    $this->assertDatabaseCount('attribute_template', 3);
});
