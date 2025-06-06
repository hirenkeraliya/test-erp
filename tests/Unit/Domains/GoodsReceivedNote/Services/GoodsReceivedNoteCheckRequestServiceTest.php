<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteCheckRequestService;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\Batch;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Database\Eloquent\Collection;

test('grn reference validation passes when proper data is provided', function (): void {
    $this->mock(GoodsReceivedNoteQueries::class, function ($mock): void {
        $mock->shouldReceive('grnReferenceExists')
            ->once()
            ->with('1', 1)
            ->andReturn(false);
    });

    $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

    $goodsReceivedNoteCheckRequestService->validateGrnReference('1', 1);
});

test('grnReferenceExists method throws an exception if grn reference exist in our records.', function (): void {
    $this->mock(GoodsReceivedNoteQueries::class, function ($mock): void {
        $mock->shouldReceive('grnReferenceExists')
            ->once()
            ->with('1', 1)
            ->andReturn(true);
    });

    $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

    $goodsReceivedNoteCheckRequestService->validateGrnReference('1', 1);
})->throws(RedirectBackWithErrorException::class);

test('products validation passes when proper data provided', function (): void {
    $goodsReceivedNoteProducts = getGrnRequestDetails('a12312', '2030-10-10');
    $product = commonGetProductDetails();
    $product->unit_of_measure_id = null;

    $this->mock(BatchQueries::class, function ($mock): void {
        $mock->shouldReceive('getByNumbers')
            ->once()
            ->with(['a12312'], 1)
            ->andReturn(new Collection([]));
    });

    $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

    $response = $goodsReceivedNoteCheckRequestService->validateProducts(
        collect($goodsReceivedNoteProducts['goods_received_note_products']),
        collect([$product]),
        1,
        collect([])
    );
    expect($response)->toBeEmpty();
});

test('validateProducts method throws an exception if batch number is null', function (): void {
    $goodsReceivedNoteProducts = getGrnRequestDetails(null, '1990-10-10');
    $product = commonGetProductDetails();

    $this->mock(BatchQueries::class, function ($mock): void {
        $mock->shouldReceive('getByNumbers')
            ->once()
            ->with([], 1)
            ->andReturn(new Collection([]));
    });

    $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

    $goodsReceivedNoteCheckRequestService->validateProducts(
        collect($goodsReceivedNoteProducts['goods_received_note_products']),
        collect([$product]),
        1,
        collect([])
    );
})->throws(RedirectBackWithErrorException::class);

test('validateProducts method throws an exception if batch expiry date set null', function (): void {
    $goodsReceivedNoteProducts = getGrnRequestDetails('12312');
    $product = commonGetProductDetails();

    $this->mock(BatchQueries::class, function ($mock): void {
        $mock->shouldReceive('getByNumbers')
            ->once()
            ->with(['12312'], 1)
            ->andReturn(new Collection([]));
    });

    $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

    $goodsReceivedNoteCheckRequestService->validateProducts(
        collect($goodsReceivedNoteProducts['goods_received_note_products']),
        collect([$product]),
        1,
        collect([])
    );
})->throws(RedirectBackWithErrorException::class);

test(
    'validateProducts method throws an exception if batch number exists for other product',
    function (): void {
        $goodsReceivedNoteProducts = getGrnRequestDetails('12312', '2030-10-10');
        $product = commonGetProductDetails();
        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 2,
            'number' => '12312',
            'expiry_date' => '1909-10-10',
        ]);

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByNumbers')
                ->once()
                ->with([$batch->number], 1)
                ->andReturn(new Collection([$batch]));
        });

        $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

        $goodsReceivedNoteCheckRequestService->validateProducts(
            collect($goodsReceivedNoteProducts['goods_received_note_products']),
            collect([$product]),
            1,
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'validateProducts method throws an exception if batch expiry date does not match with other product',
    function (): void {
        $goodsReceivedNoteProducts = getGrnRequestDetails('12312', '2030-10-10');
        $product = commonGetProductDetails();
        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => '12312',
            'expiry_date' => '1910-10-10',
        ]);

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByNumbers')
                ->once()
                ->with([$batch->number], 1)
                ->andReturn(new Collection([$batch]));
        });

        $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

        $goodsReceivedNoteCheckRequestService->validateProducts(
            collect($goodsReceivedNoteProducts['goods_received_note_products']),
            collect([$product]),
            1,
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'validateProducts method throws an exception if batch number is provided for a product that does not maintain batches.',
    function (): void {
        $goodsReceivedNoteProducts = getGrnRequestDetails('12312', '2030-10-10');
        $product = commonGetProductDetails(false);
        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => '12312',
            'expiry_date' => '2030-10-10',
        ]);

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByNumbers')
                ->once()
                ->with([$batch->number], 1)
                ->andReturn(new Collection([$batch]));
        });

        $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

        $goodsReceivedNoteCheckRequestService->validateProducts(
            collect($goodsReceivedNoteProducts['goods_received_note_products']),
            collect([$product]),
            1,
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'validateProducts method throws an exception if batch expiry date does not set future date',
    function (): void {
        $goodsReceivedNoteProducts = getGrnRequestDetails('12312', '1909-10-10');
        $product = commonGetProductDetails(true);
        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'number' => '12312',
            'expiry_date' => '2030-10-10',
        ]);

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByNumbers')
                ->once()
                ->with([$batch->number], 1)
                ->andReturn(new Collection([$batch]));
        });

        $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

        $goodsReceivedNoteCheckRequestService->validateProducts(
            collect($goodsReceivedNoteProducts['goods_received_note_products']),
            collect([$product]),
            1,
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'validateProducts method throws an exception if derivate attached on non unit of measure product',
    function (): void {
        $goodsReceivedNoteProducts = getGrnRequestDetails('a12312', '2030-10-10', 'test');
        $product = commonGetProductDetails();
        $product->unit_of_measure_id = null;

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldReceive('getByNumbers')
                ->once()
                ->with(['a12312'], 1)
                ->andReturn(new Collection([]));
        });

        $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

        $response = $goodsReceivedNoteCheckRequestService->validateProducts(
            collect($goodsReceivedNoteProducts['goods_received_note_products']),
            collect([$product]),
            1,
            collect([])
        );
        expect($response)->toBeEmpty();
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'validateProducts method throws an exception if derivate does not exists by uploaded derivate name.',
    function (): void {
        $goodsReceivedNoteProducts = getGrnRequestDetails('a12312', '2030-10-10', 'test');
        $UnitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'uom2',
        ]);
        $product = commonGetProductDetails();
        $product->unit_of_measure_id = $UnitOfMeasure->id;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $UnitOfMeasure->id,
            'name' => 'abcd',
        ]);

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldReceive('getByNumbers')
                ->once()
                ->with(['a12312'], 1)
                ->andReturn(new Collection([]));
        });

        $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

        $response = $goodsReceivedNoteCheckRequestService->validateProducts(
            collect($goodsReceivedNoteProducts['goods_received_note_products']),
            collect([$product]),
            1,
            collect([$derivative])
        );
        expect($response)->toBeEmpty();
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'validateProducts method throws an exception if derivate & product UOM does not match',
    function (): void {
        $unitOfMeasure2 = UnitOfMeasure::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'name' => 'uom2',
        ]);

        $unitOfMeasure1 = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'uom1',
        ]);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $unitOfMeasure1->id,
            'name' => 'abcd',
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure1;

        $goodsReceivedNoteProducts = getGrnRequestDetails('a12312', '2030-10-10', $derivative->name);

        $product = commonGetProductDetails();
        $product->unitOfMeasure = $unitOfMeasure2;

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldReceive('getByNumbers')
                ->once()
                ->with(['a12312'], 1)
                ->andReturn(new Collection([]));
        });

        $goodsReceivedNoteCheckRequestService = new GoodsReceivedNoteCheckRequestService();

        $response = $goodsReceivedNoteCheckRequestService->validateProducts(
            collect($goodsReceivedNoteProducts['goods_received_note_products']),
            collect([$product]),
            1,
            collect([$derivative])
        );
        expect($response)->toBeEmpty();
    }
)->throws(RedirectBackWithErrorException::class);

function getGrnRequestDetails($batchNumber = null, $batchExpiryDate = null, $derivativeName = null): array
{
    return [
        'purchase_order_reference' => 'do',
        'delivery_order_reference' => 'po',
        'notes' => 'test_notes',
        'goods_received_note_products' => [
            [
                'location_name' => 'new_location',
                'upc' => 'abd123',
                'quantity' => 10,
                'derivative_name' => $derivativeName,
                'fob' => null,
                'freight_charges' => null,
                'insurance_charges' => null,
                'duty' => null,
                'sst' => null,
                'handling_charges' => null,
                'other_charges' => null,
                'batch_number' => $batchNumber,
                'batch_expiry_date' => $batchExpiryDate,
            ],
        ],
    ];
}
