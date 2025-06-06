<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\PaymentType\Resources\PosPaymentTypeListResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentTypeController extends Controller
{
    /**
     * @return array<string, array|AnonymousResourceCollection>
     */
    public function getList(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $paymentTypesList = $paymentTypeQueries->getActiveOnlyAndAvailableInPosWithSubPaymentTypes(
            $companyId,
            $afterUpdatedAt
        );

        return [
            'static_payment_types' => StaticPaymentTypes::getList(),
            'payment_types' => PosPaymentTypeListResource::collection($paymentTypesList),
        ];
    }
}
