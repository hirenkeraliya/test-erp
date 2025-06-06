<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment\Services;

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Models\BookingPayment;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Member;
use App\Models\PaymentType;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PrintBookingPaymentReportService
{
    public function print(int $bookingPaymentId, int $companyId, ?int $locationId): string
    {
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);

        $bookingPaymentPrintDetails = $bookingPaymentQueries->getDetailsForPrint(
            $bookingPaymentId,
            $companyId,
            $locationId
        );
        $bookingPaymentDetails = $this->preparedData($bookingPaymentPrintDetails);
        [$company, $location] = $this->preparedCompanyAndLocation($bookingPaymentPrintDetails);

        return view('prints.booking_payment_report', [
            'bookingPaymentDetails' => $bookingPaymentDetails,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'location' => $location,
        ])->render();
    }

    /**
     * @return mixed[]
     */
    private function preparedData(BookingPayment $bookingPayment): array
    {
        /** @var Collection $bookingPaymentProducts */
        $bookingPaymentProducts = $bookingPayment->products;

        /** @var Collection $bookingPaymentPayments */
        $bookingPaymentPayments = $bookingPayment->bookingPaymentPayments;

        /** @var Member|null $member */
        $member = $bookingPayment->member;

        $userDataPrepare = resolve(UserDataPreparer::class);

        return [
            'user_details' => $userDataPrepare->getMemberNameAndAddressDetails($member),
            'total_amount' => $bookingPayment->total_amount,
            'bill_reference_number' => $bookingPayment->bill_reference_number,
            'remarks' => $bookingPayment->remarks,
            'products' => $this->getPreparedProducts($bookingPaymentProducts),
            'payments' => $bookingPaymentPayments->map(function ($bookingPaymentPayment): array {
                /** @var PaymentType $paymentType */
                $paymentType = $bookingPaymentPayment->paymentType;

                return [
                    'id' => $bookingPaymentPayment->getKey(),
                    'payment_type' => $paymentType->name,
                    'amount' => (float) $bookingPaymentPayment->amount,
                ];
            }),
        ];
    }

    private function preparedCompanyAndLocation(BookingPayment $bookingPayment): array
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $bookingPayment->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        /** @var Company $company */
        $company = $location->company;

        return [$company, $location];
    }

    private function getPreparedProducts(Collection $bookingPaymentProducts): array
    {
        return $bookingPaymentProducts->map(function ($bookingPaymentProduct): array {
            /** @var Product $product */
            $product = $bookingPaymentProduct->product;

            $productService = resolve(ProductService::class);

            return [
                'product' => $product->getName(),
                'color' => config('app.product_variant') ? null : $product?->color?->name ?? null,
                'size' => config('app.product_variant') ? null : $product?->size?->name ?? null,
                'upc' => $product->getUpc(),
                'quantity' => $bookingPaymentProduct->quantity,
                'attributes' => $productService->getAttributesWithNameAndValueKey($product),
            ];
        })->toArray();
    }
}
