<?php

declare(strict_types=1);

use App\Domains\Tag\TagQueries;
use App\Http\Controllers\WarehouseManager\TagController;
use Illuminate\Http\Request;

test('It calls the getFilteredTags method to fetch the records', function (): void {
    $companyId = 1;

    setWarehouseManagerWarehouseCompanyIdInSession();

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
