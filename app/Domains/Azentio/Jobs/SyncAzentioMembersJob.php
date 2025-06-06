<?php

declare(strict_types=1);

namespace App\Domains\Azentio\Jobs;

use App\CommonFunctions;
use App\Domains\Azentio\DataObjects\AzentioMemberData;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Domains\IntegrationSyncUpdate\IntegrationSyncUpdateQueries;
use App\Domains\Member\MemberQueries;
use App\Models\IntegrationSyncUpdate;
use App\Services\AzentioOneerpService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncAzentioMembersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 3;

    protected int $batchSize = 1000;

    public function __construct(
        private ?int $integrationId = null,
        private int $startRowFrom = 1,
        private ?string $fromDate = null,
        private ?string $toDate = null
    ) {
    }

    public function handle(): void
    {
        /** @var IntegrationQueries $integrationQueries */
        $integrationQueries = resolve(IntegrationQueries::class);

        $integrations = $integrationQueries->getIntegrationsByConnectionId(IntegrationConnections::ONE_ERP);

        if ($integrations->isEmpty()) {
            return;
        }

        Log::channel('azentio_oneerp_integration')->info('Start fetching members from OneERP', [
            'start_time' => now()->format('Y-m-d H:i:s'),
        ]);

        if ($this->integrationId && $this->startRowFrom && $this->fromDate && $this->toDate) {
            $this->handleContinuationJob();

            return;
        }

        /** @var IntegrationSyncUpdateQueries $integrationSyncUpdateQueries */
        $integrationSyncUpdateQueries = resolve(IntegrationSyncUpdateQueries::class);

        $azentioOneerpService = new AzentioOneerpService();

        $defaultFromDate = config('services.azentio_integration.from_date');
        $toDate = Carbon::now()->format('d/m/Y H:i:s');

        $validFormat = false;
        try {
            $validDate = Carbon::createFromFormat('d/m/Y H:i:s', $defaultFromDate);
            $validFormat = $validDate && $validDate->format('d/m/Y H:i:s') === $defaultFromDate;
        } catch (Exception) {
            $validFormat = false;
        }

        if (! $validFormat) {
            $defaultFromDate = Carbon::yesterday()->format('d/m/Y H:i:s');
        }

        foreach ($integrations as $integration) {
            $fromDate = $defaultFromDate;

            $integrationSyncUpdate = $integrationSyncUpdateQueries->getByIntegrationIdAndModuleType(
                $integration->id,
                ModelMapping::MEMBER->name,
            );

            if ($integrationSyncUpdate instanceof IntegrationSyncUpdate) {
                /** @var Carbon $lastSyncDate */
                $lastSyncDate = Carbon::createFromFormat('Y-m-d H:i:s', $integrationSyncUpdate->last_sync_date);
                $fromDate = $lastSyncDate->startOfDay()->format('d/m/Y H:i:s');
            }

            $azentioOneerpService->setDetails($integration->url, $integration->secret, $fromDate, $toDate);

            $this->processItemsForIntegration(
                $azentioOneerpService,
                $fromDate,
                $toDate,
                $integration->getKey(),
                $this->startRowFrom
            );
        }

        Log::channel('azentio_oneerp_integration')->info('Complete fetching items from OneERP', [
            'end_time' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function handleContinuationJob(): void
    {
        if (null === $this->integrationId || null === $this->fromDate || null === $this->toDate) {
            return;
        }

        /** @var IntegrationQueries $integrationQueries */
        $integrationQueries = resolve(IntegrationQueries::class);

        $azentioOneerpService = new AzentioOneerpService();

        $integration = $integrationQueries->getByIdAndStatus($this->integrationId);

        $azentioOneerpService->setDetails($integration->url, $integration->secret, $this->fromDate, $this->toDate);

        $this->processItemsForIntegration(
            $azentioOneerpService,
            $this->fromDate,
            $this->toDate,
            $this->integrationId,
            $this->startRowFrom
        );
    }

    private function processItemsForIntegration(
        AzentioOneerpService $azentioOneerpService,
        string $fromDate,
        string $toDate,
        int $integrationId,
        int $rowNumFrom = 1
    ): void {
        $rowNumTo = $rowNumFrom + $this->batchSize - 1;
        try {
            $response = $azentioOneerpService->getMembers($rowNumFrom, $rowNumTo);

            if (! $response->successful()) {
                Log::channel('azentio_oneerp_integration')->error('API request failed', [
                    'integration_id' => $integrationId,
                    'row_from' => $rowNumFrom,
                    'row_to' => $rowNumTo,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                if (in_array($response->status(), [404, 204])) {
                    return;
                }
            }

            $responseData = $response->json();

            if (empty($responseData) || ! is_array($responseData)) {
                Log::channel('azentio_oneerp_integration')->info('Empty response received', [
                    'integration_id' => $integrationId,
                    'row_from' => $rowNumFrom,
                ]);

                $this->addOrUpdateSyncDetails($integrationId, ModelMapping::MEMBER->name, $toDate);

                return;
            }

            if (
                array_key_exists('Message', $responseData) &&
                'No Records Found' === $responseData['Message']
            ) {
                Log::channel('azentio_oneerp_integration')->info('No more records found', [
                    'integration_id' => $integrationId,
                    'row_from' => $rowNumFrom,
                ]);

                $this->addOrUpdateSyncDetails($integrationId, ModelMapping::MEMBER->name, $toDate);

                return;
            }

            $recordCount = count($responseData);

            $this->saveMembers($responseData, $integrationId);

            Log::channel('azentio_oneerp_integration')->info('Batch processed successfully', [
                'integration_id' => $integrationId,
                'row_from' => $rowNumFrom,
                'row_to' => $rowNumTo,
                'records_in_batch' => $recordCount,
            ]);

            if ($recordCount < $this->batchSize) {
                Log::channel('azentio_oneerp_integration')->info('Partial batch received - likely last batch', [
                    'integration_id' => $integrationId,
                    'expected' => $this->batchSize,
                    'received' => $recordCount,
                ]);

                $this->addOrUpdateSyncDetails($integrationId, ModelMapping::MEMBER->name, $toDate);

                return;
            }

            $rowNumFrom = $rowNumTo + 1;
        } catch (Exception $exception) {
            Log::channel('azentio_oneerp_integration')->error('Error processing batch', [
                'integration_id' => $integrationId,
                'row_from' => $rowNumFrom,
                'row_to' => $rowNumTo,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }

        self::dispatch($integrationId, $rowNumFrom, $fromDate, $toDate);
    }

    private function addOrUpdateSyncDetails(int $integrationId, string $moduleType, string $lastSyncDate): void
    {
        /** @var IntegrationSyncUpdateQueries $integrationSyncUpdateQueries */
        $integrationSyncUpdateQueries = resolve(IntegrationSyncUpdateQueries::class);

        /** @var Carbon $carbonDate */
        $carbonDate = Carbon::createFromFormat('d/m/Y H:i:s', $lastSyncDate);

        DB::beginTransaction();

        try {
            $integrationSyncUpdateQueries->createOrUpdateSyncDetails(
                $integrationId,
                $moduleType,
                $carbonDate->format('Y-m-d H:i:s')
            );

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::channel('azentio_oneerp_integration')->error('Failed to update sync details', [
                'integration_id' => $integrationId,
                'module_type' => $moduleType,
                'last_sync_date' => $lastSyncDate,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    private function saveMembers(array $itemDetails, int $integrationId): void
    {
        /** @var IntegrationQueries $integrationQueries */
        $integrationQueries = resolve(IntegrationQueries::class);

        /** @var MemberQueries $memberQueries */
        $memberQueries = resolve(MemberQueries::class);

        $integration = $integrationQueries->getByIdAndStatus($integrationId);

        DB::beginTransaction();

        try {
            foreach ($itemDetails as $item) {
                $contactDetails = $item['CUSTOMER']['CONTACT'] ?? [];
                foreach ($contactDetails as $contactDetail) {
                    $contactDetail['COMPANY_ID'] = $integration->getAttribute('company_id');
                    $contactDetail['CARD_NUMBER'] = $memberQueries->generateUniqueCardNumber();

                    try {
                        AzentioMemberData::validate($contactDetail);
                    } catch (Throwable $throwable) {
                        CommonFunctions::logChannelErrorDetails(
                            $throwable,
                            'azentio_oneerp_integration',
                            'Validation failed for member',
                            [
                                'integrationId' => $integrationId,
                                'contactDetails' => $contactDetail,
                            ]);
                        continue;
                    }

                    $memberQueries->createOrUpdateMemberFromAzentioMember(
                        AzentioMemberData::from($contactDetail)->toArray()
                    );
                }
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            CommonFunctions::logChannelErrorDetails(
                $throwable,
                'azentio_oneerp_integration',
                'Error saving items to members table',
                [
                    'integrationId' => $integrationId,
                ]);
        }
    }
}
