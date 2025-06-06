<?php

declare(strict_types=1);

namespace App\Domains\CurrencyRate\Jobs;

use App\Domains\Company\CompanyQueries;
use App\Domains\CurrencyRate\CurrencyRateQueries;
use App\Services\CurrencyRatesUpdateService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CurrencyRateUpdateJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('currency_rate_update_service')->info('Currency rate update job', [
            'Start time of currency rate update job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        $currencyRatesUpdateService = resolve(CurrencyRatesUpdateService::class);
        $currencyRateQueries = resolve(CurrencyRateQueries::class);
        $companyQueries = resolve(CompanyQueries::class);

        try {
            $companies = $companyQueries->getCompanies();
            foreach ($companies as $company) {
                $defaultCurrency = $company->defaultCountry->currency;
                $response = $currencyRatesUpdateService->getCurrencyRate($defaultCurrency->code);

                if (! $response['status']) {
                    Log::channel('currency_rate_update_service')->error('currency_rate_update_service', [
                        'message' => 'Something Went Wrong.',
                        'response_data' => $response,
                    ]);

                    continue;
                }

                $currencyRateQueries->deleteOldRate($company->id);
                $allCurrencyRates = $response['response_data']['conversion_rates'] ?? [];

                foreach ($company->countries as $country) {
                    $currency = $country->currency;
                    if (array_key_exists($currency->code, $allCurrencyRates)) {
                        $data = [
                            'company_id' => $company->id,
                            'currency_id' => $currency->id,
                            'rate' => $allCurrencyRates[$currency->code],
                        ];
                        $currencyRateQueries->add($data);
                    }
                }
            }
        } catch (Throwable $throwable) {
            Log::error('Currency rate update job failed', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('currency_rate_update_service')->info('Currency rate update job', [
            'Completed time of currency rate update job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
