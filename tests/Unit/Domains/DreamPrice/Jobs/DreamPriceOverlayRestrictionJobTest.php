<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPrice\Jobs\DreamPriceOverlayRestrictionJob;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Notification\NotificationQueries;
use App\Models\Admin;
use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use App\Models\Location;

test(
    'DreamPriceOverlayRestrictionJob method call getAllActiveDreamPrice for all active Dream Prices',
    function (): void {
        $dreamPrice = DreamPrice::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $dreamPriceProduct = DreamPriceProduct::factory()->make([
            'id' => 1,
            'dream_price_id' => 1,
            'product_id' => 1,
        ]);

        $dreamPrice->locations = collect($location);
        $dreamPrice->dreamPriceProducts = collect($dreamPriceProduct);

        $data = [
            'dream_price_id_1' => $dreamPrice->id,
            'dream_price_id_2' => 2,
            'dream_price_company_id_1' => 1,
            'dream_price_company_id_2' => 1,
            'dream_price_name_1' => 'test',
            'dream_price_name_2' => 'hello',
        ];

        $this->mock(DreamPriceQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('getAllActiveDreamPrice')
                ->once()
                ->andReturn(collect($data));
        });

        $this->mock(AdminQueries::class, function ($mock): void {
            $mock->shouldNotReceive('getByCompanyIdOnlyId');
        });

        DreamPriceOverlayRestrictionJob::dispatch()->onQueue(config('horizon.default_queue_name'));
    }
);

test(
    'DreamPriceOverlayRestrictionJob method calls getAllActiveDreamPrice for all active Dream Prices And Add Notifications',
    function (): void {
        $dreamPrice1 = DreamPrice::factory()->make([
            'id' => 1,
            'name' => 'dream price 1',
            'company_id' => 1,
        ]);

        $dreamPrice2 = DreamPrice::factory()->make([
            'id' => 1,
            'name' => 'dream price 2',
            'company_id' => 1,
        ]);

        $location1 = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location2 = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $dreamPriceProduct1 = DreamPriceProduct::factory()->make([
            'id' => 1,
            'dream_price_id' => 1,
            'product_id' => 1,
        ]);

        $dreamPriceProduct2 = DreamPriceProduct::factory()->make([
            'id' => 1,
            'dream_price_id' => 1,
            'product_id' => 1,
        ]);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $dreamPrice1->locations = collect([$location1]);
        $dreamPrice1->dreamPriceProducts = collect([$dreamPriceProduct1]);

        $dreamPrice2->locations = collect([$location2]);
        $dreamPrice2->dreamPriceProducts = collect([$dreamPriceProduct2]);

        $data = [
            [
                'dream_price_id_1' => $dreamPrice1->id,
                'dream_price_id_2' => $dreamPrice2->id,
                'dream_price_company_id_1' => $dreamPrice1->company_id,
                'dream_price_company_id_2' => $dreamPrice2->company_id,
                'dream_price_name_1' => $dreamPrice1->name,
                'dream_price_name_2' => $dreamPrice2->name,
            ],
            [
                'dream_price_id_1' => 3,
                'dream_price_id_2' => 4,
                'dream_price_company_id_1' => $dreamPrice1->company_id,
                'dream_price_company_id_2' => $dreamPrice2->company_id,
                'dream_price_name_1' => $dreamPrice1->name,
                'dream_price_name_2' => $dreamPrice2->name,
            ],
        ];

        $dataObject = json_decode(json_encode($data));

        $this->mock(DreamPriceQueries::class, function ($mock) use ($dataObject): void {
            $mock->shouldReceive('getAllActiveDreamPrice')
                ->once()
                ->andReturn(collect($dataObject));
        });

        $this->mock(AdminQueries::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('getByCompanyIdOnlyId')
                ->twice()
                ->andReturn(collect([$admin]));
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->twice();
        });

        DreamPriceOverlayRestrictionJob::dispatch()->onQueue(config('horizon.default_queue_name'));
    }
);
