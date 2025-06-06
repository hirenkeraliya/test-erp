<?php

declare(strict_types=1);

namespace App\Domains\Order\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Models\Member;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $orders,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->orders->map(function (Order $order): array {
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

            $paymentType = $this->getPayments($order->payments);

            $orderData = [
                'digital_invoice_number' => $order->digital_invoice_number ?: 'N/A',
                'bill_reference_number' => $order->bill_reference_number ?? 'N/A',
                'receipt_number' => $order->receipt_number,
                'external_order_id' => $order->getOrderChannelReference()?->external_order_id ?? 'N/A',
                'created_at' => $happenedAt,
                'member' => null != $member ? $member->getFullName() : 'Walk In Member',
                'channel' => Str::of($order->channel_id->name)->title()->replace('_', ' ')->value(),
                'payment_types' => [] === $paymentType ? '-' : $paymentType['name'],
                'logistic' => $order->courier_name ?? 'N/A',
                'units_sold' => $this->getTotalUnitsSold($orderItems),
                'status' => $order->status?->name ?? 'N/A',
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($orderData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
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
