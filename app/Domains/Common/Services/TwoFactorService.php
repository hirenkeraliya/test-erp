<?php

namespace App\Domains\Common\Services;

use App\Domains\Admin\AdminQueries;
use App\Domains\Admin\DataObjects\AdminData;
use App\Domains\Panel\Service\PanelManagementService;
use App\Domains\Role\RoleQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\SuperAdmin\DataObjects\SuperAdminData;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\StoreManager;
use App\Models\SuperAdmin;
use App\Models\WarehouseManager;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Exception;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Recovery\Recovery;
use Throwable;

class TwoFactorService extends Controller
{
    /**
     * This function is used to generate the secret key and QR code for 2FA.
     */
    public function generate2FA(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getAuthUser($request);
        [$email, $id] = $this->getEmailAndIDFromUserType($user);

        if (! $email || ! $id) {
            return $this->handle2FAError(
                new Exception('Employee email not found for this user.'),
                '"Employee email not found for this user."'
            );
        }

        try {
            $google2fa = new Google2FA();
            $secret = $google2fa->generateSecretKey();

            $recovery = new Recovery();
            $recoveryCodes = $recovery->numeric()->toArray();

            $qrCodeSvg = $this->generateQRCodeSvg($google2fa, $email, $secret);
            $this->cacheSecret($id, $secret);

            return $this->generate2FAResponse($qrCodeSvg, $secret, $recoveryCodes);
        } catch (Throwable $throwable) {
            return $this->handle2FAError($throwable, 'Something went wrong! Please try again later');
        }
    }

    private function generateQRCodeSvg(Google2FA $google2fa, string $email, string $secret): string
    {
        $qrCodeData = $google2fa->getQRCodeUrl(config('app.name'), $email, $secret);
        $imageRenderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
        $writer = new Writer($imageRenderer);

        return $writer->writeString($qrCodeData);
    }

    private function cacheSecret(int $id, string $secret): void
    {
        Cache::put('2fa_secret_' . $id, $secret, now()->addMinutes(10));
    }

    private function generate2FAResponse(string $qrCodeSvg, string $secret, array $recoveryCodes): JsonResponse
    {
        return response()->json([
            'qrCodeSvg' => $qrCodeSvg,
            'secret' => $secret,
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    /**
     * This function is used to Validate OTP at the time of login.
     */
    public function validateOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required',
        ]);

        $google2fa = App::get('pragmarx.google2fa');
        $guard = $this->getActiveGuard($request);

        /** @var Admin|StoreManager|WarehouseManager|SuperAdmin|null $user */
        $user = $this->getAuthUser($request);
        if (! $user) {
            return redirect('/' . $guard . '/login');
        }

        if (
            $this->isValidUser($user) &&
            $this->isOtpValid($google2fa, $user, $request->code)
        ) {
            $request->session()->put($guard . '_two_factor_authenticated', true);

            return redirect()->intended(route($guard . '.dashboard'))->with('success', 'Logged in successfully.');
        }

        return back()->withErrors([
            'code' => 'Invalid OTP. Please try again.',
        ]);
    }

    /**
     * This function is used to confirm 2FA at the time of setting up 2FA from profile section.
     */
    public function confirm2FA(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => 'required|numeric',
        ]);
        $google2fa = new Google2FA();

        $user = $this->getAuthUser($request);
        $guard = $this->getActiveGuard($request);

        if (! ($user instanceof Admin || $user instanceof StoreManager || $user instanceof WarehouseManager || $user instanceof SuperAdmin)) {
            return $this->handle2FAError(new Exception('User type not allowed.'), 'User type not allowed.');
        }

        $secret = Cache::get('2fa_secret_' . $user->id);

        if (! $secret || ! $google2fa->verifyKey($secret, $request->otp)) {
            return $this->handle2FAError(
                new Exception('Invalid OTP. Please try again.'),
                'Invalid OTP. Please try again.'
            );
        }

        try {
            $encryptedSecret = encrypt($secret);
            $recoveryCode = encrypt(json_encode($request->get('recovery_code')));
            $userId = $user->id;
            switch ($user) {
                case $user instanceof SuperAdmin:
                    $superAdminQueries = resolve(SuperAdminQueries::class);
                    $superAdmin = $superAdminQueries->getById($userId);

                    $superAdminData = new SuperAdminData(
                        $superAdmin->username,
                        $superAdmin->name,
                        null,
                        $superAdmin->email,
                        $superAdmin->two_factor_secret,
                        null,
                    );
                    $superAdminData->two_factor_secret = $encryptedSecret;
                    $superAdminData->two_factor_recovery_codes = $recoveryCode;
                    $superAdminQueries->update($superAdminData, $superAdmin);
                    break;

                case $user instanceof Admin:
                    $adminQueries = resolve(AdminQueries::class);
                    $roleQueries = resolve(RoleQueries::class);
                    $roles = $roleQueries->getRoles('admin');
                    $admin = $adminQueries->getAdminData($userId);
                    $adminData = new AdminData(
                        $admin->username,
                        $admin->employee_id,
                        null,
                        $roles->pluck('id')->toArray(),
                        $admin->two_factor_secret,
                        null,
                    );
                    $adminData->two_factor_secret = $encryptedSecret;
                    $adminData->two_factor_recovery_codes = $recoveryCode;
                    $adminQueries->update($adminData, $userId);
                    break;

                case $user instanceof StoreManager:
                    $storeManagerQueries = resolve(StoreManagerQueries::class);
                    $storeManagerArray = [
                        'two_factor_secret' => $encryptedSecret,
                        'two_factor_recovery_codes' => $recoveryCode,
                    ];
                    $storeManagerQueries->updateStoreManagerProfile((int) $userId, $storeManagerArray);
                    break;

                case $user instanceof WarehouseManager:
                    $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
                    $warehouseManagerArray = [
                        'two_factor_secret' => $encryptedSecret,
                        'two_factor_recovery_codes' => $recoveryCode,
                    ];
                    $warehouseManagerQueries->updateWarehouseManagerProfile($userId, $warehouseManagerArray);
                    break;
            }

            Cache::forget('2fa_secret_' . $user->id);
            $request->session()->put($guard . '_two_factor_authenticated', true);

            return response()->json([
                'success' => '2FA setup successfully.',
            ]);
        } catch (Throwable $throwable) {
            return $this->handle2FAError($throwable, 'Something went wrong! Please try again later.');
        }
    }

    /**
     * This function is used to disable 2FA.
     */
    public function disable2FA(Request $request): void
    {
        $user = $this->getAuthUser($request);

        switch ($user) {
            case $user instanceof SuperAdmin:
                $superAdminId = $request->superAdminId;

                $superAdminQueries = resolve(SuperAdminQueries::class);
                $superAdmin = $superAdminQueries->getById($superAdminId);
                $superAdminArray = $superAdmin->toArray();
                $superAdminData = resolve(SuperAdminData::class, $superAdminArray);

                $superAdminData->two_factor_secret = null;
                $superAdminData->two_factor_recovery_codes = null;

                $superAdminQueries->update($superAdminData, $superAdmin);
                break;

            case $user instanceof Admin:
                $adminId = $request->adminId;

                $roleQueries = resolve(RoleQueries::class);
                $roles = $roleQueries->getRoles('admin');

                $superAdminQueries = resolve(AdminQueries::class);
                $admin = $superAdminQueries->getAdminData($adminId);
                $adminArray = $admin->toArray();
                $adminArray['role_ids'] = array_values($roles->pluck('id')->toArray());

                $adminData = new AdminData(
                    username: $adminArray['username'],
                    employee_id: $adminArray['employee_id'],
                    password: $adminArray['password'] ?? null,
                    role_ids: $adminArray['role_ids'],
                    two_factor_secret: $adminArray['two_factor_secret'] ?? null,
                    two_factor_recovery_codes: $adminArray['two_factor_recovery_codes'] ?? null,
                );

                $adminData->two_factor_secret = null;
                $adminData->two_factor_recovery_codes = null;

                $superAdminQueries->update($adminData, $adminId);
                break;

            case $user instanceof StoreManager:
                $storeManagerQueries = resolve(StoreManagerQueries::class);
                $storeManager = $storeManagerQueries->getStoreManagerData((int) $user->id);
                $storeManagerArray = $storeManager->toArray();

                $storeManagerArray['two_factor_secret'] = null;
                $storeManagerArray['two_factor_recovery_codes'] = null;
                unset($storeManagerArray['id']);

                $storeManagerQueries->updateStoreManagerProfile((int) $user->id, $storeManagerArray);
                break;

            case $user instanceof WarehouseManager:
                $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
                $warehouseManager = $warehouseManagerQueries->getWarehouseManagerData((int) $user->id);
                $warehouseManagerArray = $warehouseManager->toArray();
                $warehouseManagerArray['two_factor_secret'] = null;
                $warehouseManagerArray['two_factor_recovery_codes'] = null;
                unset($warehouseManagerArray['id']);

                $warehouseManagerQueries->updateWarehouseManagerProfile((int) $user->id, $warehouseManagerArray);

                break;

            default:
                $this->handle2FAError(new Exception('User type not allowed'), 'User type not allowed.');
                break;
        }
    }

    /**
     * Check if the user is a valid type for 2FA.
     */
    private function isValidUser(User $user): bool
    {
        return $user instanceof Admin || $user instanceof StoreManager || $user instanceof WarehouseManager || $user instanceof SuperAdmin;
    }

    /**
     * Validate the OTP code.
     */
    private function isOtpValid(
        Google2FA $google2fa,
        Admin|StoreManager|WarehouseManager|SuperAdmin $user,
        string $code
    ): bool {
        $decryptedRecoveryCodes = [];
        if (! empty($user->two_factor_recovery_codes)) {
            $decryptedRecoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes ?? ''), true);
        }

        if (null !== $user->two_factor_secret && $google2fa->verifyKey(decrypt($user->two_factor_secret), $code)) {
            return true;
        }

        if (! empty($decryptedRecoveryCodes) && in_array($code, $decryptedRecoveryCodes)) {
            $decryptedRecoveryCodes = array_values(array_diff($decryptedRecoveryCodes, [$code]));
            $twoFactorRecoveryCodes = encrypt(json_encode($decryptedRecoveryCodes));

            switch ($user) {
                case $user instanceof SuperAdmin:
                    $superAdminQueries = resolve(SuperAdminQueries::class);
                    $superAdmin = $superAdminQueries->getById($user->id);

                    $superAdminData = new SuperAdminData(
                        $superAdmin->username,
                        $superAdmin->name,
                        null,
                        $superAdmin->email,
                        $superAdmin->two_factor_secret,
                        null,
                    );
                    $superAdminData->two_factor_recovery_codes = $twoFactorRecoveryCodes;
                    $superAdminQueries->update($superAdminData, $superAdmin);

                    return true;

                case $user instanceof Admin:
                    $adminQueries = resolve(AdminQueries::class);
                    $roleQueries = resolve(RoleQueries::class);
                    $roles = $roleQueries->getRoles('admin');
                    $admin = $adminQueries->getAdminData($user->id);
                    $adminData = new AdminData(
                        $admin->username,
                        $admin->employee_id,
                        null,
                        $roles->pluck('id')->toArray(),
                        $admin->two_factor_secret,
                        null,
                    );
                    $adminData->two_factor_secret = $twoFactorRecoveryCodes;
                    $adminQueries->update($adminData, $user->id);

                    return true;

                case $user instanceof StoreManager:
                    $storeManagerQueries = resolve(StoreManagerQueries::class);
                    $storeManagerArray = [
                        'two_factor_recovery_codes' => $twoFactorRecoveryCodes,
                    ];
                    $storeManagerQueries->updateStoreManagerProfile((int) $user->id, $storeManagerArray);

                    return true;

                case $user instanceof WarehouseManager:
                    $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
                    $warehouseManagerArray = [
                        'two_factor_recovery_codes' => $twoFactorRecoveryCodes,
                    ];
                    $warehouseManagerQueries->updateWarehouseManagerProfile($user->id, $warehouseManagerArray);

                    return true;

                default:
                    return false;
            }
        }

        return false;
    }

    /**
     * This function is used to get the active guard based on session and route.
     */
    public function getActiveGuard(Request $request): ?string
    {
        if (Auth::guard('super_admin')->check() && PanelManagementService::requestForSuperAdmin($request)) {
            return 'super_admin';
        }

        if (Auth::guard('store_manager')->check() && PanelManagementService::requestForStoreManager($request)) {
            return 'store_manager';
        }

        if (Auth::guard('warehouse_manager')->check() && PanelManagementService::requestForWarehouseManager($request)) {
            return 'warehouse_manager';
        }

        if (Auth::guard('admin')->check() && PanelManagementService::requestForAdmin($request)) {
            return 'admin';
        }

        return $this->handle2FAError(new Exception('User not found!'), 'User not found.');
    }

    private function getEmailAndIDFromUserType(User $user): array
    {
        $email = null;
        $id = null;

        if ($user instanceof Admin || $user instanceof StoreManager || $user instanceof WarehouseManager) {
            if (method_exists($user, 'employee')) {
                $user->load([
                    'employee' => fn ($query) => $query->select(['id', 'email']),
                ]);
            }

            $email = $user->employee->email ?? null;
            $id = $user->id ?? null;
        }

        if ($user instanceof SuperAdmin) {
            /** @var SuperAdmin $user */
            $email = $user->email;
            $id = $user->id;
        }

        return [$email, $id];
    }

    public function getAuthUser(Request $request): SuperAdmin|Admin|StoreManager|WarehouseManager|null
    {
        if (Auth::guard('super_admin')->check() && PanelManagementService::requestForSuperAdmin($request)) {
            return Auth::guard('super_admin')->user();
        }

        if (Auth::guard('store_manager')->check() && PanelManagementService::requestForStoreManager($request)) {
            return Auth::guard('store_manager')->user();
        }

        if (Auth::guard('warehouse_manager')->check() && PanelManagementService::requestForWarehouseManager(
            $request
        )) {
            return Auth::guard('warehouse_manager')->user();
        }

        if (Auth::guard('admin')->check() && PanelManagementService::requestForAdmin($request)) {
            return Auth::guard('admin')->user();
        }

        return null;
    }

    private function handle2FAError(Throwable $throwable, ?string $message): JsonResponse
    {
        Log::error('2FA Errors', [
            'error' => $throwable->getMessage(),
        ]);

        return response()->json([
            'error' => $message,
        ], 417);
    }
}
