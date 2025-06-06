<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Http\Controllers\Api\ExternalConnection\CompanyController;

test('getCompanies method calls the getList method of CompanyQueries calls', function (): void {
    $return = collect([]);
    $this->mock(CompanyQueries::class, function ($mock) use ($return): void {
        $mock->shouldReceive('getList')
            ->once()
            ->andReturn($return);
    });

    $companyController = new CompanyController();
    $response = $companyController->getCompanies();
    $this->assertEquals($response, $return);
});
