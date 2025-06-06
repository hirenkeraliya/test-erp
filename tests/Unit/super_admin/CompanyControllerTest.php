<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\DataObjects\CompanyData;
use App\Domains\Company\DataObjects\CurrencyRateData;
use App\Domains\Company\Enums\CompanyStatuses;
use App\Domains\Company\Enums\LocationAssignmentTypes;
use App\Domains\Company\Jobs\ShareCompanyDetailsToPosAdminJob;
use App\Domains\Country\CountryQueries;
use App\Domains\CurrencyRate\CurrencyRateQueries;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Models\Company;
use App\Models\CurrencyRate;
use App\Models\ExternalConnection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

test('It calls the list query method of the company queries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'status' => CompanyStatuses::ACTIVE->value,
    ];

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $companyController = new CompanyController($companyQueries);

    $response = $companyController->fetchCompanies(new Request($requestParameter));
    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals('[]', $response['data']->toJson());
});

test('It calls addNew method of the company queries class', function (): void {
    Queue::fake();

    $companyData = companyDataArray();

    $companyRecord = new CompanyData(...$companyData);

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock) use ($companyRecord): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($companyRecord);
    });

    $companyController = new CompanyController($companyQueries);
    $redirectResponse = $companyController->store($companyRecord);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Company added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/companies', $redirectResponse->getTargetUrl());
    Queue::assertPushed(ShareCompanyDetailsToPosAdminJob::class);
});

test('It calls get by id method of the company queries class and return proper response', function (): void {
    $requestParameter = Company::factory()->make([
        'default_country_id' => 1,
    ])->toArray();

    $countryData = [
        'data' => [
            [
                'id' => 1,
                'name' => 'Country 1',
            ],
            [
                'id' => 2,
                'name' => 'Country 2',
            ],
            [
                'id' => 3,
                'name' => 'Country 3',
            ],
        ],
    ];
    $countries = collect($countryData);

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getByIdWithMediaAndBrands')
            ->once()
            ->with(1)
            ->andReturn(new Company($requestParameter));
    });

    $brandQueries = $this->mock(BrandQueries::class, function ($mock): void {
        $mock->shouldReceive('getWithBasicColumns')
            ->once()
            ->andReturn(new Collection([]));
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
            ->andReturn(new Collection([]));
    });

    $this->mock(CountryQueries::class, function ($mock) use ($countries): void {
        $mock->shouldReceive('getList')
            ->once()
            ->andReturn($countries);
    });

    $companyController = new CompanyController($companyQueries);
    $response = $companyController->edit(1, $brandQueries);
    $response->rootView('super_admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
            ->has(
                'company',
                fn (Assert $company): Assert => $company
                    ->where('name', $requestParameter['name'])
                    ->where('code', $requestParameter['code'])
                    ->where('grn_format', $requestParameter['grn_format'])
                    ->where('email', $requestParameter['email'])
                    ->etc()
            )
    );
});

test(
    'It calls archive method of the company queries class and sends requests to external connections',
    function (): void {
        $companyId = 1;

        $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('delete')
                ->once()
                ->with($companyId);
        });

        $this->mock(SaleChannelQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('setArchiveCompanyInactive')
                ->once()
                ->with($companyId, false);
        });

        $externalConnection = ExternalConnection::factory([
            'url' => 'https://example.com/',
        ])->make();

        $this->mock(ExternalConnectionQueries::class, function ($mock) use ($externalConnection): void {
            $mock->shouldReceive('getAll')
                ->once()
                ->andReturn(collect([$externalConnection]));
        });

        Http::fake([
            $externalConnection->url => Http::response([], 200),
        ]);

        $companyController = resolve(CompanyController::class);
        $redirectResponse = $companyController->archive($companyId);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Company archived successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('super-admin/companies', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls restore method of the company queries class and sends requests to external connections',
    function (): void {
        $companyId = 1;

        $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('restore')
                ->once()
                ->with($companyId);
        });

        $this->mock(SaleChannelQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('setRestoreCompanyActive')
                ->once()
                ->with($companyId, true);
        });

        $externalConnection = ExternalConnection::factory([
            'url' => 'https://example.com/',
        ])->make();

        $this->mock(ExternalConnectionQueries::class, function ($mock) use ($externalConnection): void {
            $mock->shouldReceive('getAll')
                ->once()
                ->andReturn(collect([$externalConnection]));
        });

        Http::fake([
            $externalConnection->url => Http::response([], 200),
        ]);

        $companyController = resolve(CompanyController::class);
        $redirectResponse = $companyController->restore($companyId);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Company restored successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('super-admin/companies', $redirectResponse->getTargetUrl());
    }
);

test('It calls update method of the company queries class', function (): void {
    Queue::fake();
    $companyData = companyDataArray();

    $companyRecord = new CompanyData(...$companyData);

    $companyData['brands'] = collect([
        [
            'id' => 1,
        ],
    ]);
    $brands = $companyData['brands'];

    unset($companyData['light_logo']);
    unset($companyData['dark_logo']);
    unset($companyData['email_footer_logo']);
    unset($companyData['brand_ids']);
    unset($companyData['country_ids']);
    unset($companyData['brands']);
    unset($companyData['company_setting']);

    $company = new Company($companyData);
    $company->brands = $brands;

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock) use ($company, $companyRecord): void {
        $mock->shouldReceive('getByIdWithBrands')
            ->once()
            ->with(1)
            ->andReturn($company);
        $mock->shouldReceive('update')
            ->once()
            ->with($companyRecord, 1);
    });

    $locationQueries = $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('hasBrands')
            ->once()
            ->with(1, [1])
            ->andReturn(false);
    });

    $companyController = new CompanyController($companyQueries);
    $redirectResponse = $companyController->update($companyRecord, 1, $locationQueries);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Company updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/companies', $redirectResponse->getTargetUrl());
    Queue::assertPushed(ShareCompanyDetailsToPosAdminJob::class);
});

test('It calls currencyRateUpdate method of the currency rate queries class', function (): void {
    $data = [
        'company_id' => 1,
        'currency_data' => [
            [
                'id' => 1,
                'rate' => 1.00,
            ],
        ],
    ];
    CurrencyRate::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'currency_id' => 1,
        'rate' => 1,
    ]);

    $currencyRateData = new CurrencyRateData(...$data);

    $this->mock(CurrencyRateQueries::class, function ($mock): void {
        $mock->shouldReceive('currencyRateUpdateByCompanyId')
            ->once();
    });

    $companyController = resolve(CompanyController::class);
    $redirectResponse = $companyController->currencyRateUpdate($currencyRateData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Currency rates updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/companies', $redirectResponse->getTargetUrl());
});

function companyDataArray(): array
{
    Storage::fake('public');

    $company = Company::factory()->make([
        'commission_type_id' => 1,
        'min_promoters_per_item' => 0,
        'is_bill_reference_number_mandatory' => 0,
        'default_location_id' => null,
        'location_assignment_type' => LocationAssignmentTypes::MANUAL_ASSIGNMENT->value,
        'allow_happy_hour_discount' => 1,
        'auto_include_in_collections' => true,
        'auto_include_in_member_group' => true,
        'creator_can_approve_draft_product' => false,
        'enable_e_invoice' => true,
        'show_e_invoice_qr_on_receipt' => true,
        'default_country_id' => 1,
        'loyalty_point_expiration_days' => 10,
    ])->toArray();

    $company['light_logo'] = UploadedFile::fake()->image('avatar.jpg');
    $company['dark_logo'] = UploadedFile::fake()->image('avatar2.jpg');
    $company['email_footer_logo'] = UploadedFile::fake()->image('avatar3.jpg');
    $company['brand_ids'] = [500];
    $company['country_ids'] = [1];
    $company['company_setting'] = [];

    return $company;
}
