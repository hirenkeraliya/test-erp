<?php

declare(strict_types=1);

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\SaleChannel\DataObjects\SaleChannelData;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\SaleChannel;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->saleChannel = SaleChannel::factory()->create();
    $this->saleChannelQueries = new SaleChannelQueries();
});

test('sale channel can be fetch', function (): void {
    $response = $this->saleChannelQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => 'asc',
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->saleChannel->name);
});

test('setArchiveCompanyInactive method updates the status of all sale channels for a company', function (): void {
    $this->saleChannelQueries->setArchiveCompanyInactive($this->saleChannel->company_id, false);

    $this->assertDatabaseHas('sale_channels', [
        'company_id' => $this->saleChannel->company_id,
        'status' => false,
    ]);
});

test('setRestoreCompanyActive method updates the status of all sale channels for a company', function (): void {
    $companyId = $this->saleChannel->company_id;

    SaleChannel::factory()->count(2)->create([
        'company_id' => $companyId,
        'status' => false,
    ]);

    $this->saleChannelQueries->setRestoreCompanyActive($companyId, true);

    $this->assertDatabaseHas('sale_channels', [
        'company_id' => $companyId,
        'status' => true,
    ]);
});

test('New sale channel can be added', function (): void {
    $this->saleChannelQueries->addNew(new SaleChannelData(
        'Test',
        '007',
        $this->saleChannel->company_id,
        $this->saleChannel->default_location_id,
        SaleChannelTypes::ECOMMERCE->value,
        OrderStatus::ACCEPTED->value,
        'test',
        'test',
        [OrderStatus::CANCELLED->value, OrderStatus::RETURNED->value, OrderStatus::DECLINED->value],
        [[
            'webhook_url_type_id' => WebhookUrls::PRODUCT_CREATE->value,
            'url' => 'test/product/create',
            'variance_url' => null,
        ]],
        true
    ));

    $this->assertDatabaseHas(SaleChannel::class, [
        'name' => 'Test',
        'code' => '007',
    ]);
});

test('A sale channel can be updated', function (): void {
    $this->saleChannelQueries->update(
        new SaleChannelData(
            'Test',
            '007',
            $this->saleChannel->company_id,
            $this->saleChannel->default_location_id,
            SaleChannelTypes::ECOMMERCE->value,
            OrderStatus::ACCEPTED->value,
            'test',
            'test',
            [OrderStatus::CANCELLED->value, OrderStatus::RETURNED->value, OrderStatus::DECLINED->value],
            [[
                'webhook_url_type_id' => WebhookUrls::PRODUCT_CREATE->value,
                'url' => 'test/product/create',
                'variance_url' => null,
            ]],
            true
        ),
        $this->saleChannel
    );

    $this->assertDatabaseHas(SaleChannel::class, [
        'name' => 'Test',
        'code' => '007',
    ]);

    $this->saleChannel->refresh();
});

test('can get all company wise sale channels', function (): void {
    $response = $this->saleChannelQueries->getAllByCompanyId($this->saleChannel->company_id);

    expect($response)->toBeInstanceOf(Collection::class);
    expect($response->first()->toArray())
        ->toHaveKey('name', $this->saleChannel->name);
});

test('A sale channel can refresh the access tokens', function (): void {
    $response = $this->saleChannelQueries->refreshToken($this->saleChannel->getKey());

    expect($response)->toBeString();
});

test('updateStatus method update the status.', function (): void {
    $this->saleChannelQueries->updateStatus($this->saleChannel->id, false);

    $this->assertDatabaseHas('sale_channels', [
        'id' => $this->saleChannel->id,
        'company_id' => $this->saleChannel->company_id,
        'status' => false,
    ]);
});

test('calls getWebspertSaleChannel method.', function (): void {
    $saleChannel = SaleChannel::factory()->create([
        'company_id' => $this->saleChannel->company_id,
        'type_id' => SaleChannelTypes::WEBSPERT_ECOMMERCE->value,
    ]);

    $this->mock(SaleChannelQueries::class, function ($mock) use ($saleChannel): void {
        $mock->shouldReceive('getWebspertSaleChannel')
        ->andReturn($saleChannel);
    });

    $response = $this->saleChannelQueries->getWebspertSaleChannel();

    expect($response)->toBeInstanceOf(SaleChannel::class);
});

test('calls isEcommerceEnabled method.', function (): void {
    SaleChannel::factory()->create([
        'company_id' => $this->saleChannel->company_id,
        'type_id' => SaleChannelTypes::ECOMMERCE->value,
    ]);

    $response = $this->saleChannelQueries->isEcommerceEnabled();

    expect($response)->toBeTrue();
});
