<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\Admin\AdminQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ExternalCompany\Jobs\ExternalCompanyUpdateJob;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalProduct\ExternalProductQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExternalConnectionController extends Controller
{
    public function __construct(
        protected ExternalConnectionQueries $externalConnectionQueries
    ) {
    }

    public function setNotification(Request $request): void
    {
        $superAdminQueries = resolve(SuperAdminQueries::class);
        $notificationQueries = resolve(NotificationQueries::class);
        $superAdmins = $superAdminQueries->getAll();
        foreach ($superAdmins as $superAdmin) {
            $message = $this->getNotificationMessage($request->name, $request->url, (int) $request->id, null);

            $notification = $notificationQueries->addNewWithNullValue(
                ModelMapping::SUPER_ADMIN->name,
                $superAdmin->id,
                $message
            );

            $message = $this->getNotificationMessage(
                $request->name,
                $request->url,
                (int) $request->id,
                $notification->id
            );

            $notificationQueries->updateMessage($notification, $message);
        }
    }

    public function reject(Request $request): void
    {
        $this->externalConnectionQueries->reject((int) $request->id);
    }

    public function approve(Request $request): array
    {
        $externalConnection = $this->externalConnectionQueries->approve((int) $request->id);

        return [
            'name' => config('app.name'),
            'url' => config('app.url'),
            'token' => $externalConnection->token,
        ];
    }

    public function verify(Request $request): array
    {
        $externalConnection = $this->externalConnectionQueries->existsByNameOrUrl($request->name, $request->url);

        return [
            'is_exists' => $externalConnection,
        ];
    }

    public function syncData(Request $request): void
    {
        if (! $request->token) {
            return;
        }

        $externalConnection = $this->externalConnectionQueries->getByToken($request->token);
        ExternalCompanyUpdateJob::dispatch($externalConnection->id)->onQueue('medium');
    }

    public function sendExternalProductData(Request $request): void
    {
        $this->externalConnectionQueries->getByToken($request->token);

        $product = json_decode($request->product);
        $receiverCompanyId = $request->receiver_company_id;
        $senderCompanyId = $request->sender_company_id;

        // Here we are checking product is exist or not as event listener is running to avoid infinite creation.
        $productQueries = resolve(ProductQueries::class);
        if ($productQueries->existsByUpc($product->upc, $receiverCompanyId)) {
            return;
        }

        $externalProductQueries = resolve(ExternalProductQueries::class);
        $externalProductIsCreated = $externalProductQueries->addNew(
            (array) $product,
            $receiverCompanyId,
            $senderCompanyId
        );
        if ($externalProductIsCreated) {
            $this->sendNotificationMessageToAdminByCompanyId($product->company_id, $product->upc);
        }
    }

    private function getNotificationMessage(
        string $externalConnectionName,
        string $url,
        int $externalConnectionId,
        ?int $notificationId
    ): string {
        $rejectRoute = route('super_admin.external_connections.reject', [
            'url' => $url,
            'id' => $externalConnectionId,
            'notification_id' => $notificationId,
        ]);

        $approveRoute = route('super_admin.external_connections.approve', [
            'url' => $url,
            'id' => $externalConnectionId,
            'notification_id' => $notificationId,
        ]);

        if (! $notificationId) {
            $rejectRoute = route('super_admin.external_connections.reject', [
                'url' => $url,
                'id' => $externalConnectionId,
            ]);

            $approveRoute = route('super_admin.external_connections.approve', [
                'url' => $url,
                'id' => $externalConnectionId,
            ]);
        }

        return $externalConnectionName . ' is requested Inter Company Transfer: <a href="' . $approveRoute . '" class="text-primary underline cursor-pointer" onclick="return confirm(\'Are you sure you want to approve this request?\')">Click here to approve</a> or <a href="' . $rejectRoute . '" class="text-primary underline cursor-pointer" onclick="return confirm(\'Are you sure you want to reject this request?\')">Click here to reject</a>';
    }

    private function sendNotificationMessageToAdminByCompanyId(int $companyId, string $upc): void
    {
        $adminQueries = resolve(AdminQueries::class);
        $notificationQueries = resolve(NotificationQueries::class);
        $admins = $adminQueries->getAdminListByCompanyId($companyId);
        foreach ($admins as $admin) {
            $message = 'External Product : ' . $upc . ' is Available.';
            $notificationQueries->addNewWithNullValue(
                ModelMapping::ADMIN->name,
                (int) $admin->id,
                $message,
                $companyId
            );
        }
    }
}
