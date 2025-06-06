<?php

declare(strict_types=1);

namespace App\Domains\Order\Resources;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMappingTypes;
use App\Domains\Order\Enums\OrderTypes;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Location;
use App\Models\Member;
use App\Models\OrderPayment;
use App\Models\PaymentType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MarketPlaceOrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $order = $this->resource;

        /** @var ?Member $member */
        $member = $order->getMember();

        /** @var Location $location */
        $location = $order->getLocation();

        /** @var Company $company */
        $company = $location->company;

        /** @var Country $country */
        $country = $company->defaultCountry;

        /** @var Currency $currency */
        $currency = $country->currency;

        /** @var Collection $orderPayments */
        $orderPayments = $order->payments;

        $currencySymbol = $currency->getSymbol();

        $module = 'N/A';

        if ((int) $request->module_type === ModelMappingTypes::BASE_MODULES->value) {
            if (null !== $order->parent_module_name) {
                $module = CommonFunctions::stringTitleLowerCase((string) $order->parent_module_name);
            }

            if (null === $order->parent_module_name) {
                $module = CommonFunctions::stringTitleLowerCase((string) $order->subject_type);
            }
        }

        return [
            'id' => $order->getKey(),
            'receipt_number' => $order->receipt_number,
            'bill_reference_number' => $order->getBillReferenceNumber() ?? 'N/A',
            'type_id' => $order->type_id->value,
            'type' => Str::of($order->type_id->name)->title()->replace('_', ' ')->value(),
            'channel' => Str::of($order->channel_id->name)->title()->replace('_', ' ')->value(),
            'member' => $member instanceof Member ? $member->getFullName() : 'Walk In Member',
            'units_sold' => CommonFunctions::numberFormat((float) $order->order_items_sum_quantity),
            'net_total' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->netAmount(),
                false
            ),
            'order_types' => OrderTypes::getFormattedArrayForStaticUse(),
            'status_id' => $order?->status,
            'status' => $order?->status?->name,
            'logistic' => $order->courier_name ?? 'N/A',
            'payment_types' => $this->getPreparedPayments($orderPayments),
            'created_at' => $order->created_at->format('d-m-Y h:i:s A'),
            'digital_invoice_submitted' => $order->digital_invoice_submitted,
            'digital_invoice_number' => $order->digital_invoice_number ?: 'N/A',
            'location' => $location->getNameWithCode(),
            'order_channel_reference' => $order->getOrderChannelReference(),
            'module' => $module,
            'happened_at' => Carbon::parse($order->happened_at)->format('d-m-Y h:i:s A'),
        ];
    }

    public function getPreparedPayments(?Collection $orderPayments): string
    {
        if (! $orderPayments instanceof Collection) {
            return '';
        }

        $paymentTypes = $orderPayments->map(function ($payment): string {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $payment;
            /** @var PaymentType $paymentType */
            $paymentType = $orderPayment->paymentType;

            return $paymentType->getName();
        })->toArray();

        return implode(', ', $paymentTypes);
    }
}
