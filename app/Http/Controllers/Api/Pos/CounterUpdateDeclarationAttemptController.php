<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\CounterUpdateDeclarationAttempt\CounterUpdateDeclarationAttemptQueries;
use App\Domains\CounterUpdateDeclarationAttempt\DataObjects\CounterUpdateDeclarationAttemptData;
use App\Domains\CounterUpdateDeclarationAttempt\Resources\PosCounterUpdateDeclarationAttemptListResource;
use App\Domains\CounterUpdateDeclarationAttemptPayment\CounterUpdateDeclarationAttemptPaymentQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CounterUpdateDeclarationAttemptController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function getList(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        $counterUpdateDeclarationAttemptQueries = resolve(CounterUpdateDeclarationAttemptQueries::class);
        $counterUpdateDeclarationAttemptList = $counterUpdateDeclarationAttemptQueries->getList(
            $cashier->counter_update_id, $afterUpdatedAt
        );

        return [
            'counter_update_declaration_attempts' => PosCounterUpdateDeclarationAttemptListResource::collection(
                $counterUpdateDeclarationAttemptList
            ),
        ];
    }

    public function store(
        Request $request,
        CounterUpdateDeclarationAttemptData $counterUpdateDeclarationAttemptData
    ): void {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        $counterUpdateDeclarationAttemptQueries = resolve(CounterUpdateDeclarationAttemptQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $counterUpdateDeclarationAttemptPaymentQueries = resolve(CounterUpdateDeclarationAttemptPaymentQueries::class);

        $counterUpdateDeclarationAttemptPayments = collect($counterUpdateDeclarationAttemptData->payments);

        $paymentTypeIds = $counterUpdateDeclarationAttemptPayments->pluck('payment_type_id')
            ->filter()
            ->unique()
            ->toArray();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        if (! $paymentTypeQueries->checkExistingPaymentTypeIds($paymentTypeIds, $companyId)) {
            abort(412, 'Some of the payment types do not exist in our records.');
        }

        DB::beginTransaction();

        try {
            $counterUpdateDeclarationAttemptId = $counterUpdateDeclarationAttemptQueries->addNew(
                $counterUpdateDeclarationAttemptData->offline_id,
                $counterUpdateDeclarationAttemptData->happened_at,
                $cashier->counter_update_id
            );

            $counterUpdateDeclarationAttemptPaymentsData = $counterUpdateDeclarationAttemptPayments->map(
                function (array $payment) use ($counterUpdateDeclarationAttemptId): array {
                    $payment['counter_update_declaration_attempt_id'] = $counterUpdateDeclarationAttemptId;
                    $payment['created_at'] = now()->format('Y-m-d H:i:s');
                    $payment['updated_at'] = now()->format('Y-m-d H:i:s');
                    $payment['denominations'] = array_key_exists('denominations', $payment) ? json_encode(
                        $payment['denominations'],
                        JSON_THROW_ON_ERROR
                    ) : null;

                    return $payment;
                }
            )->toArray();

            $counterUpdateDeclarationAttemptPaymentQueries->createMany($counterUpdateDeclarationAttemptPaymentsData);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Attempt to update counter declaration', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }
}
