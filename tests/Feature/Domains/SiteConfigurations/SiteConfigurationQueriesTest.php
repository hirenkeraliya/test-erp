<?php

declare(strict_types=1);

use App\Domains\SiteConfiguration\DataObjects\SiteConfigurationData;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Models\SiteConfiguration;

beforeEach(function (): void {
    $this->siteConfigurationA = SiteConfiguration::factory()->create([
        'type_id' => SiteConfigurationTypes::THEME->value,
        'value' => ThemeColors::PINK->value,
    ]);

    $this->siteConfigurationQueries = new SiteConfigurationQueries();
});

test('site configuration can be searched', function (): void {
    $response = $this->siteConfigurationQueries->listQuery([
        'search_text' => 'theme',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('type_id', $this->siteConfigurationA->type_id->value);
});

test('site configuration as per page', function (): void {
    $response = $this->siteConfigurationQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ]);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('type_id', $this->siteConfigurationA->type_id->value)
        ->toHaveKey('value', $this->siteConfigurationA->value);
});

test('A site configuration can be fetched', function (): void {
    $response = $this->siteConfigurationQueries->getById($this->siteConfigurationA->id);

    expect($response->toArray())
        ->toHaveKey('type_id', $this->siteConfigurationA->type_id->value)
        ->toHaveKey('value', $this->siteConfigurationA->value);
});

test('A site configuration can be updated', function (): void {
    $this->siteConfigurationQueries->update(
        new SiteConfigurationData(
            SiteConfigurationTypes::THEME->value,
            ThemeColors::PINK->value,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        ),
        $this->siteConfigurationA->id
    );

    $this->assertDatabaseHas('site_configurations', [
        'type_id' => SiteConfigurationTypes::THEME->value,
        'value' => ThemeColors::PINK->value,
    ]);
});
