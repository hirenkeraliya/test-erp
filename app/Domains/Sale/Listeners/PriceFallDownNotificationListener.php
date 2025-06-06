<?php

declare(strict_types=1);

namespace App\Domains\Sale\Listeners;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Sale\Events\SaleCreatedEvent;
use App\Domains\Sale\SaleQueries;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Collection;

class PriceFallDownNotificationListener
{
    /**
     * Handle the event.
     */
    public function handle(SaleCreatedEvent $event): void
    {
        /** @var Sale $sale */
        $sale = $event->sale;
        $notificationQueries = resolve(NotificationQueries::class);
        $saleQueries = resolve(SaleQueries::class);

        $saleData = $saleQueries->loadSaleItemAndOtherRelation($sale);

        /** @var Collection $saleItems */
        $saleItems = $saleData->saleItems;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $saleData->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($location->company_id);

        /** @var float $priceFallDownPercentage */
        $priceFallDownPercentage = CommonFunctions::numberFormat((float) $location->price_fall_down_percentage);

        /** @var Collection $storeManagers */
        $storeManagers = $location->storeManagers;

        foreach ($saleItems as $saleItem) {
            if ((float) $saleItem->original_price_per_unit <= 0.0) {
                continue;
            }

            $percentage = $this->getPercentagePriceDifference($saleItem);

            if ($percentage > $priceFallDownPercentage) {
                foreach ($storeManagers as $storeManager) {
                    $route = route('store_manager.sales.index', [
                        'offline_sale_id' => $sale->offline_sale_id,
                        'start_date' => $sale->happened_at,
                        'end_date' => $sale->happened_at,
                    ]);

                    $message = 'Receipt Id :<a href=' . $route . ' class="text-primary underline">' . $sale->offline_sale_id . '</a> of ' . $saleItem->product->name. ' price fall down with  '.
                        $percentage . '% against '. $priceFallDownPercentage .'% ('.CommonFunctions::currencySymbolDisplayWithAmount(
                            $currency->getSymbol(),
                            CommonFunctions::currencyFormat((float) $saleItem->price_paid_per_unit)
                        ).')';

                    $textMessage = 'Receipt Id :' . $sale->offline_sale_id . 'of ' . $saleItem->product->name. ' price fall down with  '. $percentage . '% against '. $priceFallDownPercentage .'% ('.CommonFunctions::currencySymbolDisplayWithAmount(
                        $currency->getSymbol(),
                        CommonFunctions::currencyFormat((float) $saleItem->price_paid_per_unit)
                    ).')';

                    $payload = [
                        'type' => ModelMapping::SALE->name,
                        'id' => $sale->id,
                        'location_id' => $location->getKey(),
                    ];

                    $notificationQueries->addNew(
                        $location->company_id,
                        ModelMapping::CASHIER->name,
                        $counterUpdate->cashier_id,
                        ModelMapping::STORE_MANAGER->name,
                        $storeManager->getKey(),
                        $message,
                        null,
                        $textMessage,
                        $payload,
                    );
                }
            }
        }
    }

    private function getPercentagePriceDifference(SaleItem $saleItem): float
    {
        $difference = $saleItem->original_price_per_unit - $saleItem->price_paid_per_unit;
        $percentagePriceDifference = ($difference / $saleItem->original_price_per_unit) * 100;

        return CommonFunctions::numberFormat((float) $percentagePriceDifference);
    }
}
