<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Http\Controllers\Api\Integration\CompanyController;
use App\Models\Integration;
use Illuminate\Http\Request;

test('It calls the getAllCompanies method of the companyQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $companiesData = [
        [
            'id' => 100,
            'name' => 'TEST',
            'code' => '123',
            'legal_name' => 'Test Company',
            'employer_identification_number' => 123,
            'social_security_number' => 123,
            'address' => 'test xyz',
            'email' => 'test@test.com',
        ],
    ];

    $this->mock(CompanyQueries::class, function ($mock) use ($companiesData): void {
        $mock->shouldReceive('getAllCompanies')
            ->once()
            ->andReturn(collect($companiesData));
    });

    $companyController = new CompanyController();
    $response = $companyController->getAllCompanies();

    expect($response['companies']->first())->toHaveKeys([
        'id',
        'name',
        'code',
        'legal_name',
        'employer_identification_number',
        'social_security_number',
        'address',
        'email',
    ]);
});
