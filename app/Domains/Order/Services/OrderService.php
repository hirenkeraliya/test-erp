<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Domains\Order\OrderQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Models\Member;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OrderService
{
    public function getPaymentTypeList(int $companyId): Collection
    {
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        $paymentTypesList = $paymentTypeQueries->getActiveOnlyWithSubPaymentTypes($companyId);

        return $paymentTypesList->whereNotIn('id', [
            StaticPaymentTypes::BOOKING_PAYMENT->value,
            StaticPaymentTypes::CREDIT_NOTE->value,
            StaticPaymentTypes::LOYALTY_POINT->value,
            StaticPaymentTypes::GIFT_CARD->value,
        ])->sort()->values();
    }

    public function getPaginateData(
        array $filterData,
        int $storeManagerId,
        int $locationId,
        int $companyId,
        bool $isOnlyB2B,
    ): array {
        $orderQueries = resolve(OrderQueries::class);

        $ordersData = $orderQueries->getPaginatedCompleteOrderWithRelations(
            $filterData,
            $storeManagerId,
            $locationId,
            $companyId,
            $isOnlyB2B,
        );

        $consolidatedSales = $orderQueries->getFilteredTotalsForReport(
            $filterData,
            $storeManagerId,
            $locationId,
            $companyId,
            $isOnlyB2B,
        )->first()?->toArray();

        $consolidatedSales = null === $consolidatedSales ? null : head($consolidatedSales['order_items']);

        return [$ordersData, $consolidatedSales];
    }

    public function prepareDataForWithoutB2BCommerce(array $filterData): array
    {
        return $this->getPaginateData(
            $filterData,
            (int) $filterData['store_manager_id'],
            (int) $filterData['location_id'],
            (int) $filterData['company_id'],
            false,
        );
    }

    public function prepareDataForPrintMarketplaceOrder(array $filterData): Collection
    {
        $orderQueries = resolve(OrderQueries::class);

        return $orderQueries->getCompleteOrderWithRelationsForExport(
            $filterData,
            (int) $filterData['store_manager_id'],
            (int) $filterData['location_id'],
            (int) $filterData['company_id'],
            false,
        );
    }

    public function orderDataPrint(Collection $ordersData, int $moduleType, Collection $filteredColumns): Collection
    {
        $orderData = $ordersData->map(function (Order $order): array {
            /** @var Member $member */
            $member = $order->member;

            /** @var Collection $orderItems */
            $orderItems = $order->load('orderItems')->orderItems;

            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $order->getHappenedAt() ?? Carbon::now()->toDateTimeString()
            );
            $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

            $createdAt = $order->created_at ? $order->created_at->format('d-m-Y h:i:s A') : Carbon::now()->format(
                'd-m-Y h:i:s A'
            );

            $paymentType = $this->getPayments($order->payments);

            return [
                'digital_invoice_number' => $order->digital_invoice_number ?? 'N/A',
                'bill_reference_number' => $order->bill_reference_number ?? 'N/A',
                'receipt_number' => $order->receipt_number,
                'external_order_id' => $order->getOrderChannelReference()?->external_order_id ?? 'N/A',
                'created_at' => $createdAt,
                'happened_at' => $happenedAt,
                'member' => null != $member ? $member->getFullName() : 'Walk In Member',
                'channel' => Str::of($order->channel_id->name)->title()->replace('_', ' ')->value(),
                'payment_types' => [] === $paymentType ? '-' : $paymentType['name'],
                'logistic' => $order->courier_name ?? 'N/A',
                'units_sold' => $this->getTotalUnitsSold($orderItems),
                'status' => $order->status?->name ?? 'N/A',
            ];
        });
        $exportService = resolve(ExportService::class);

        return $exportService->exportDataMapping($orderData, $filteredColumns);
    }

    /**
     * @return mixed[]
     */
    private function getPayments(Collection $orderPayments): array
    {
        $payments = collect([]);
        $orderPayments->each(function ($orderPayment, string $key) use ($payments): void {
            $payments->push([
                'name' => $orderPayment->paymentType->name,
                'amount' => CommonFunctions::currencyFormat((float) $orderPayment->amount),
            ]);
        });

        return $payments->collapse()->toArray();
    }

    private function getTotalUnitsSold(Collection $orderItems): float
    {
        $totalUnitsSold = $orderItems->sum(fn ($orderItems): ?float => $orderItems->getQuantity());

        return CommonFunctions::numberFormat((float) $totalUnitsSold);
    }
}
