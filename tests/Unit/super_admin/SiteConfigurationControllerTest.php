<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\SiteConfiguration\DataObjects\SiteConfigurationData;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\SuperAdmin\SiteConfigurationController;
use App\Models\SiteConfiguration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

test(
    'It calls the List query method of the site configuration queries class and returns proper response',
    function (): void {
        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $siteConfigurationQueries = $this->mock(SiteConfigurationQueries::class, function ($mock) use (
            $requestParameter
        ): void {
            $mock->shouldReceive('listQuery')
        ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $siteConfigurationController = new SiteConfigurationController($siteConfigurationQueries);

        $response = $siteConfigurationController->fetch(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertTrue($response['data']->resource->isEmpty());
    }
);

test(
    'It calls the get by id method of the site configuration queries class and returns proper response',
    function (): void {
        $requestParameter = [
            'type_id' => SiteConfigurationTypes::THEME->value,
            'value' => null,
        ];

        $siteConfigurationQueries = $this->mock(SiteConfigurationQueries::class, function ($mock) use (
            $requestParameter
        ): void {
            $mock->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn(new SiteConfiguration($requestParameter));

            $mock->shouldReceive('getAll')
                ->once()
                ->andReturn(new Collection());
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithIdAndName')
                ->once()
                ->andReturn(new Collection());
        });

        $siteConfigurationController = new SiteConfigurationController($siteConfigurationQueries);
        $response = $siteConfigurationController->edit(1);
        $response->rootView('super_admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
            ->has(
                'siteConfiguration',
                fn (Assert $siteConfiguration): Assert => $siteConfiguration->where(
                    'type_id',
                    SiteConfigurationTypes::THEME->value
                )->where('theme_color', null)
                ->where('id', null)
                ->where('favicon_icon_url', null)
                ->where('login_page_logo_url', null)
                ->where('login_page_tagline', null)
                ->where('login_page_sub_tagline', null)
                ->where('default_company', null)
                ->etc()
            )
        );
    }
);

test('It can call update site configuration method of site configuration queries class', function (): void {
    $siteConfigurationData = new SiteConfigurationData(
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
    );

    $siteConfigurationQueries = $this->mock(SiteConfigurationQueries::class, function ($mock) use (
        $siteConfigurationData
    ): void {
        $mock->shouldReceive('update')
        ->once()
            ->with($siteConfigurationData, 1);
    });

    $siteConfigurationController = new SiteConfigurationController($siteConfigurationQueries);
    $redirectResponse = $siteConfigurationController->update($siteConfigurationData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Site Configuration updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/site-configurations', $redirectResponse->getTargetUrl());
});
