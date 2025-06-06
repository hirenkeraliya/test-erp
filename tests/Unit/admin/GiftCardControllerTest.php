<?php

declare(strict_types=1);

use App\Domains\GiftCard\DataObjects\GiftCardData;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCard\Resources\GiftCardListResource;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Admin\GiftCardController;
use App\Models\Admin;
use App\Models\GiftCard;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the gift card queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'status' => null,
        'type' => null,
        'expiry_date' => 'null',
        'created_date' => 'null',
    ];

    $giftCardQueries = $this->mock(GiftCardQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $giftCardController = new GiftCardController($giftCardQueries);

    $response = $giftCardController->fetchGiftCard(new Request($requestParameter));

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(GiftCardListResource::collection(collect([])), $response['data']);
});

test('It calls the upload method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $giftCardData = [
        'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
        'gift_cards' => [
            [
                'number' => '123',
                'expiry_date' => null,
                'amount' => 10,
            ],
        ],
    ];

    $giftCard = [
        'company_id' => $companyId,
        'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
        'number' => '123',
        'expiry_date' => null,
        'total_amount' => 10,
        'available_amount' => 10,
    ];

    $request = new Request();

    $request->setUserResolver(fn (): Admin => new Admin([
        'employee_id' => 1,
    ]));

    $giftCardData = new GiftCardData(...$giftCardData);

    $giftCardQueries = $this->mock(GiftCardQueries::class, function ($mock): void {
        $mock->shouldReceive('checkExistingNumbers')
            ->once()
            ->andReturn(false);

        $mock->shouldReceive('createMany')
            ->once();
    });

    $giftCardController = new GiftCardController($giftCardQueries);
    $response = $giftCardController->upload($giftCardData);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Gift cards added successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/gift-cards', $response->getTargetUrl());
});

test('exceptions are thrown when numbers are duplicated.', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $giftCardData = [
        'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
        'gift_cards' => [
            [
                'number' => '123',
                'expiry_date' => null,
                'amount' => 10,
            ],
        ],
    ];

    $request = new Request();

    $request->setUserResolver(fn (): Admin => new Admin([
        'employee_id' => 1,
    ]));

    $giftCardData = new GiftCardData(...$giftCardData);

    $giftCardQueries = $this->mock(GiftCardQueries::class, function ($mock): void {
        $mock->shouldReceive('checkExistingNumbers')
            ->once()
            ->andReturn(true);

        $mock->shouldNotReceive('createMany');
    });

    $giftCardController = new GiftCardController($giftCardQueries);
    $giftCardController->upload($giftCardData);
})->throws(RedirectBackWithErrorException::class);

test('It calls the exportGiftCards method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'status' => null,
        'type' => null,
        'expiry_date' => 'null',
        'created_date' => 'null',
    ];

    $giftCardQueries = $this->mock(GiftCardQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getGiftCardsForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new GiftCard()));
    });

    $giftCardController = new GiftCardController($giftCardQueries);

    $response = $giftCardController->exportGiftCards('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
