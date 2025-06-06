<?php

declare(strict_types=1);

namespace App\Domains\ExternalConnection\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ExternalCompany\Jobs\ExternalCompanyUpdateJob;
use App\Domains\ExternalConnection\DataObjects\ExternalConnectionData;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\Notification\NotificationQueries;
use App\Models\ExternalConnection;
use App\Models\Product;
use App\Models\SuperAdmin;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ExternalConnectionService
{
    public function sendNotification(ExternalConnection $externalConnection): void
    {
        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/set-notification',
            [
                'name' => config('app.name'),
                'url' => config('app.url'),
                'id' => $externalConnection->id,
            ]
        );
    }

    public function getExternalInventories(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/fetch-inventories',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function exportExternalInventories(array $filterData): BinaryFileResponse
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->get(
            $filterData['url'] . '/api/external-connection/export-inventories/' . $filterData['filename'],
            $filterData
        );

        if ($response->successful()) {
            $content = $response->body();

            $tempFilePath = tempnam(sys_get_temp_dir(), 'export');

            if (false === $tempFilePath) {
                abort(412, 'Failed to create temporary file');
            }

            if (file_put_contents($tempFilePath, $content) === false) {
                abort(412, 'Failed to write content to temporary file');
            }

            $binaryFileResponse = new BinaryFileResponse($tempFilePath);

            $binaryFileResponse->setContentDisposition('attachment', $filterData['filename']);

            return $binaryFileResponse;
        }

        abort(417, 'Something went wrong');
    }

    public function getFilteredExternalInventoryProducts(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-filtered-inventory-products',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getFilteredExternalInventoryCategories(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-filtered-inventory-categories',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getFilteredExternalInventoryBrands(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-filtered-inventory-brands',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getFilteredExternalInventorySizes(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-filtered-inventory-sizes',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getFilteredExternalInventoryAttributes(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-filtered-inventory-attributes',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getFilteredExternalInventoryColors(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-filtered-inventory-colors',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getFilteredExternalInventoryDepartments(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-filtered-inventory-departments',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getFilteredExternalInventoryArticleNumbers(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-filtered-inventory-article-numbers',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getFilteredExternalInventoryTags(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-filtered-inventory-tags',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getFilteredExternalInventoryStyles(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-filtered-inventory-styles',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getStoresWarehousesAndRegions(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-stores-warehouses-and-regions',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getStoresAndRegions(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-stores-and-regions',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function getWarehousesAndRegions(array $filterData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $filterData['url'] . '/api/external-connection/get-warehouses-and-regions',
            $filterData
        );

        return $this->handleResponse($response);
    }

    public function checkExternalConnectionAvailable(ExternalConnectionData $externalConnectionData): void
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->get(
                $externalConnectionData->url . '/api/external-connection/verify',
                [
                    'name' => config('app.name'),
                    'url' => config('app.url'),
                ]
            );

            if ($response->successful()) {
                $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
                if (! $data['is_exists']) {
                    return;
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('external_connection')->error('external login failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        abort(417, 'URL or name available on external site');
    }

    public function rejectExternalConnection(
        SuperAdmin $superAdmin,
        string $url,
        int $id,
        ?int $notificationId
    ): void {
        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post($url . '/api/external-connection/reject', [
            'id' => $id,
        ]);

        if ($notificationId) {
            $notificationQueries = resolve(NotificationQueries::class);
            $notificationQueries->markAsReadById($notificationId, $superAdmin->id, ModelMapping::SUPER_ADMIN->value);
        }
    }

    public function approveExternalConnection(
        SuperAdmin $superAdmin,
        string $url,
        int $id,
        ?int $notificationId
    ): void {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->get($url . '/api/external-connection/approve', [
            'id' => $id,
        ]);

        if ($response->successful()) {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            $externalConnectionData = new ExternalConnectionData(
                $data['name'],
                $data['url'],
                null,
                $superAdmin->id,
                now()->format('Y-m-d H:i:s'),
                null,
                $data['token'],
            );

            $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
            $externalConnection = $externalConnectionQueries->addNewWithApprove($externalConnectionData);

            if ($notificationId) {
                $notificationQueries = resolve(NotificationQueries::class);
                $notificationQueries->markAsReadById(
                    $notificationId,
                    $superAdmin->id,
                    ModelMapping::SUPER_ADMIN->value
                );
            }

            ExternalCompanyUpdateJob::dispatch($externalConnection->id)->onQueue('medium');
            $this->syncDataExternalConnection($externalConnection);
        }
    }

    public function syncDataExternalConnection(ExternalConnection $externalConnection): void
    {
        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/sync-data',
            [
                'token' => $externalConnection->token,
            ]
        );
    }

    public function sendProductDataExternalConnection(
        ExternalConnection $externalConnection,
        Product $product,
        int $receiverCompanyId,
        int $senderCompanyId
    ): void {
        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/send-external-product-data',
            [
                'token' => $externalConnection->token,
                'product' => json_encode($product->toArray()),
                'receiver_company_id' => $receiverCompanyId,
                'sender_company_id' => $senderCompanyId,
            ]
        );
    }

    private function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        }

        abort(417, 'Something went wrong');
    }
}
