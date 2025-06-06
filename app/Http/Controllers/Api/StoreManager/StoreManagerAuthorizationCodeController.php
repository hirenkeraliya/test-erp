<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\CommonFunctions;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class StoreManagerAuthorizationCodeController extends Controller
{
    public function getAuthorizationCode(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerAuthorizationCodeQueries = resolve(StoreManagerAuthorizationCodeQueries::class);

        /** @var StoreManager $storeManager */
        $storeManager = $storeManagerQueries->loadEmployee($storeManager);

        /** @var Employee $employee */
        $employee = $storeManager->employee;

        $this->checkStoreManagerExists($storeManager->getKey());

        $code = $this->generateNewCode($employee->staff_id);

        $storeManagerAuthorizationCodeData = [
            'store_manager_id' => $storeManager->getKey(),
            'code' => $code,
            'expiry_date' => Carbon::now()->addHour()->format('Y-m-d H:i:s'),
        ];

        DB::beginTransaction();
        try {
            $storeManagerAuthorizationCodeQueries->addNew($storeManagerAuthorizationCodeData);
            DB::commit();

            return [
                'code' => $code,
            ];
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Store Manager API Get Authorization Code');

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    private function checkStoreManagerExists(int $storeManagerId): void
    {
        $storeManagerAuthorizationCodeQueries = resolve(StoreManagerAuthorizationCodeQueries::class);
        $storeManagerAuthorizationCode = $storeManagerAuthorizationCodeQueries->getWithStoreManager($storeManagerId);

        if (! $storeManagerAuthorizationCode instanceof StoreManagerAuthorizationCode) {
            return;
        }

        if ($storeManagerAuthorizationCode->getStatus() === StoreManagerAuthorizationCodeStatuses::CANCELLED) {
            return;
        }

        if ($storeManagerAuthorizationCode->getStatus() === StoreManagerAuthorizationCodeStatuses::EXPIRED && null !== $storeManagerAuthorizationCode->expiry_date) {
            return;
        }

        $storeManagerAuthorizationCodeQueries->cancelTheAuthorizationCode($storeManagerAuthorizationCode->getKey());
    }

    public function generateNewCode(string $storeManagerStaffId): string
    {
        $staticCodePrefix = 'SM';
        $randomSixCharacter = Str::random(6);
        $timeHourAndMinute = Carbon::now()->format('Hi');

        return $staticCodePrefix . $storeManagerStaffId . $randomSixCharacter . $timeHourAndMinute;
    }
}
