<?php

declare(strict_types=1);

use App\Domains\AutomatedNotificationProduct\Services\UpdateAutomatedNotificationProductService;
use App\Domains\Batch\BatchQueries;
use App\Domains\BookingPaymentProduct\BookingPaymentProductQueries;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Cashback\CashbackQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\HoldBookingPaymentItem\HoldBookingPaymentItemQueries;
use App\Domains\HoldSaleItem\HoldSaleItemQueries;
use App\Domains\HoldSaleReturnItem\HoldSaleReturnItemQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Media\MediaQueries;
use App\Domains\MemberProductReview\MemberProductReviewQueries;
use App\Domains\MergeProductTransaction\MergeProductTransactionQueries;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderReturnItem\OrderReturnItemQueries;
use App\Domains\Product\Jobs\ProductMergeJob;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductEcommerceService;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\SellThroughAggregate\Services\SellThroughAggregateServices;
use App\Domains\StockAdjustmentItem\StockAdjustmentItemQueries;
use App\Domains\StockTakeProduct\StockTakeProductQueries;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Models\Admin;
use App\Models\MergeProductTransaction;
use App\Models\ProductChannelReference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

test(
    'ProductMergeJob job calls respective methods as expected when product variant is ',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $productChannelReference = ProductChannelReference::factory()->make([
            'product_id' => 1,
            'sale_channel_id' => 1,
            'external_product_id' => 1,
            'external_variant_id' => 1,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($productVariant): void {
            if (! $productVariant) {
                $mock->shouldReceive('updateProductIdInTagsPivot')
                    ->once();
            }

            $mock->shouldReceive('deleteProduct')
                ->once();

            $mock->shouldReceive('restore')
                ->once();
        });

        $this->mock(MergeProductTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn(new MergeProductTransaction());
        });

        $this->mock(ProductChannelReferenceQueries::class, function ($mock) use ($productChannelReference): void {
            $mock->shouldReceive('getRecordsByProductId')
                ->times(2)
                ->andReturn(new Collection([$productChannelReference]));
        });

        $this->mock(ProductEcommerceService::class, function ($mock): void {
            $mock->shouldReceive('mergeProduct')
                ->once();
        });

        $this->mock(InventoryService::class, function ($mock): void {
            $mock->shouldReceive('mergeInventory')
                ->once();
        });

        $this->mock(CashbackQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductIdsInCashbackProductPivot')
                ->once();
        });

        $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductIdsInCategoryProductPivot')
                ->once();
        });

        $this->mock(PromotionQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductIdsInProductPromotionPivot')
                ->once();
        });

        $this->mock(VoucherConfigurationQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductIdsInVoucherConfigurationProductPivot')
                ->once();
        });

        $this->mock(StockTransferItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(MemberProductReviewQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(MediaQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductIdWithMedia')
                ->once();
        });

        $this->mock(StockTakeProductQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(StockAdjustmentItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(SaleReturnItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(OrderItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(OrderReturnItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(HoldSaleReturnItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(HoldSaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(HoldBookingPaymentItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(GoodsReceivedNoteProductQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(DreamPriceProductQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(BookingPaymentProductQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(ProductLoyaltyPointQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(PurchaseOrderItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(PurchaseOrderFulfillmentItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(BoxProductQueries::class, function ($mock): void {
            $mock->shouldReceive('updateProductId')
                ->once();
        });

        $this->mock(UpdateAutomatedNotificationProductService::class, function ($mock): void {
            $mock->shouldReceive('updateProduct')
                ->once();
        });

        $this->mock(SellThroughAggregateServices::class, function ($mock): void {
            $mock->shouldReceive('updateProductIdDuringProductMerge')
                ->once();
        });

        ProductMergeJob::dispatch($admin, 1, 1, 1)->onQueue(config('horizon.default_queue_name'));
    }
)->with([[true], [false]]);
