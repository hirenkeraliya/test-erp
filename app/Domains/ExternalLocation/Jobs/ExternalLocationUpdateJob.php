<?php

declare(strict_types=1);

namespace App\Domains\ExternalLocation\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Models\ExternalConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalLocationUpdateJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $externalCompanyId,
    ) {
    }

    public function handle(): void
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getByIdWithExternalConnection($this->externalCompanyId);

        DB::beginTransaction();
        try {
            if ($externalCompany) {
                /** @var ExternalConnection $externalConnection */
                $externalConnection = $externalCompany->externalConnection;
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post(
                    $externalConnection->url . '/api/external-connection/get-locations',
                    [
                        'token' => $externalConnection->token,
                        'company_id' => $externalCompany->external_company_id,
                    ]
                );

                $externalLocations = $externalCompany->getExternalLocations();
                if ($response->successful()) {
                    $externalExternalLocations = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
                    foreach ($externalExternalLocations as $externalExternalLocation) {
                        $externalLocation = $externalLocations
                            ->firstWhere('external_location_id', $externalExternalLocation['id']);

                        $externalLocationRecord = [
                            'external_company_id' => $externalCompany->id,
                            'external_location_id' => $externalExternalLocation['id'],
                            'type_id' => $externalExternalLocation['type_id'],
                            'name' => $externalExternalLocation['name'],
                            'code' => $externalExternalLocation['code'],
                            'email' => $externalExternalLocation['email'],
                            'phone' => $externalExternalLocation['phone'],
                            'address_line_1' => $externalExternalLocation['address_line_1'],
                            'address_line_2' => $externalExternalLocation['address_line_2'],
                            'city' => $externalExternalLocation['city'],
                            'area_code' => $externalExternalLocation['area_code'],
                            'fax' => $externalExternalLocation['fax'],
                        ];

                        $externalLocationQueries = resolve(ExternalLocationQueries::class);
                        if ($externalLocation) {
                            $externalLocationQueries->update($externalLocation, $externalLocationRecord);

                            continue;
                        }

                        $externalLocationQueries->addNew($externalLocationRecord);
                    }
                }

                $superAdminQueries = resolve(SuperAdminQueries::class);
                $notificationQueries = resolve(NotificationQueries::class);
                $superAdmins = $superAdminQueries->getAll();
                foreach ($superAdmins as $superAdmin) {
                    $message = 'External Company ' . $externalCompany->name . ' location details updated successfully.';
                    $textMessage = 'External Company ' . $externalCompany->name . ' location details updated successfully.';
                    $notificationQueries->addNewWithNullValue(
                        ModelMapping::SUPER_ADMIN->name,
                        $superAdmin->id,
                        $message,
                        textMessage: $textMessage,
                    );
                }
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error([
                'error_name' => 'External Location Update Job job error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);

            $this->fail($throwable);
        }
    }
}
