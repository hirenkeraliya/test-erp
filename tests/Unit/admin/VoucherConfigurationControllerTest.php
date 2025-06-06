<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\VoucherConfiguration\DataObjects\VoucherConfigurationData;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Admin\VoucherConfigurationController;
use App\Models\Admin;
use App\Models\VoucherConfiguration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the voucher configuration queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'birthday voucher',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'status' => null,
            'restricted_by_type_id' => null,
            'voucher_type_id' => null,
            'discount_type_id' => null,
            'type' => null,
        ];

        $voucherConfigurationQueries = $this->mock(VoucherConfigurationQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $voucherConfigurationController = new VoucherConfigurationController($voucherConfigurationQueries);

        $response = $voucherConfigurationController->fetchVoucherConfigurations(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        expect($response['data']->resource)->toBeCollection();
    }
);

test(
    'It calls the addNew method of the VoucherConfigurationQueries class and returns proper response',
    function (): void {
        setCompanyIdInSession();

        $voucherConfigurationRecord = VoucherConfiguration::factory()->make([
            'company_id' => 1,
            'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
            'exclude_by_type' => ExcludeByTypes::NONE->value,
            'start_date' => '2022-06-27',
            'end_date' => '2022-06-28',
            'title' => 'Abcde',
        ])->toArray();

        $tiers = [
            'minimum_spend_amount' => 50,
            'maximum_spend_amount' => 10,
            'get_value' => 5,
        ];

        $voucherConfigurationRecord['image'] = null;
        $voucherConfigurationRecord['thumbnail'] = null;

        $voucherConfigurationRecord['tiers'] = [$tiers];
        $voucherConfigurationRecord['category_ids'] = [];
        $voucherConfigurationRecord['product_ids'] = [];
        $voucherConfigurationRecord['membership_ids'] = [1];

        unset($voucherConfigurationRecord['company_id']);

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $voucherConfigurationData = new VoucherConfigurationData(...$voucherConfigurationRecord);

        $voucherConfigurationQueries = $this->mock(VoucherConfigurationQueries::class, function ($mock) use (
            $voucherConfigurationData,
            $admin
        ): void {
            $mock->shouldReceive('getBirthdayVoucherId')
                ->once()
                ->with(1)
                ->andReturn();

            $mock->shouldReceive('getWelcomeMemberVoucherId')
                ->once()
                ->with(1)
                ->andReturn();

            $mock->shouldReceive('addNew')
                ->once()
                ->with($voucherConfigurationData, 1, $admin);
        });

        $voucherConfigurationController = new VoucherConfigurationController($voucherConfigurationQueries);
        $redirectResponse = $voucherConfigurationController->store($voucherConfigurationData, $request);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals(
            'Voucher Configuration added successfully.',
            $redirectResponse->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/vouchers-configuration', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the get by id method of the voucher configurations queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $voucherConfigurationRecord = VoucherConfiguration::factory()->make([
            'company_id' => 1,
            'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
        ])->toArray();

        $returnData = [
            'id' => '1',
            'name' => 'ABC',
        ];

        $voucherConfigurationQueries = $this->mock(VoucherConfigurationQueries::class, function ($mock) use (
            $voucherConfigurationRecord,
            $companyId
        ): void {
            $mock->shouldReceive('getBirthdayVoucherId')
                ->once()
                ->with($companyId)
                ->andReturn(1);

            $mock->shouldReceive('getWelcomeMemberVoucherId')
                ->once()
                ->with($companyId)
                ->andReturn(1);

            $mock->shouldReceive('getById')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new VoucherConfiguration($voucherConfigurationRecord));
        });

        $this->mock(CategoryQueries::class, function ($mock) use ($returnData): void {
            $mock->shouldReceive('getMainCategoriesWithBasicColumns')
                ->once()
                ->with(1)
                ->andReturn(new Collection([$returnData]));
        });

        $this->mock(MembershipQueries::class, function ($mock) use ($returnData): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->with(1)
                ->andReturn(new Collection([$returnData]));
        });

        $voucherConfigurationController = new VoucherConfigurationController($voucherConfigurationQueries);
        $response = $voucherConfigurationController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'voucherConfiguration',
            fn (Assert $voucherConfiguration): Assert => $voucherConfiguration
                ->where('restricted_by_type', $voucherConfigurationRecord['restricted_by_type'])
                ->where('voucher_type', $voucherConfigurationRecord['voucher_type'])
                ->where('validity_days', $voucherConfigurationRecord['validity_days'])
                ->etc()
        )
        ->has(
            'categories',
            fn (Assert $category): Assert => $category
                ->where('0.id', $returnData['id'])
                ->where('0.name', $returnData['name'])
        )
        );
    }
);

test(
    'It calls the update method of the VoucherConfigurationsQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'company_id' => 1,
            'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
            'exclude_by_type' => ExcludeByTypes::NONE->value,
            'start_date' => '2022-06-27',
            'end_date' => '2022-06-28',
            'title' => 'Abcde',
        ]);

        $voucherConfigurationRecord = $voucherConfiguration->toArray();

        $tiers = [
            'minimum_spend_amount' => 50,
            'maximum_spend_amount' => 10,
            'get_value' => 5,
        ];

        $voucherConfigurationRecord['tiers'] = [$tiers];
        $voucherConfigurationRecord['category_ids'] = [];
        $voucherConfigurationRecord['product_ids'] = [];
        $voucherConfigurationRecord['membership_ids'] = [];
        $voucherConfigurationRecord['image'] = null;
        $voucherConfigurationRecord['thumbnail'] = null;

        unset($voucherConfigurationRecord['company_id']);

        $voucherConfigurationData = new VoucherConfigurationData(...$voucherConfigurationRecord);

        $voucherConfigurationQueries = $this->mock(VoucherConfigurationQueries::class, function ($mock) use (
            $voucherConfigurationData,
            $voucherConfiguration,
            $companyId
        ): void {
            $mock->shouldReceive('getBirthdayVoucherId')
                ->once()
                ->with($companyId)
                ->andReturn();

            $mock->shouldReceive('getWelcomeMemberVoucherId')
                ->once()
                ->with($companyId)
                ->andReturn();
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($voucherConfiguration);
            $mock->shouldReceive('update')
                ->once()
                ->with($voucherConfigurationData, 1, $companyId);
        });

        $voucherConfigurationController = new VoucherConfigurationController($voucherConfigurationQueries);
        $redirectResponse = $voucherConfigurationController->update($voucherConfigurationData, 1);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals(
            'Voucher Configuration updated successfully.',
            $redirectResponse->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/vouchers-configuration', $redirectResponse->getTargetUrl());
    }
);

test(
    'it cannot update voucher to the birthday voucher type when birthday voucher is already exists.',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $voucherConfigurationRecord = VoucherConfiguration::factory()->make([
            'company_id' => 1,
            'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
            'exclude_by_type' => ExcludeByTypes::NONE->value,
            'start_date' => '2022-06-27',
            'end_date' => '2022-06-28',
            'title' => 'abcde',
        ])->toArray();

        $tiers = [
            'minimum_spend_amount' => 50,
            'maximum_spend_amount' => 10,
            'get_value' => 5,
        ];

        $voucherConfigurationRecord['tiers'] = [$tiers];
        $voucherConfigurationRecord['category_ids'] = [];
        $voucherConfigurationRecord['product_ids'] = [];
        $voucherConfigurationRecord['membership_ids'] = [];
        $voucherConfigurationRecord['image'] = null;
        $voucherConfigurationRecord['thumbnail'] = null;

        unset($voucherConfigurationRecord['company_id']);

        $voucherConfigurationData = new VoucherConfigurationData(...$voucherConfigurationRecord);

        $voucherConfigurationQueries = $this->mock(VoucherConfigurationQueries::class, function ($mock) use (
            $companyId
        ): void {
            $mock->shouldReceive('getBirthdayVoucherId')
                ->once()
                ->with($companyId)
                ->andReturn(1);
        });

        $voucherConfigurationController = new VoucherConfigurationController($voucherConfigurationQueries);
        $voucherConfigurationController->update($voucherConfigurationData, 2);
    }
)->throws(RedirectBackWithErrorException::class);

test('it calls the setStatus method of VoucherConfigurationQueries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'company_id' => $companyId,
        'id' => 1,
        'status' => 0,
    ]);

    $voucherConfigurationQueries = $this->mock(VoucherConfigurationQueries::class, function ($mock) use (
        $voucherConfiguration,
        $companyId
    ): void {
        $mock->shouldReceive('setStatus')
            ->once()
            ->with($voucherConfiguration->id, $companyId, true);
    });

    $voucherConfigurationController = new VoucherConfigurationController($voucherConfigurationQueries);
    $response = $voucherConfigurationController->setStatus(1, true);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/vouchers-configuration', $response->getTargetUrl());
});

test('It calls the exportVoucherConfigurations method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'status' => null,
        'restricted_by_type_id' => null,
        'voucher_type_id' => null,
        'discount_type_id' => null,
        'type' => null,
    ];

    $voucherConfigurationQueries = $this->mock(VoucherConfigurationQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getVouchersConfigurationExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new VoucherConfiguration()));
    });

    $voucherConfigurationController = new VoucherConfigurationController($voucherConfigurationQueries);

    $response = $voucherConfigurationController->exportVoucherConfigurations(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'it cannot update voucher type changed during the edit',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'company_id' => 1,
            'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
            'exclude_by_type' => ExcludeByTypes::NONE->value,
            'start_date' => '2022-06-27',
            'end_date' => '2022-06-28',
            'title' => 'Abcde',
        ]);

        $voucherConfigurationRecord = $voucherConfiguration->toArray();

        $voucherConfigurationRecord['voucher_type'] = VoucherTypes::TIER_VOUCHER->value;

        $tiers = [
            'minimum_spend_amount' => 50,
            'maximum_spend_amount' => 10,
            'get_value' => 5,
        ];

        $voucherConfigurationRecord['tiers'] = [$tiers];
        $voucherConfigurationRecord['category_ids'] = [];
        $voucherConfigurationRecord['product_ids'] = [];
        $voucherConfigurationRecord['membership_ids'] = [];
        $voucherConfigurationRecord['image'] = null;
        $voucherConfigurationRecord['thumbnail'] = null;

        unset($voucherConfigurationRecord['company_id']);

        $voucherConfigurationData = new VoucherConfigurationData(...$voucherConfigurationRecord);

        $voucherConfigurationQueries = $this->mock(VoucherConfigurationQueries::class, function ($mock) use (
            $companyId,
            $voucherConfiguration
        ): void {
            $mock->shouldReceive('getBirthdayVoucherId')
             ->once()
             ->with($companyId)
             ->andReturn(1);

            $mock->shouldReceive('getWelcomeMemberVoucherId')
            ->once()
            ->with($companyId)
            ->andReturn();

            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($voucherConfiguration);
        });

        $voucherConfigurationController = new VoucherConfigurationController($voucherConfigurationQueries);
        $voucherConfigurationController->update($voucherConfigurationData, 2);
    }
)->throws(RedirectBackWithErrorException::class);
