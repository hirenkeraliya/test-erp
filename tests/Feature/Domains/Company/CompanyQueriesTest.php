<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\DataObjects\CompanyData;
use App\Domains\Company\Enums\CompanyStatuses;
use App\Domains\Company\Enums\LocationAssignmentTypes;
use App\Models\Brand;
use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Nnjeim\World\Models\Country;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'name' => 'WXYZ',
        'code' => 'ZYXW',
    ]);
    $this->companyB = Company::factory()->create([
        'name' => 'ABCD',
        'code' => 'DCBA',
    ]);

    $this->brandA = Brand::factory()->create();

    $this->companyA->brands()->sync($this->brandA->id);

    $this->companyQueries = new CompanyQueries();
});

test('Companies can be searched', function (): void {
    $response = $this->companyQueries->listQuery([
        'search_text' => 'WX',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => CompanyStatuses::ACTIVE->value,
    ]);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->companyA->name)
        ->toHaveKey('code', $this->companyA->code);
});

test('Companies can be filtered by status', function (string $status): void {
    $this->companyB->delete();

    $response = $this->companyQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => $status,
    ]);

    if ($status === CompanyStatuses::ACTIVE->value) {
        $this->assertEquals(1, $response->total());

        expect($response->getCollection()->first()->trashed())->toBeFalse();
        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('name', $this->companyA->name)
            ->toHaveKey('code', $this->companyA->code);
    } elseif ($status === CompanyStatuses::ARCHIVED->value) {
        $this->assertEquals(1, $response->total());

        expect($response->getCollection()->first()->trashed())->toBeTrue();
        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('name', $this->companyB->name)
            ->toHaveKey('code', $this->companyB->code);
    } elseif ($status === CompanyStatuses::ALL->value) {
        $this->assertEquals(2, $response->total());

        expect($response->getCollection()->pluck('id')->toArray())
            ->toContain($this->companyA->id)
            ->toContain($this->companyB->id);

        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('name', $response->getCollection()->first()->name)
            ->toHaveKey('code', $response->getCollection()->first()->code);
    }
})->with([CompanyStatuses::ACTIVE->value, CompanyStatuses::ARCHIVED->value, CompanyStatuses::ALL->value]);

test('Companies can be sorted by name', function (): void {
    $response = $this->companyQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'name',
        'sort_direction' => 'asc',
        'per_page' => 15,
        'status' => CompanyStatuses::ACTIVE->value,
    ]);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->companyB->name)
        ->toHaveKey('code', $this->companyB->code);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->companyA->name)
        ->toHaveKey('code', $this->companyA->code);
});

test('Companies are returned as per page', function (): void {
    $response = $this->companyQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'status' => CompanyStatuses::ACTIVE->value,
    ]);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->companyB->name)
        ->toHaveKey('email', $this->companyB->email);
});

test('A company can be stored', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $uploadedFile2 = UploadedFile::fake()->image('avatar2.jpg');
    $uploadedFile3 = UploadedFile::fake()->image('avatar3.jpg');
    $brand = Brand::factory()->create();

    $countriesData = [
        [
            'name' => 'Country 1',
        ],
    ];

    foreach ($countriesData as $countryData) {
        $countryId = Country::create($countryData)->id;
    }

    $companyArray = Company::factory([
        'commission_type_id' => 1,
        'min_promoters_per_item' => 0,
        'is_bill_reference_number_mandatory' => 0,
        'yearly_target' => 100,
        'location_assignment_type' => LocationAssignmentTypes::MANUAL_ASSIGNMENT->value,
        'default_location_id' => null,
        'allow_happy_hour_discount' => 1,
        'auto_include_in_collections' => true,
        'creator_can_approve_draft_product' => false,
        'enable_e_invoice' => false,
        'show_e_invoice_qr_on_receipt' => false,
        'default_country_id' => $countryId,
        'loyalty_point_expiration_days' => 10,
        'auto_include_in_member_group' => true,
    ])->make()->toArray();
    $companyArray['light_logo'] = $uploadedFile;
    $companyArray['dark_logo'] = $uploadedFile2;
    $companyArray['email_footer_logo'] = $uploadedFile3;
    $companyArray['brand_ids'] = [$brand->id];
    $companyArray['country_ids'] = [$countryId];
    $companyArray['company_setting'] = [
        'credit_sale_use_cashback' => true,
        'credit_sale_redeem_loyalty_points' => true,
        'credit_sale_earn_loyalty_points' => true,
        'credit_sale_redeem_vouchers' => true,
        'credit_sale_generate_vouchers' => true,
        'credit_sale_cart_wide_automatic_promotions' => true,
        'credit_sale_cart_wide_manual_promotions' => true,
        'credit_sale_item_wise_automatic_promotions' => true,
        'credit_sale_item_wise_manual_promotions' => true,
        'credit_sale_complimentary_item' => true,
        'credit_sale_manual_cart_discount' => true,
        'credit_sale_manual_item_discount' => true,
        'credit_sale_happy_hour_discount' => true,
        'credit_sale_allow_multi_currency_in_payment' => true,

        'layaway_sale_use_cashback' => true,
        'layaway_sale_redeem_loyalty_points' => true,
        'layaway_sale_earn_loyalty_points' => true,
        'layaway_sale_redeem_vouchers' => true,
        'layaway_sale_generate_vouchers' => true,
        'layaway_sale_cart_wide_automatic_promotions' => true,
        'layaway_sale_cart_wide_manual_promotions' => true,
        'layaway_sale_item_wise_automatic_promotions' => true,
        'layaway_sale_item_wise_manual_promotions' => true,
        'layaway_sale_complimentary_item' => true,
        'layaway_sale_manual_cart_discount' => true,
        'layaway_sale_manual_item_discount' => true,
        'layaway_sale_happy_hour_discount' => true,
        'layaway_sale_allow_multi_currency_in_payment' => true,

        'booking_payment_allow_multi_currency_in_payment' => true,
    ];

    $response = $this->companyQueries->addNew(new CompanyData(...$companyArray));

    expect($response)->toBeInt();

    unset($companyArray['light_logo'], $companyArray['dark_logo'], $companyArray['brand_ids'], $companyArray['email_footer_logo'], $companyArray['country_ids'], $companyArray['company_setting']);

    $this->assertDatabaseHas('companies', $companyArray);
    $this->assertDatabaseHas('brand_company', [
        'brand_id' => $brand->id,
    ]);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::COMPANY->name,
        'collection_name' => 'light_logo',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);

    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::COMPANY->name,
        'collection_name' => 'dark_logo',
        'file_name' => $uploadedFile2->name,
        'mime_type' => 'image/jpeg',
    ]);

    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::COMPANY->name,
        'collection_name' => 'email_footer_logo',
        'file_name' => $uploadedFile3->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('A company can be fetched', function (): void {
    $response = $this->companyQueries->getByIdWithMediaAndBrands($this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->companyA->name)
        ->toHaveKey('code', $this->companyA->code)
        ->toHaveKey('grn_format', $this->companyA->grn_format)
        ->toHaveKey('email', $this->companyA->email);
});

test('A company can be updated', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $uploadedFile2 = UploadedFile::fake()->image('avatar2.jpg');
    $uploadedFile3 = UploadedFile::fake()->image('avatar3.jpg');
    $brand = Brand::factory()->create();

    $countriesData = [
        [
            'name' => 'Country 1',
        ],
    ];

    foreach ($countriesData as $countryData) {
        $countryId = Country::create($countryData)->id;
    }

    $companyArray = Company::factory([
        'commission_type_id' => 1,
        'min_promoters_per_item' => 0,
        'is_bill_reference_number_mandatory' => 0,
        'yearly_target' => 20,
        'location_assignment_type' => LocationAssignmentTypes::MANUAL_ASSIGNMENT->value,
        'default_location_id' => null,
        'allow_happy_hour_discount' => 1,
        'auto_include_in_collections' => true,
        'creator_can_approve_draft_product' => false,
        'enable_e_invoice' => false,
        'show_e_invoice_qr_on_receipt' => false,
        'default_country_id' => $countryId,
        'auto_include_in_member_group' => true,
    ])->make()->toArray();
    $companyArray['light_logo'] = $uploadedFile;
    $companyArray['dark_logo'] = $uploadedFile2;
    $companyArray['email_footer_logo'] = $uploadedFile3;
    $companyArray['brand_ids'] = [$brand->id];
    $companyArray['country_ids'] = [$countryId];
    $companyArray['company_setting'] = [
        'credit_sale_use_cashback' => true,
        'credit_sale_redeem_loyalty_points' => true,
        'credit_sale_earn_loyalty_points' => true,
        'credit_sale_redeem_vouchers' => true,
        'credit_sale_generate_vouchers' => true,
        'credit_sale_cart_wide_automatic_promotions' => true,
        'credit_sale_cart_wide_manual_promotions' => true,
        'credit_sale_item_wise_automatic_promotions' => true,
        'credit_sale_item_wise_manual_promotions' => true,
        'credit_sale_complimentary_item' => true,
        'credit_sale_manual_cart_discount' => true,
        'credit_sale_manual_item_discount' => true,
        'credit_sale_happy_hour_discount' => true,
        'credit_sale_allow_multi_currency_in_payment' => true,

        'layaway_sale_use_cashback' => true,
        'layaway_sale_redeem_loyalty_points' => true,
        'layaway_sale_earn_loyalty_points' => true,
        'layaway_sale_redeem_vouchers' => true,
        'layaway_sale_generate_vouchers' => true,
        'layaway_sale_cart_wide_automatic_promotions' => true,
        'layaway_sale_cart_wide_manual_promotions' => true,
        'layaway_sale_item_wise_automatic_promotions' => true,
        'layaway_sale_item_wise_manual_promotions' => true,
        'layaway_sale_complimentary_item' => true,
        'layaway_sale_manual_cart_discount' => true,
        'layaway_sale_manual_item_discount' => true,
        'layaway_sale_happy_hour_discount' => true,
        'layaway_sale_allow_multi_currency_in_payment' => true,

        'booking_payment_allow_multi_currency_in_payment' => true,
    ];
    $this->companyQueries->update(new CompanyData(...$companyArray), $this->companyA->id);

    unset($companyArray['light_logo'], $companyArray['dark_logo'], $companyArray['email_footer_logo'], $companyArray['brand_ids'], $companyArray['country_ids'], $companyArray['company_setting']);

    $this->assertDatabaseHas('companies', $companyArray);
    $this->assertDatabaseHas('brand_company', [
        'brand_id' => $brand->id,
    ]);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::COMPANY->name,
        'collection_name' => 'light_logo',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);

    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::COMPANY->name,
        'collection_name' => 'dark_logo',
        'file_name' => $uploadedFile2->name,
        'mime_type' => 'image/jpeg',
    ]);

    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::COMPANY->name,
        'collection_name' => 'email_footer_logo',
        'file_name' => $uploadedFile3->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('a company can be searched by name', function (): void {
    $searchQuery = $this->companyQueries->searchByName($this->companyA->name);

    $totalCompanies = Company::where($searchQuery)->count();

    $this->assertEquals($totalCompanies, 1);
});

test('companies can be fetched', function (): void {
    $response = $this->companyQueries->getWithBasicColumns();

    expect($response[0])
        ->toHaveKey('id', $this->companyA->id)
        ->toHaveKey('name', $this->companyA->name);
});

test('getNameAndCodeById method call and return the company name & code', function (): void {
    $response = $this->companyQueries->getNameAndCodeById($this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->companyA->name)
        ->toHaveKey('code', $this->companyA->code);
});

test('getByIdWithBrands method returns the company with brands', function (): void {
    $response = $this->companyQueries->getByIdWithBrands($this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('id', $this->companyA->id)
        ->toHaveKey('brands.0.id', $this->brandA->id);
});

test('hasAllBrandsAttached method returns boolean as expected', function (): void {
    $brand = Brand::factory()->create();
    $response = $this->companyQueries->hasAllBrandsAttached($this->companyA->id, [$brand->id]);
    $this->assertFalse($response);

    $response = $this->companyQueries->hasAllBrandsAttached($this->companyA->id, [$this->brandA->id]);
    $this->assertTrue($response);
});

test('getGrnFormat method returns the GRN format', function (): void {
    $response = $this->companyQueries->getGrnFormat($this->companyA->id);
    $this->assertTrue($response === $this->companyA->grn_format);
});

test('getVoidSaleNumberPrefix method returns the void sale number prefix', function (): void {
    $response = $this->companyQueries->getVoidSaleNumberPrefix($this->companyA->id);
    $this->assertTrue($response === $this->companyA->void_sale_number_prefix);
});

test('getConfigurationColumnsById method returns configuration columns value', function (): void {
    $response = $this->companyQueries->getConfigurationColumnsById($this->companyA->id);

    expect($response->toArray())
        ->toHaveKeys(['id', 'min_promoters_per_item', 'is_bill_reference_number_mandatory']);
});

test('getList method return company list', function (): void {
    $response = $this->companyQueries->getList();

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->companyB->name)
        ->toHaveKey('code', $this->companyB->code)
        ->toHaveKey('email', $this->companyB->email)
        ->toHaveKey('fax', $this->companyB->fax)
        ->toHaveKey('address', $this->companyB->address);
});

test('getAllowHappyHourDiscount method return allow_happy_hour_discount value', function (): void {
    $response = $this->companyQueries->getAllowHappyHourDiscount($this->companyA->id);
    $this->assertTrue($response === $this->companyA->allow_happy_hour_discount);
});

test(
    'getWithLocationAssignmentTypeById method return location_assignment_type and default_location_id',
    function (): void {
        $this->companyA->location_assignment_type = LocationAssignmentTypes::DEFAULT_LOCATION->value;

        $response = $this->companyQueries->getWithLocationAssignmentTypeById($this->companyA->id);
        expect($response->toArray())
            ->toHaveKeys(['location_assignment_type', 'default_location_id']);
    }
);

test(
    'getIOICityAndTRXMallConfiguration method return enable_ioi_city_mall_integration and enable_trx_mall_integration',
    function (): void {
        $this->companyA->enable_ioi_city_mall_integration = true;
        $this->companyA->enable_trx_mall_integration = true;

        $response = $this->companyQueries->getIOICityAndTRXMallConfiguration($this->companyA->id);

        expect($response->toArray())
            ->toHaveKeys(['id', 'enable_ioi_city_mall_integration', 'enable_trx_mall_integration']);
    }
);

test(
    'getWithAutoIncludeInCollectionsById method return company with auto_include_in_collections column',
    function (): void {
        $this->companyA->auto_include_in_collections = true;

        $response = $this->companyQueries->getWithAutoIncludeInCollectionsById($this->companyA->id);

        expect($response->toArray())
            ->toHaveKeys(['id', 'auto_include_in_collections']);
    }
);

test(
    'getWithCreatorCanApproveDraftProductById method return company with creator_can_approve_draft_product column',
    function (): void {
        $response = $this->companyQueries->getWithCreatorCanApproveDraftProductById($this->companyA->id);

        expect($response->toArray())
            ->toHaveKeys(['id', 'creator_can_approve_draft_product']);
    }
);

test('getCountryCurrencySymbol method call and return the company currency', function (): void {
    DB::table('countries')->insert([
        'iso2' => 'Ab',
        'name' => 'ABCD',
        'status' => true,
        'phone_code' => '1234',
        'iso3' => 'bc',
        'region' => 'south',
        'subregion' => 'south left',
    ]);

    $countryId = Country::first()->id;

    $this->companyA->default_country_id = $countryId;
    $this->companyA->save();

    $response = $this->companyQueries->getCountryCurrencySymbol($this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->companyA->name)
        ->toHaveKey('code', $this->companyA->code)
        ->toHaveKey('default_country_id', $this->companyA->default_country_id);
});

test('it call getEnableEInvoiceById method and return enable_e_invoice', function (): void {
    $response = $this->companyQueries->getEnableEInvoiceById($this->companyA->id);

    expect($response)->toBeBool();
});

test('getByIdForPosAdmin method call and return the company', function (): void {
    $response = $this->companyQueries->getByIdForPosAdmin($this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->companyA->name)
        ->toHaveKey('code', $this->companyA->code)
        ->toHaveKey('email', $this->companyA->email)
        ->toHaveKey('uuid', $this->companyA->uuid);
});

test('Company can be restored', function (): void {
    $this->companyB->delete();

    $this->assertSoftDeleted('companies', [
        'id' => $this->companyB->id,
    ]);

    $this->companyQueries->restore($this->companyB->id);

    $this->assertDatabaseHas('companies', [
        'id' => $this->companyB->id,
        'deleted_at' => null,
    ]);
});

test('getAllCompanies returns the Companies details', function (): void {
    $this->companyB->delete();

    $response = $this->companyQueries->getAllCompanies();

    expect($response->count())->toBe(1);
    expect($response->toArray()[0])->toHaveKey('id', $this->companyA->id);
});

test('getCompanyIdsByUuid returns the Company by uuid', function (): void {
    $company = Company::factory()->create([
        'name' => 'ASSF',
        'code' => 'AZXV',
        'uuid' => 'qweqwe-wwqeweq-weweq',
    ]);

    $response = $this->companyQueries->getCompanyIdsByUuid($company->uuid);

    expect($response)
        ->toHaveKey('id', $company->id)
        ->toHaveKey('uuid', $company->uuid);
});
