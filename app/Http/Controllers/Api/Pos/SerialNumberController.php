<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Sale\Resources\SerialNumberDetailsResource;
use App\Domains\SerialNumber\SerialNumberQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;

class SerialNumberController extends Controller
{
    public function getSerialNumberDetail(Request $request, int|string $number): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $serialNumberQueries = resolve(SerialNumberQueries::class);
        $serialNumber = $serialNumberQueries->loadRelation($number, $companyId);

        return [
            'serial_number_details' => new SerialNumberDetailsResource($serialNumber),
        ];
    }
}
