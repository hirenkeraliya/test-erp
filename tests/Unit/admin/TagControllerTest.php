<?php

declare(strict_types=1);

use App\Domains\Tag\DataObjects\TagData;
use App\Domains\Tag\TagQueries;
use App\Http\Controllers\Admin\TagController;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the addNew method of the tag queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession();

    $tag = [
        'name' => 'ABC',
    ];

    $tagData = new TagData(...$tag);

    $tagQueries = $this->mock(TagQueries::class, function ($mock) use ($tagData, $companyId, $tag): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($tagData, $companyId)
            ->andReturn(new Tag($tag));
    });

    $tagController = new TagController($tagQueries);
    $response = $tagController->store($tagData);
    $this->assertEquals('ABC', $response['name']);
});

test('It calls the getFilteredTags method to fetch the records', function (): void {
    $companyId = 1;

    setCompanyIdInSession();

    $tag = [
        'name' => 'ABC',
    ];

    $tagQueries = $this->mock(TagQueries::class, function ($mock) use ($tag): void {
        $mock->shouldReceive('getFilteredTagsByCompanyId')
            ->once()
            ->andReturn(collect($tag));
    });

    $tagController = new TagController($tagQueries);
    $response = $tagController->getFilteredTags(new Request([
        'search_text' => 'ABC',
    ]));

    $this->assertEquals('ABC', $response['tags']['name']);
});

test('It calls the fetchTags method and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
    ];

    $tagQueries = $this->mock(TagQueries::class, function ($mock): void {
        $mock->shouldReceive('listQuery')
            ->andReturn(new LengthAwarePaginator([], 50, 15))
            ->once();
    });

    $tagController = new TagController($tagQueries);
    $response = $tagController->fetchTags(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls edit method and return proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = Tag::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $tagQueries = $this->mock(TagQueries::class, function ($mock) use ($companyId, $requestParameter): void {
        $mock->shouldReceive('getById')
            ->with(1, $companyId)
            ->andReturn(new Tag($requestParameter))
            ->once();
    });

    $tagController = new TagController($tagQueries);
    $response = $tagController->edit(1);

    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has('tag', fn (Assert $tag): Assert => $tag->where('name', $requestParameter['name'])->etc())
    );
});

test('It calls update method queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $tag = [
        'name' => 'ABC',
    ];

    $tagData = new TagData(...$tag);

    $tagQueries = $this->mock(TagQueries::class, function ($mock) use ($tagData, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($tagData, 1, $companyId);
    });

    $tagController = new TagController($tagQueries);
    $response = $tagController->update($tagData, 1);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Tag updated successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/tags', $response->getTargetUrl());
});

test('It calls the exportTags method and export as .csv and .xlsx', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
    ];

    $tagQueries = $this->mock(TagQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getTagsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Tag()));
    });

    $tagController = new TagController($tagQueries);
    $response = $tagController->exportTags('test.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
