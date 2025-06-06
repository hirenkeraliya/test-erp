<?php

declare(strict_types=1);

use App\Domains\Tag\DataObjects\TagData;
use App\Domains\Tag\TagQueries;
use App\Models\Company;
use App\Models\Tag;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->tagQueries = new TagQueries();
});

test('A tag can be added', function (): void {
    $tag = [
        'name' => 'ABC',
    ];
    $this->tagQueries->addNew(new TagData(...$tag), $this->companyA->id);
    $this->assertDatabaseHas('tags', $tag);
});

test('tags list can be fetched', function (): void {
    $tag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
    ]);

    $response = $this->tagQueries->getWithBasicColumns($this->companyA->id);

    expect($response[0])
        ->toHaveKey('id', $tag->id)
        ->toHaveKey('name', $tag->name);
});

test('getFilteredTagsByCompanyId method called to fetch the records', function (): void {
    $tag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
    ]);

    $response = $this->tagQueries->getFilteredTagsByCompanyId('ABCD', $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $tag->id)
        ->toHaveKey('name', $tag->name);
});

test('tags are returned  per page', function (): void {
    $tag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Test',
    ]);

    $response = $this->tagQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $tag->name);
});

test('A tag fetched by Id', function (): void {
    $tag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Test',
    ]);
    $response = $this->tagQueries->getById($tag->id, $this->companyA->id);
    expect($response->toArray())
        ->toHaveKey('name', $tag->name);
});

test('A tag can be updated', function (): void {
    $newTag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Test',
    ]);

    $tag = [
        'name' => 'ABC',
    ];

    $this->tagQueries->update(new TagData(...$tag), $newTag->id, $this->companyA->id);

    $this->assertDatabaseHas('tags', [
        'name' => $tag['name'],
        'company_id' => $this->companyA->id,
    ]);
});

test('getTagsExport method returns BinaryResponse', function (): void {
    $tag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Test',
    ]);

    $response = $this->tagQueries->getTagsExport([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $tag->id)
        ->toHaveKey('name', $tag->name);
});

test('it retrieves a collection of tags by their IDs for a specific company', function (): void {
    $tagId = Tag::factory()->create([
        'company_id' => $this->companyA->id,
    ])->id;

    $response = $this->tagQueries->getByIds([$tagId]);
    expect($response)->toBeInstanceOf(Collection::class);
});

test(
    'getIdByNameAndCompanyId method return category id',
    function (): void {
        $tag = Tag::factory()->create([
            'company_id' => $this->companyA->id,
        ]);
        $response = $this->tagQueries->getIdByNameAndCompanyId($tag->name, $this->companyA->id);
        $this->assertEquals($tag->id, $response);
    }
);

test('getTagNamesByIds method returns proper response', function (): void {
    $tag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
    ]);

    $response = $this->tagQueries->getTagNamesByIds($this->companyA->id, [$tag->id]);
    expect($response->toArray())
        ->toHaveKey('names', $tag->name);
});

test('Get Tags name for export PDF headers', function (): void {
    $newTag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Test',
    ]);

    $response = $this->tagQueries->getTagsNameForFilter([$newTag->id]);

    $this->assertIsString($response);
});
