<?php

declare(strict_types=1);

use App\Domains\MysteryGift\DataObjects\MysteryGiftData;
use App\Domains\MysteryGift\MysteryGiftQueries;
use App\Domains\MysteryGift\Resources\AdminEditMysteryGiftResource;
use App\Domains\MysteryGift\Services\MysteryGiftService;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Admin\MysteryGiftController;
use App\Models\Admin;
use App\Models\MysteryGift;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->mysteryGiftQueries = $this->mock(MysteryGiftQueries::class);
    $this->mysteryGiftService = $this->mock(MysteryGiftService::class);
    $this->controller = new MysteryGiftController($this->mysteryGiftQueries);
    session([
        'admin_company_id' => 1,
    ]);
});

test('index method returns Inertia response', function (): void {
    Inertia::shouldReceive('render')
        ->once()
        ->with('mystery_gifts/Index')
        ->andReturn(new Response('mystery_gifts/Index', []));

    $response = $this->controller->index();

    $this->assertInstanceOf(Response::class, $response);
});

test('fetchMysteryGifts method returns proper response', function (): void {
    $request = new Request([
        'search_text' => 'test',
        'sort_by' => 'name',
        'sort_direction' => 'asc',
        'per_page' => 10,
    ]);

    $paginator = new LengthAwarePaginator([], 50, 10);

    $this->mysteryGiftQueries->shouldReceive('listQuery')
        ->once()
        ->andReturn($paginator);

    $response = $this->controller->fetchMysteryGifts($request);

    $this->assertIsArray($response);
    $this->assertArrayHasKey('total_records', $response);
    $this->assertArrayHasKey('data', $response);
});

test('create method returns Inertia response', function (): void {
    Inertia::shouldReceive('render')
        ->once()
        ->with('mystery_gifts/Manage')
        ->andReturn(new Response('mystery_gifts/Manage', []));

    $response = $this->controller->create();

    $this->assertInstanceOf(Response::class, $response);
});

test('store method adds new mystery gift and returns redirect response', function (): void {
    $request = new Request();
    $request->setUserResolver(fn (): Admin => new Admin());

    $mysteryGiftData = new MysteryGiftData('Test Gift', '2023-01-01', '2023-12-31', 100.0, 10.0, [], 50.0);

    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('commit')->once();

    $this->mysteryGiftQueries->shouldReceive('addNew')
        ->once()
        ->andReturn(new MysteryGift());

    $this->mysteryGiftService->shouldReceive('generateVoucherForMysteryGift')
        ->once();

    $response = $this->controller->store($mysteryGiftData, $request);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Mystery Gift added successfully.', $response->getSession()->get('success'));
});

test('store method handles exception and throws RedirectBackWithErrorException', function (): void {
    $request = new Request();
    $request->setUserResolver(fn (): Admin => new Admin());

    $mysteryGiftData = new MysteryGiftData('Test Gift', '2023-01-01', '2023-12-31', 100.0, 10.0, [], 50.0);

    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('rollBack')->once();

    $this->mysteryGiftQueries->shouldReceive('addNew')
        ->once()
        ->andThrow(new Exception('Test Exception'));

    Log::shouldReceive('error')->once();

    $this->expectException(RedirectBackWithErrorException::class);

    $this->controller->store($mysteryGiftData, $request);
});

test('edit method returns Inertia response with mystery gift data', function (): void {
    $mysteryGift = new MysteryGift();
    $this->mysteryGiftQueries->shouldReceive('getById')
        ->once()
        ->andReturn($mysteryGift);

    Inertia::shouldReceive('render')
        ->once()
        ->with('mystery_gifts/Manage', [
            'mysteryGift' => new AdminEditMysteryGiftResource($mysteryGift),
        ])
        ->andReturn(new Response('mystery_gifts/Manage', [
            'mysteryGift' => new AdminEditMysteryGiftResource($mysteryGift),
        ]));

    $response = $this->controller->edit(1);

    $this->assertInstanceOf(Response::class, $response);
});

test('update method updates mystery gift and returns redirect response', function (): void {
    $request = new Request();
    $request->setUserResolver(fn (): Admin => new Admin());

    $mysteryGiftData = new MysteryGiftData('Test Gift', '2023-01-01', '2023-12-31', 100.0, 10.0, [], 50.0);

    $mysteryGift = new MysteryGift([
        'status' => true,
    ]);

    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('commit')->once();

    $this->mysteryGiftQueries->shouldReceive('getById')
        ->once()
        ->andReturn($mysteryGift);

    $this->mysteryGiftQueries->shouldReceive('update')
        ->once()
        ->andReturn($mysteryGift);

    $this->mysteryGiftService->shouldReceive('generateVoucherForMysteryGift')
        ->once();

    $response = $this->controller->update($mysteryGiftData, 1, $request);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Promotion has been successfully updated.', $response->getSession()->get('success'));
});

test('update method throws exception when promotion is inactive', function (): void {
    $request = new Request();
    $request->setUserResolver(fn (): Admin => new Admin());

    $mysteryGiftData = new MysteryGiftData('Test Gift', '2023-01-01', '2023-12-31', 100.0, 10.0, [], 50.0);

    $mysteryGift = new MysteryGift([
        'status' => false,
    ]);

    $this->mysteryGiftQueries->shouldReceive('getById')
        ->once()
        ->andReturn($mysteryGift);

    $this->expectException(HttpException::class);
    $this->expectExceptionMessage('This promotion is currently inactive.');

    $this->controller->update($mysteryGiftData, 1, $request);
});

test('setStatus method updates status and returns redirect response', function (): void {
    $this->mysteryGiftQueries->shouldReceive('setStatus')
        ->once();

    $response = $this->controller->setStatus(1, true);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Status changed successfully.', $response->getSession()->get('success'));
});

test('removeSelectedProducts method calls removeSelectedProducts on queries', function (): void {
    $requestParameter = [
        'id' => 1,
    ];

    $request = $this->mock(Request::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('validate')
            ->once()
            ->andReturn($requestParameter);
    });

    $this->mysteryGiftQueries->shouldReceive('removeSelectedProducts')
        ->with($requestParameter)
        ->once();

    $this->controller->removeSelectedProducts($request);
});

test('exportMysteryGiftsProductsDetails method returns BinaryFileResponse', function (): void {
    $mysteryGift = new MysteryGift();

    $this->mysteryGiftQueries->shouldReceive('fetchPromotionProducts')
        ->once()
        ->andReturn($mysteryGift);

    $response = $this->controller->exportMysteryGiftsProductsDetails(1, 'test.xlsx');

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);

    $this->assertInstanceOf(BinaryFileResponse::class, $response);
});

test('the generateQrCode method and returns the QrCode', function (): void {
    $response = $this->controller->generateQrCode();

    expect($response)->toBeInstanceOf(HtmlString::class);
});
