<?php

declare(strict_types=1);

use App\Domains\StoreManager\DataObjects\StoreManagerProfileData;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\StoreManager\StoreManagerProfileController;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

it('editProfile method renders the correct view with store manager data', function (): void {
    $storeManagerId = 1;
    $storeManagerData = new StoreManager([
        'username' => 'JohnDoe',
        'employee_id' => 123,
        'price_override_type' => 'percentage',
        'price_override_limit_percentage_for_item' => 10,
    ]);

    Auth::shouldReceive('id')->andReturn($storeManagerId);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManagerId, $storeManagerData): void {
        $mock->shouldReceive('getStoreManagerData')
            ->with($storeManagerId)
            ->andReturn($storeManagerData);
    });

    $storeManagerProfileController = new StoreManagerProfileController();
    $response = $storeManagerProfileController->editProfile();

    $response->rootView('store_manager.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));
    $newResponse->assertInertia(fn (Assert $inertia): Assert => $inertia->has('storeManager'));
});

it('updateProfile method handles successful profile update', function (): void {
    $storeManagerId = 1;
    $storeManagerData = new StoreManagerProfileData(1, 'UpdatedUsername', null, null);
    $storeManagerDataArray = $storeManagerData->all();
    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManagerId, $storeManagerDataArray): void {
        $mock->shouldReceive('updateStoreManagerProfile')
        ->with($storeManagerId, $storeManagerDataArray)
        ->once();
    });

    $storeManagerProfileController = new StoreManagerProfileController();
    $redirectResponse = $storeManagerProfileController->updateProfile($storeManagerData, $storeManagerId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Store Manager updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/dashboard', $redirectResponse->getTargetUrl());
});
