<?php

declare(strict_types=1);

namespace App\Domains\ExternalCompany\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalLocation\Jobs\ExternalLocationUpdateJob;
use App\Domains\Notification\NotificationQueries;
use App\Domains\SuperAdmin\SuperAdminQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalCompanyUpdateJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly ?int $externalConnectionId = null,
    ) {
    }

    public function handle(): void
    {
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $externalConnections = $externalConnectionQueries->getAll($this->externalConnectionId);

        DB::beginTransaction();
        try {
            foreach ($externalConnections as $externalConnection) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post(
                    $externalConnection->url . '/api/external-connection/get-companies',
                    [
                        'token' => $externalConnection->token,
                    ]
                );

                if ($response->successful()) {
                    $companies = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
                    $externalCompanies = $externalConnection->getExternalCompanies();
                    foreach ($companies as $company) {
                        $externalCompany = $externalCompanies->firstWhere('external_company_id', $company['id']);

                        $externalCompanyRecord = [
                            'external_connection_id' => $externalConnection->id,
                            'external_company_id' => $company['id'],
                            'name' => $company['name'],
                            'code' => $company['code'],
                            'email' => $company['email'],
                            'fax' => $company['fax'],
                            'address' => $company['address'],
                            'social_security_number' => $company['social_security_number'],
                        ];

                        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
                        if ($externalCompany) {
                            $externalCompany = $externalCompanyQueries->update(
                                $externalCompany,
                                $externalCompanyRecord
                            );

                            $externalCompanyQueries->uploadLogos(
                                $externalCompany,
                                [
                                    'light_logo' => $company['light_logo'],
                                    'dark_logo' => $company['dark_logo'],
                                    'email_footer_logo' => $company['email_footer_logo'],
                                ]
                            );

                            ExternalLocationUpdateJob::dispatch($externalCompany->id)->onQueue('medium');

                            continue;
                        }

                        $externalCompany = $externalCompanyQueries->addNew($externalCompanyRecord);

                        $externalCompanyQueries->uploadLogos(
                            $externalCompany,
                            [
                                'light_logo' => $company['light_logo'],
                                'dark_logo' => $company['dark_logo'],
                                'email_footer_logo' => $company['email_footer_logo'],
                            ]
                        );

                        ExternalLocationUpdateJob::dispatch($externalCompany->id)->onQueue('medium');
                    }
                }

                $superAdminQueries = resolve(SuperAdminQueries::class);
                $notificationQueries = resolve(NotificationQueries::class);
                $superAdmins = $superAdminQueries->getAll();
                foreach ($superAdmins as $superAdmin) {
                    $message = 'External Connection Company details updated successfully.';
                    $textMessage = 'External Connection Company details updated successfully.';
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
                'error_name' => 'External Company Update Job job error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);

            $this->fail($throwable);
        }

        ExternalCompanyLocationUpdateJob::dispatch()->onQueue('medium');
    }
}
