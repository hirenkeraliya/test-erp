<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Common\Enums\Statuses;
use App\Domains\Membership\MembershipQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\VoucherConfiguration\DataObjects\VoucherConfigurationData;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\Types;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\Exports\VoucherConfigurationExport;
use App\Domains\VoucherConfiguration\Resources\AdminVoucherConfigurationListResource;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class VoucherConfigurationController extends Controller
{
    public function __construct(
        protected VoucherConfigurationQueries $voucherConfigurationQueries
    ) {
    }

    public function index(): Response
    {
        $expiredBirthdayVoucher = $this->voucherConfigurationQueries->getExpiredBirthdayVoucher(
            session('admin_company_id')
        );

        return Inertia::render('voucher_configurations/Index', [
            'expiredBirthdayVoucher' => $expiredBirthdayVoucher,
            'statuses' => Statuses::getList(),
            'restrictedByTypes' => RestrictedByTypes::getList(),
            'voucherTypes' => VoucherTypes::getList(),
            'discountTypes' => DiscountTypes::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('vouchers_configuration'),
            'types' => Types::getList(),
            'allTypes' => Types::getFormattedArrayForStaticUse(),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchVoucherConfigurations(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'status' => $request->get('status'),
            'restricted_by_type_id' => $request->get('restricted_by_type_id'),
            'voucher_type_id' => $request->get('voucher_type_id'),
            'discount_type_id' => $request->get('discount_type_id'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'type' => $request->get('type'),
        ];

        $lengthAwarePaginator = $this->voucherConfigurationQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminVoucherConfigurationListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('voucher_configurations/Manage', $this->getCommonRecords());
    }

    public function store(VoucherConfigurationData $voucherConfigurationData, Request $request): RedirectResponse
    {
        if (
            $this->voucherConfigurationQueries->getBirthdayVoucherId(session('admin_company_id')) !== null
            && $voucherConfigurationData->voucher_type === VoucherTypes::BIRTHDAY_VOUCHER->value
        ) {
            throw new RedirectBackWithErrorException('The birthday voucher has already been added.');
        }

        if (
            $this->voucherConfigurationQueries->getWelcomeMemberVoucherId(session('admin_company_id')) !== null
            && $voucherConfigurationData->voucher_type === VoucherTypes::WELCOME_MEMBER->value
        ) {
            throw new RedirectBackWithErrorException('The welcome member voucher has already been added.');
        }

        DB::beginTransaction();

        try {
            /** @var User $user */
            $user = $request->user();

            $this->voucherConfigurationQueries->addNew($voucherConfigurationData, session('admin_company_id'), $user);

            DB::commit();

            return to_route('admin.vouchers_configuration.index')
                ->with('success', 'Voucher Configuration added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Voucher', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            throw new RedirectBackWithErrorException($throwable->getMessage());
        }
    }

    public function edit(int $voucherConfigurationId): Response
    {
        $voucherConfiguration = $this->voucherConfigurationQueries->getById(
            $voucherConfigurationId,
            session('admin_company_id')
        );

        $voucherConfiguration['image_url'] = $voucherConfiguration->getDiskBasedFirstMediaUrl('image');
        $voucherConfiguration['thumbnail_url'] = $voucherConfiguration->getDiskBasedFirstMediaUrl('thumbnail');

        return Inertia::render('voucher_configurations/Manage', [
            'voucherConfiguration' => $voucherConfiguration,
            ...$this->getCommonRecords(),
        ]);
    }

    public function update(
        VoucherConfigurationData $voucherConfigurationData,
        int $voucherConfigurationId
    ): RedirectResponse {
        $voucherConfigurationIdExist = $this->voucherConfigurationQueries->getBirthdayVoucherId(
            session('admin_company_id')
        );

        if ($voucherConfigurationIdExist && ($voucherConfigurationIdExist !== $voucherConfigurationId
            && $voucherConfigurationData->voucher_type === VoucherTypes::BIRTHDAY_VOUCHER->value)) {
            throw new RedirectBackWithErrorException('The birthday voucher has already been added.');
        }

        $voucherConfigurationWelcomeMemberIdExist = $this->voucherConfigurationQueries->getWelcomeMemberVoucherId(
            session('admin_company_id')
        );

        if ($voucherConfigurationWelcomeMemberIdExist && ($voucherConfigurationWelcomeMemberIdExist !== $voucherConfigurationId
            && $voucherConfigurationData->voucher_type === VoucherTypes::WELCOME_MEMBER->value)) {
            throw new RedirectBackWithErrorException('The welcome member voucher has already been added.');
        }

        $voucherConfiguration = $this->voucherConfigurationQueries->getById(
            $voucherConfigurationId,
            session('admin_company_id')
        );
        if ($voucherConfigurationData->voucher_type !== $voucherConfiguration->voucher_type) {
            throw new RedirectBackWithErrorException('The voucher type cannot be changed during the edit.');
        }

        DB::beginTransaction();

        try {
            $this->voucherConfigurationQueries->update(
                $voucherConfigurationData,
                $voucherConfigurationId,
                session('admin_company_id')
            );

            DB::commit();

            return to_route('admin.vouchers_configuration.index')
                ->with('success', 'Voucher Configuration updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Voucher', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            throw new RedirectBackWithErrorException($throwable->getMessage());
        }
    }

    public function setStatus(int $voucherConfigurationId, bool $status): RedirectResponse
    {
        $this->voucherConfigurationQueries->setStatus($voucherConfigurationId, session('admin_company_id'), $status);

        return to_route('admin.vouchers_configuration.index')->with('success', 'Status changed successfully.');
    }

    public function removeSelectedProducts(Request $request): void
    {
        $validatedData = $request->validate([
            'id' => ['required', 'exists:voucher_configurations,id'],
        ]);

        $this->voucherConfigurationQueries->removeSelectedProducts($validatedData);
    }

    public function exportVoucherConfigurations(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'status' => $request->get('status'),
            'restricted_by_type_id' => $request->get('restricted_by_type_id'),
            'voucher_type_id' => $request->get('voucher_type_id'),
            'discount_type_id' => $request->get('discount_type_id'),
            'type' => $request->get('type'),
        ];

        $voucherConfigurations = $this->voucherConfigurationQueries->getVouchersConfigurationExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new VoucherConfigurationExport($voucherConfigurations), $filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function getCommonRecords(): array
    {
        $categoryQueries = resolve(CategoryQueries::class);
        $categories = $categoryQueries->getMainCategoriesWithBasicColumns(session('admin_company_id'));
        $membershipQueries = resolve(MembershipQueries::class);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getAllByCompanyId(session('admin_company_id'));

        return [
            'categories' => $categories,
            'saleChannels' => $saleChannels,
            'voucherTypes' => VoucherTypes::getList(),
            'discountTypes' => DiscountTypes::getList(),
            'restrictedByTypes' => RestrictedByTypes::getList(),
            'excludeByTypes' => ExcludeByTypes::getList(),
            'birthdayVoucherId' => $this->voucherConfigurationQueries->getBirthdayVoucherId(
                session('admin_company_id')
            ),
            'welcomeMemberVoucherId' => $this->voucherConfigurationQueries->getWelcomeMemberVoucherId(
                session('admin_company_id')
            ),
            'memberships' => $membershipQueries->getWithBasicColumns(session('admin_company_id')),
            'staticDetails' => [
                'birthday_voucher' => VoucherTypes::BIRTHDAY_VOUCHER,
                'tier_voucher' => VoucherTypes::TIER_VOUCHER,
                'multiple_voucher' => VoucherTypes::MULTIPLE_VOUCHER,
                'welcome_member' => VoucherTypes::WELCOME_MEMBER,
                'flat_discount' => DiscountTypes::FLAT,
                'percentage_discount' => DiscountTypes::PERCENTAGE,
                'restricted_by_all' => RestrictedByTypes::ALL,
                'restricted_by_member' => RestrictedByTypes::MEMBER_ONLY,
                'restricted_by_non_member' => RestrictedByTypes::NON_MEMBER_ONLY,
                'exclude_by_products' => ExcludeByTypes::PRODUCTS,
                'exclude_by_categories' => ExcludeByTypes::CATEGORIES,
                'exclude_by_none' => ExcludeByTypes::NONE,
                'loyalty_point' => VoucherTypes::LOYALTY_POINT,
            ],
        ];
    }
}
