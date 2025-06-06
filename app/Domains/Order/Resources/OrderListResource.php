<?php

declare(strict_types=1);

namespace App\Domains\Order\Resources;

use App\CommonFunctions;
use App\Domains\Order\Enums\OrderTypes;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\OrderReturn;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class OrderListResource extends JsonResource
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

        /** @var ?StoreManager $storeManager */
        $storeManager = $order->getStoreManager();
        $storeManagerEmployeeFullName = 'N/A';
        if ($storeManager) {
            /** @var Employee $storeManagerEmployee */
            $storeManagerEmployee = $storeManager->employee;
            $storeManagerEmployeeFullName = $storeManagerEmployee->getFullName();
        }

        /** @var Location $location */
        $location = $order->getLocation();

        /** @var Company $company */
        $company = $location->company;

        /** @var Country $country */
        $country = $company->defaultCountry;

        /** @var Currency $currency */
        $currency = $country->currency;

        $currencySymbol = $currency->getSymbol();

        /** @var ?OrderReturn $checkHasOrderReturn */
        $checkHasOrderReturn = $order->getCheckHasOrderReturn();

        /** @var ?Member $member */
        $member = $order->getMember();

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $order->getCreatedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $order->getKey(),
            'receipt_number' => $order->receipt_number,
            'bill_reference_number' => $order->bill_reference_number ?? 'N/A',
            'location' => $location->getNameWithCode(),
            'store_manager' => $storeManagerEmployeeFullName,
            'happened_at' => $happenedAt,
            'type_id' => $order->type_id->value,
            'type' => Str::of($order->type_id->name)->title()->replace('_', ' ')->value(),
            'channel' => Str::of($order->channel_id->name)->title()->replace('_', ' ')->value(),
            'member' => $member instanceof Member ? $member->getFullName() : 'Walk In Member',
            'gross_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->getGrossTotal(),
                false
            ),
            'total_tax_amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->getTotalTaxAmount(),
                false
            ),
            'total_discount_amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->getTotalDiscountAmount(),
                true
            ),
            'total_amount_paid' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->getTotalAmountPaid(),
                false
            ),
            'layaway_pending_amount' => CommonFunctions::numberFormat($order->getLayawayPendingAmount()),
            'credit_pending_amount' => CommonFunctions::numberFormat($order->getCreditPendingAmount()),
            'net_total' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $order->netAmount(),
                false
            ),
            'units_sold' => CommonFunctions::currencyFormat((float) $order->order_items_sum_quantity),
            'notes' => $order->notes ?? 'N/A',
            'order_types' => OrderTypes::getFormattedArrayForStaticUse(),
            'location_id' => $location->id,
            'store_manager_id' => $storeManager ? $storeManager->id : '',
            'is_order_returned' => $checkHasOrderReturn instanceof OrderReturn,
            'status' => $order->status,
            'digital_invoice_submitted' => $order->digital_invoice_submitted,
            'digital_invoice_number' => $order->digital_invoice_number ?: 'N/A',
        ];
    }
}
