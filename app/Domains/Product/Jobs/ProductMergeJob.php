<?php

declare(strict_types=1);

namespace App\Domains\Product\Jobs;

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
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductMergeJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected User $user,
        protected int $oldProductId,
        protected int $newProductId,
        protected int $companyId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);
        $stockAdjustmentItemQueries = resolve(StockAdjustmentItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);

        $holdSaleReturnItemQueries = resolve(HoldSaleReturnItemQueries::class);
        $holdSaleItemQueries = resolve(HoldSaleItemQueries::class);
        $holdBookingPaymentItemQueries = resolve(HoldBookingPaymentItemQueries::class);
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $dreamPriceProductQueries = resolve(DreamPriceProductQueries::class);
        $bookingPaymentProductQueries = resolve(BookingPaymentProductQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $promotionQueries = resolve(PromotionQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $cashbackQueries = resolve(CashbackQueries::class);
        $mergeProductTransactionQueries = resolve(MergeProductTransactionQueries::class);
        $inventoryService = resolve(InventoryService::class);
        $productQueries = resolve(ProductQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $updateAutomatedNotificationProductService = resolve(UpdateAutomatedNotificationProductService::class);
        $sellThroughAggregateService = resolve(SellThroughAggregateServices::class);
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productEcommerceService = resolve(ProductEcommerceService::class);
        $memberProductReviewQueries = resolve(MemberProductReviewQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        DB::beginTransaction();

        try {
            $mergeProductTransactionQueries->addNew($this->user, $this->oldProductId, $this->newProductId);
            $updateAutomatedNotificationProductService->updateProduct($this->oldProductId, $this->newProductId);
            $inventoryService->mergeInventory($this->oldProductId, $this->newProductId);
            $cashbackQueries->updateProductIdsInCashbackProductPivot($this->oldProductId, $this->newProductId);
            $categoryQueries->updateProductIdsInCategoryProductPivot($this->oldProductId, $this->newProductId);
            $promotionQueries->updateProductIdsInProductPromotionPivot($this->oldProductId, $this->newProductId);
            $voucherConfigurationQueries->updateProductIdsInVoucherConfigurationProductPivot(
                $this->oldProductId,
                $this->newProductId
            );
            $memberProductReviewQueries->updateProductId($this->oldProductId, $this->newProductId);

            if (! config('app.product_variant')) {
                $productQueries->updateProductIdInTagsPivot($this->oldProductId, $this->newProductId);
            }

            $stockTransferItemQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $stockTakeProductQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $stockAdjustmentItemQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $saleReturnItemQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $saleItemQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $orderItemQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $orderReturnItemQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $holdSaleReturnItemQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $holdSaleItemQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $holdBookingPaymentItemQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $goodsReceivedNoteProductQueries->updateProductId(
                $this->companyId,
                $this->oldProductId,
                $this->newProductId
            );
            $dreamPriceProductQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $bookingPaymentProductQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $batchQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $productLoyaltyPointQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $purchaseOrderItemQueries->updateProductId($this->companyId, $this->oldProductId, $this->newProductId);
            $mediaQueries->updateProductIdWithMedia($this->oldProductId, $this->newProductId);
            $purchaseOrderFulfillmentItemQueries->updateProductId(
                $this->companyId,
                $this->oldProductId,
                $this->newProductId
            );
            $boxProductQueries->updateProductId($this->oldProductId, $this->newProductId);
            $sellThroughAggregateService->updateProductIdDuringProductMerge($this->oldProductId, $this->newProductId);

            $oldProductChannelReferences = $productChannelReferenceQueries->getRecordsByProductId($this->oldProductId);
            $newProductChannelReferences = $productChannelReferenceQueries->getRecordsByProductId($this->newProductId);

            $productQueries->deleteProduct($this->companyId, $this->oldProductId);
            $productQueries->restore($this->newProductId, $this->companyId);

            DB::commit();

            if ($oldProductChannelReferences->isNotEmpty()) {
                $productEcommerceService->mergeProduct(
                    $this->oldProductId,
                    $this->newProductId,
                    $this->companyId,
                    $oldProductChannelReferences,
                    $newProductChannelReferences
                );
            }
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Product Merge Error', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
