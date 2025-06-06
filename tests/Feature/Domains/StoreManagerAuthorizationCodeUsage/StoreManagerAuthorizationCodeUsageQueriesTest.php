<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCodeUsage\Enum\StoreManagerAuthorizationCodeUsageTypes;
use App\Domains\StoreManagerAuthorizationCodeUsage\StoreManagerAuthorizationCodeUsageQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Sale;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
use App\Models\StoreManagerAuthorizationCodeUsage;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->employeeA = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'ABCD',
    ]);

    $this->storeManager = StoreManager::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE->value,
    ]);

    $this->storeManagerAuthorizationCodeUsageQueries = new StoreManagerAuthorizationCodeUsageQueries();
});

test('cancelTheAuthorizationCode updates the status as cancel as expected', function (): void {
    $sale = Sale::factory()->create();
    $this->storeManagerAuthorizationCodeUsageQueries->addNew([
        'store_manager_authorization_code_id' => $this->storeManagerAuthorizationCode->id,
        'usage_type_id' => StoreManagerAuthorizationCodeUsageTypes::CREDIT_SALE->value,
        'reference_id' => $sale->id,
        'reference_type' => ModelMapping::SALE->name,
    ]);

    $this->assertDatabaseHas(StoreManagerAuthorizationCodeUsage::class, [
        'store_manager_authorization_code_id' => $this->storeManagerAuthorizationCode->id,
        'usage_type_id' => StoreManagerAuthorizationCodeUsageTypes::CREDIT_SALE->value,
        'reference_id' => $sale->id,
        'reference_type' => ModelMapping::SALE->name,
    ]);
});
