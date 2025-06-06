<?php

declare(strict_types=1);

namespace App\Domains\OrderReturn\Resources;

use App\CommonFunctions;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderReturnListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $orderReturn = $this->resource;

        /** @var StoreManager $storeManager */
        $storeManager = $orderReturn->getStoreManager();

        /** @var Order $order */
        $order = $orderReturn->getOriginalOrder();

        /** @var Location $location */
        $location = $orderReturn->getLocation();

        /** @var Company $company */
        $company = $location->company;

        /** @var Country $country */
        $country = $company->defaultCountry;

        /** @var Currency $currency */
        $currency = $country->currency;

        $currencySymbol = $currency->getSymbol();

        /** @var Employee $storeManagerEmployee */
        $storeManagerEmployee = $storeManager->employee;

        /** @var ?Member $member */
        $member = $orderReturn->getMember();

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $orderReturn->getCreatedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $orderReturn->getKey(),
            'receipt_number' => $orderReturn->getReceiptNumber(),
            'original_order_receipt_number' => $order->getReceiptNumber(),
            'location' => $location->getNameWithCode(),
            'store_manager' => $storeManagerEmployee->getFullName(),
            'created_at' => $happenedAt,
            'member' => $member instanceof Member ? $member->getFullName() : 'Walk In Member',
            'total_price_paid' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                $orderReturn->total_price_paid
            ),
            'digital_invoice_submitted' => $orderReturn->digital_invoice_submitted,
            'digital_invoice_number' => $orderReturn->digital_invoice_number ?: 'N/A',
        ];
    }
}
