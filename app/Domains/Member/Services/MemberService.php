<?php

declare(strict_types=1);

namespace App\Domains\Member\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Services\ExportService;
use App\Domains\Company\CompanyQueries;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\ExportRecord\Jobs\ExportToExcelJob;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\DataObjects\OrderMemberData;
use App\Domains\Member\DataObjects\PosMemberData;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\Exports\MemberExport;
use App\Domains\Member\MemberQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Jobs\GenerateWelcomeMemberVouchersJob;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Attribute;
use App\Models\Cashier;
use App\Models\Color;
use App\Models\Company;
use App\Models\Employee;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Size;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;

class MemberService
{
    public function preparedMemberRecords(Collection $memberDetails): Collection
    {
        return $memberDetails->transform(fn ($member): array => [
            'type' => $member->type_id ? Types::getFormattedCaseName($member->type_id) : null,
            'title' => $member->title_id ? Titles::getFormattedCaseName($member->title_id) : null,
            'race' => $member->race_id ? Races::getFormattedCaseName($member->race_id) : null,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'gender' => $member->gender_id ? Genders::getFormattedCaseName($member->gender_id) : null,
            'date_of_birth' => $member->date_of_birth ? Carbon::parse($member->date_of_birth)->format('Y-m-d') : null,
            'mobile_number' => $member->mobile_number,
            'email' => $member->email,
            'company_name' => $member->company_name,
            'company_registration_number' => $member->company_registration_number,
            'company_tax_number' => $member->company_tax_number,
            'company_address' => $member->company_address,
            'company_phone' => $member->company_phone,
            'created_location' => $member->createdInLocation ? $member->createdInLocation->name : null,
            'notes' => $member->notes,
            'loyalty_points' => $member->loyalty_points,
            'card_number' => $member->card_number,
        ]);
    }

    public function preparedMemberReportRecords(Collection $memberDetails, Collection $filteredColumns): Collection
    {
        $memberReportRecord = $memberDetails->transform(function ($member): array {
            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d', $member->date);

            return [
                'date' => $date->format('d-m-Y'),
                'location' => $member->createdInLocation?->name,
                'members_count' => $member->members_count,
            ];
        });

        $exportService = resolve(ExportService::class);

        return $exportService->exportDataMapping($memberReportRecord, $filteredColumns);
    }

    public function checkRequiredMemberColumns(array $memberDetails, int $companyId): void
    {
        if (! array_key_exists('first_name', $memberDetails) || ! $memberDetails['first_name']) {
            abort(412, 'First name is required');
        }

        if (! array_key_exists('mobile_number', $memberDetails) || ! $memberDetails['mobile_number']) {
            abort(412, 'mobile number is required');
        }

        $memberQueries = resolve(MemberQueries::class);

        if (
            array_key_exists('email', $memberDetails)
            && $memberDetails['email']
            && $memberQueries->existsByEmail($memberDetails['email'], $companyId)
        ) {
            abort(412, 'The specified email address is already taken by another member.');
        }

        if (! array_key_exists('card_number', $memberDetails)) {
            return;
        }

        if (! $memberDetails['card_number']) {
            return;
        }

        if (! $memberQueries->existsByCardNumber($memberDetails['card_number'], $companyId)) {
            return;
        }

        abort(412, 'Specified card number is already taken by another member.');
    }

    public function checkRequiredMemberColumnsForEcommerce(array $memberDetails, int $companyId): ?Member
    {
        if (! array_key_exists('first_name', $memberDetails) || ! $memberDetails['first_name']) {
            abort(412, 'First name is required');
        }

        if (! array_key_exists('mobile_number', $memberDetails) || ! $memberDetails['mobile_number']) {
            abort(412, 'mobile number is required');
        }

        $memberQueries = resolve(MemberQueries::class);

        if ($memberQueries->memberExistsByMobileNumber($companyId, (string) $memberDetails['mobile_number'])
        ) {
            return $memberQueries->getMemberByMobileNumber($memberDetails['mobile_number'], $companyId);
        }

        if (! array_key_exists('email', $memberDetails)) {
            return null;
        }

        if (! $memberDetails['email']) {
            return null;
        }

        if (! $memberQueries->existsByEmail($memberDetails['email'], $companyId)) {
            return null;
        }

        return $memberQueries->getMemberByEmails($memberDetails['email'], $companyId);
    }

    public function addNewMember(
        Cashier $cashier,
        array $memberDetails,
        int $companyId,
        int $locationId,
        int $channelId
    ): int {
        $memberQueries = resolve(MemberQueries::class);

        $posMemberData = new PosMemberData(
            type_id: array_key_exists('type_id', $memberDetails) ?
                (int) $memberDetails['type_id'] : null,
            title_id: array_key_exists('title_id', $memberDetails) ?
                (int) $memberDetails['title_id'] : null,
            race_id: array_key_exists('race_id', $memberDetails) ?
                (int) $memberDetails['race_id'] : null,
            first_name: $memberDetails['first_name'],
            last_name: $memberDetails['last_name'] ?? null,
            gender_id: array_key_exists('gender_id', $memberDetails) ?
                (int) $memberDetails['gender_id'] : null,
            date_of_birth: $memberDetails['date_of_birth'] ?? null,
            mobile_number: $memberDetails['mobile_number'],
            email: $memberDetails['email'] ?? null,
            address_line_1: $memberDetails['address_line_1'] ?? null,
            address_line_2: $memberDetails['address_line_2'] ?? null,
            city_name: $memberDetails['city'] ?? null,
            area_code: $memberDetails['area_code'] ?? null,
            company_name: $memberDetails['company_name'] ?? null,
            company_registration_number: $memberDetails['company_registration_number'] ?? null,
            company_tax_number: $memberDetails['company_tax_number'] ?? null,
            company_phone: $memberDetails['company_phone'] ?? null,
            created_location_id: $locationId,
            notes: $memberDetails['notes'] ?? null,
            card_number: $this->getCardNumber($memberDetails),
            photo: null,
        );

        $member = $memberQueries->addNew($posMemberData, $companyId, $cashier, $channelId);

        $this->generateWelcomeMemberVoucher($member, $locationId);

        return $member->id;
    }

    public function getCardNumber(array $memberDetails): string
    {
        $memberQueries = resolve(MemberQueries::class);

        if (! array_key_exists('card_number', $memberDetails)) {
            return $memberQueries->generateUniqueCardNumber();
        }

        if (! $memberDetails['card_number']) {
            return $memberQueries->generateUniqueCardNumber();
        }

        return $memberDetails['card_number'];
    }

    public function addNewMemberFromEcommerceOrder(array $memberDetails, int $companyId, int $locationId): int
    {
        $memberQueries = resolve(MemberQueries::class);

        $orderMemberData = new OrderMemberData(
            type_id: array_key_exists('type_id', $memberDetails) ?
                (int) $memberDetails['type_id'] : Types::REGULAR->value,
            first_name: $memberDetails['first_name'],
            mobile_number: $memberDetails['mobile_number'],
            email: $memberDetails['email'] ?? null,
            created_location_id: $locationId,
            card_number: $memberQueries->generateUniqueCardNumber(),
            company_name: $memberDetails['company_name'] ?? null,
            company_address: $memberDetails['company_address'] ?? null,
            pic_name: $memberDetails['pic_name'] ?? null,
            pic_contact: $memberDetails['pic_contact'] ?? null,
        );

        $member = $memberQueries->addNewFromEcommerceOrder($orderMemberData, $companyId);

        return $member->getKey();
    }

    public function getMemberIdFromDetails(array $memberDetails, int $companyId): ?int
    {
        $memberQueries = resolve(MemberQueries::class);

        if (array_key_exists('card_number', $memberDetails) && $memberDetails['card_number']) {
            $cardNumber = $memberDetails['card_number'];
            $member = $memberQueries->getMemberByCardNumber($cardNumber, $companyId);

            if ($member) {
                return $member->id;
            }
        }

        if (array_key_exists('mobile_number', $memberDetails) && $memberDetails['mobile_number']) {
            $mobileNumber = $memberDetails['mobile_number'];
            $member = $memberQueries->getMemberByMobileNumber($mobileNumber, $companyId);

            if ($member) {
                return $member->id;
            }
        }

        if (array_key_exists('email', $memberDetails) && $memberDetails['email']) {
            $email = $memberDetails['email'];
            $member = $memberQueries->getMemberByEmails($email, $companyId);

            if ($member) {
                return $member->id;
            }
        }

        return null;
    }

    private function generateWelcomeMemberVoucher(Member $member, int $locationId): void
    {
        $today = Carbon::now();

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherConfiguration = $voucherConfigurationQueries->getWelcomeMemberVoucherConfigurationByCompanyId(
            $member->company_id,
            $today
        );

        if (! $voucherConfiguration) {
            return;
        }

        if ($member->welcome_member_voucher_generated_at) {
            return;
        }

        $expiryDate = null;
        if ($voucherConfiguration->validity_days > 0) {
            $expiryDate = now()->addDays($voucherConfiguration->validity_days);
        }

        $voucherQueries = resolve(VoucherQueries::class);
        $voucher = $voucherQueries->addNew(
            $voucherConfiguration,
            (float) $voucherConfiguration->get_value,
            $voucherConfiguration->discount_type,
            $expiryDate,
            $member->id,
        );

        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $voucherTransactionQueries->addNew(
            $voucher->id,
            VoucherTransactionActionTypes::CREATED->value,
            now()->format('Y-m-d H:i:s'),
            null,
            $locationId
        );

        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->updateWelcomeMemberVoucherDetails($member, $voucher->id);
    }

    public function addNewMemberMembershipLoyaltyPointsAndWelcomeVouchers(Member $member): void
    {
        if (! $member->created_location_id) {
            return;
        }

        $this->addNewMemberFreeMembership($member);
        $this->addNewMemberFreeLoyaltyPoints($member);
        $this->addNewMemberWelcomeVouchers($member);
    }

    public function addNewMemberMembershipAndLoyaltyPoints(Member $member): void
    {
        if (! $member->created_location_id) {
            return;
        }

        $this->addNewMemberFreeMembership($member);
        $this->addNewMemberFreeLoyaltyPoints($member);
    }

    public function addNewMemberWelcomeVouchers(Member $member): void
    {
        if ($member->welcome_member_voucher_generated_at) {
            return;
        }

        GenerateWelcomeMemberVouchersJob::dispatch($member->id, $member->company_id)->onQueue(
            config('horizon.default_queue_name')
        );
    }

    public function addNewMemberFreeMembership(Member $member): void
    {
        if ($member->membership_id) {
            return;
        }

        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->assignMembershipToMember($member->getKey(), $member->company_id);
    }

    public function addNewMemberFreeLoyaltyPoints(Member $member): void
    {
        if (! $member->created_location_id) {
            return;
        }

        if ($member->loyalty_points > 0) {
            return;
        }

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNewMemberFreeLoyaltyPointsById($member->company_id);
        if ($company->new_member_free_loyalty_points <= 0) {
            return;
        }

        $locationQueries = resolve(LocationQueries::class);
        $locationQueries->getLoyaltyPointExpirationDaysById($member->created_location_id, $member->company_id);

        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->addLoyaltyPoints($member, $company->new_member_free_loyalty_points);

        $this->addNewLoyaltyPointsAndLoyaltyPointsUpdate(
            $member,
            $company->new_member_free_loyalty_points,
            $company->loyalty_point_expiration_days
        );
    }

    public function addNewLoyaltyPointsAndLoyaltyPointsUpdate(
        Member $member,
        ?int $newMemberFreeLoyaltyPoints,
        ?int $loyaltyPointExpirationDays
    ): void {
        $loyaltyPointData = [
            'member_id' => $member->id,
            'sale_id' => null,
            'loyalty_campaign_id' => null,
            'expiry_date' => ((0 === $loyaltyPointExpirationDays) || (null === $loyaltyPointExpirationDays)) ? null : now()->addDays(
                $loyaltyPointExpirationDays
            ),
            'points' => $newMemberFreeLoyaltyPoints ?? 0,
            'available_points' => $newMemberFreeLoyaltyPoints ?? 0,
            'minimum_spend_amount' => 0,
        ];

        $loyaltyPointUpdateData = [
            'member_id' => $member->id,
            'affected_by_id' => $member->id,
            'affected_by_type' => ModelMapping::getCaseName($member::class),
            'type_id' => LoyaltyPointUpdateTypes::SIGNUP_BONUS->value,
            'points' => $newMemberFreeLoyaltyPoints ?? 0,
            'closing_loyalty_points_balance' => $newMemberFreeLoyaltyPoints ?? 0,
            'happened_at' => now()->format('Y-m-d H:i:s'),
        ];

        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
        $loyaltyPoint = $loyaltyPointQueries->addNew($loyaltyPointData);

        $loyaltyPointUpdateData['loyalty_point_id'] = $loyaltyPoint->id;

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointUpdateQueries->addNew($loyaltyPointUpdateData);
    }

    public function addNewEmployeeMember(Employee $employee): void
    {
        $memberData = [
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'mobile_number' => $employee->mobile_number,
            'address_line_1' => $employee->address_line_1,
            'address_line_2' => $employee->address_line_2,
            'city' => $employee->city,
            'area_code' => $employee->area_code ?? '',
            'membership_id' => $employee->membership_id,
            'loyalty_points' => $employee->loyalty_points,
            'card_number' => $employee->card_number,
            'created_by_id' => $employee->created_by_id,
            'created_by_type' => $employee->created_by_type,
            'status' => Status::ACTIVE,
        ];

        $memberQueries = resolve(MemberQueries::class);
        $isMemberExistsByEmployee = $memberQueries->isMemberExistsByEmployee($employee);

        if ($isMemberExistsByEmployee) {
            $memberQueries->addEmployeeId($employee);

            return;
        }

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getWithLocationAssignmentTypeById((int) $memberData['company_id']);

        if (! $company instanceof Company) {
            return;
        }

        $memberQueries->addNewEmployeeMember($memberData, $company);
    }

    public function getMemberPreferencesRecords(int $memberId, int $companyId, ?int $locationId = null): array
    {
        $saleItemQueries = resolve(SaleItemQueries::class);

        $getPreferredItems = $saleItemQueries->getPreferredItems($memberId, $companyId, $locationId);
        $products = [];
        $colors = [];
        $sizes = [];
        $categories = [];
        $dates = [];
        $days = [];

        foreach ($getPreferredItems as $item) {
            /** @var Product $product */
            $product = $item->product;

            /** @var Sale $sale */
            $sale = $item->sale;

            /** @var ?Color $color */
            $color = $product->color;

            /** @var ?Size $size */
            $size = $product->size;

            /** @var Collection $productCategories */
            $productCategories = $product->categories;

            $saleDate = Carbon::parse($sale->happened_at)->format('d');

            if (array_key_exists($saleDate, $dates)) {
                $dates[$saleDate]['quantity'] += (float) $item->quantity;
            } else {
                $dates[$saleDate] = [
                    'date' => $saleDate,
                    'quantity' => (float) $item->quantity,
                ];
            }

            $saleDay = Carbon::parse($sale->happened_at)->format('l');

            if (array_key_exists($saleDay, $days)) {
                $days[$saleDay]['quantity'] += (float) $item->quantity;
            } else {
                $days[$saleDay] = [
                    'day' => $saleDay,
                    'quantity' => (float) $item->quantity,
                ];
            }

            foreach ($productCategories as $category) {
                if (array_key_exists($category->id, $categories)) {
                    $categories[$category->id]['quantity'] += (float) $item->quantity;
                } else {
                    $categories[$category->id] = [
                        'id' => $category->id,
                        'name' => $category->name,
                        'quantity' => (float) $item->quantity,
                    ];
                }
            }

            if (null !== $product->color_id) {
                if (array_key_exists($product->color_id, $colors)) {
                    $colors[$product->color_id]['quantity'] += (float) $item->quantity;
                } else {
                    $colors[$product->color_id] = [
                        'id' => $product->color_id,
                        'name' => $color?->name,
                        'quantity' => (float) $item->quantity,
                    ];
                }
            }

            if (null !== $product->size_id) {
                if (array_key_exists($product->size_id, $sizes)) {
                    $sizes[$product->size_id]['quantity'] += (float) $item->quantity;
                } else {
                    $sizes[$product->size_id] = [
                        'id' => $size?->id,
                        'name' => $size?->name,
                        'quantity' => (float) $item->quantity,
                    ];
                }
            }

            if (array_key_exists($item->product_id, $products)) {
                $products[$item->product_id]['quantity'] += (float) $item->quantity;
            } else {
                $products[$item->product_id] = [
                    'id' => $product->id,
                    'sku' => $product->upc,
                    'name' => $product->name,
                    'quantity' => (float) $item->quantity,
                ];
            }
        }

        $preferencesProduct = collect($products)->sortByDesc('quantity')->take(5)->toArray();
        $preferencesProduct = array_values($preferencesProduct);

        $preferencesColor = collect($colors)->sortByDesc('quantity')->first();
        $preferencesSize = collect($sizes)->sortByDesc('quantity')->first();
        $preferencesCategory = collect($categories)->sortByDesc('quantity')->first();
        $preferredDate = collect($dates)->sortByDesc('quantity')->first();
        $preferredDay = collect($days)->sortByDesc('quantity')->first();

        return [
            'preferences_products' => $preferencesProduct,
            'preferences_color' => $preferencesColor,
            'preferences_size' => $preferencesSize,
            'preferences_category' => $preferencesCategory,
            'preferred_date' => $preferredDate,
            'preferred_day' => $preferredDay,
        ];
    }

    public function exportMemberWithJob(User $user, array $filterData, int $companyId): array
    {
        $memberQueries = resolve(MemberQueries::class);
        $totalRecords = $memberQueries->getMembersExportCount($filterData, $companyId);

        if ($totalRecords <= (int) config('app.excel.export.job_limit')) {
            return [
                'exceeds_limit' => false,
            ];
        }

        $exportRecordQueries = resolve(ExportRecordQueries::class);

        $headerColumns = resolve(MemberExport::class)->headings();

        $exportRecord = $exportRecordQueries->addNew(
            $user,
            $filterData,
            $companyId,
            ExportRecordTypes::MEMBERS->value,
            $headerColumns,
            $totalRecords
        );

        ExportToExcelJob::dispatch($exportRecord)->onQueue('medium');

        return [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ];
    }

    public function getMemberPreferencesRecordsForApp(int $memberId, int $companyId, ?int $locationId = null): array
    {
        $saleItemQueries = resolve(SaleItemQueries::class);

        $getPreferredItems = $saleItemQueries->getPreferredItems($memberId, $companyId, $locationId);
        $colors = [];
        $sizes = [];
        $categories = [];
        $masterCategories = [];
        $attributes = [];
        $products = [];

        foreach ($getPreferredItems as $item) {
            /** @var Product $product */
            $product = $item->product;

            /** @var ?MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;

            if ($product->productVariantValues->isNotEmpty()) {
                $productVariantValues = $product->productVariantValues;
                foreach ($productVariantValues as $productVariantValue) {
                    if (isset($attributes[$productVariantValue->attribute_id][$productVariantValue->value])) {
                        $attributes[$productVariantValue->attribute_id][$productVariantValue->value]['quantity'] += (float) $item->quantity;
                    } else {
                        /** @var Attribute $attribute */
                        $attribute = $productVariantValue->attribute;
                        $attributes[$productVariantValue->attribute_id][$productVariantValue->value] = [
                            $attribute->name => $productVariantValue->value,
                            'quantity' => (float) $item->quantity,
                        ];
                    }
                }
            }

            if ($masterProduct instanceof MasterProduct) {
                foreach ($masterProduct->categories as $category) {
                    if (array_key_exists($category->id, $masterCategories)) {
                        $masterCategories[$category->id]['quantity'] += (float) $item->quantity;
                    } else {
                        $masterCategories[$category->id] = [
                            'name' => $category->name,
                            'quantity' => (float) $item->quantity,
                        ];
                    }
                }
            }

            /** @var ?Color $color */
            $color = $product->color;

            /** @var ?Size $size */
            $size = $product->size;

            /** @var Collection $productCategories */
            $productCategories = $product->categories;

            foreach ($productCategories as $category) {
                if (array_key_exists($category->id, $categories)) {
                    $categories[$category->id]['quantity'] += (float) $item->quantity;
                } else {
                    $categories[$category->id] = [
                        'name' => $category->name,
                        'quantity' => (float) $item->quantity,
                    ];
                }
            }

            if (null !== $product->color_id) {
                if (array_key_exists($product->color_id, $colors)) {
                    $colors[$product->color_id]['quantity'] += (float) $item->quantity;
                } else {
                    $colors[$product->color_id] = [
                        'name' => $color?->name,
                        'quantity' => (float) $item->quantity,
                    ];
                }
            }

            if (null !== $product->size_id) {
                if (array_key_exists($product->size_id, $sizes)) {
                    $sizes[$product->size_id]['quantity'] += (float) $item->quantity;
                } else {
                    $sizes[$product->size_id] = [
                        'name' => $size?->name,
                        'quantity' => (float) $item->quantity,
                    ];
                }
            }

            if (array_key_exists($item->product_id, $products)) {
                $products[$item->product_id]['quantity'] += (float) $item->quantity;
            } else {
                $products[$item->product_id] = [
                    'id' => $product->id,
                    'sku' => $product->upc,
                    'name' => $product->name,
                    'quantity' => (float) $item->quantity,
                ];
            }
        }

        $preferencesColor = collect($colors)->sortByDesc('quantity')->first();
        $preferencesSize = collect($sizes)->sortByDesc('quantity')->first();
        $preferencesCategory = collect($categories)->sortByDesc('quantity')->first();
        $preferencesProduct = collect($products)->sortByDesc('quantity')->first();
        $preferencesMasterCategory = collect($masterCategories)->sortByDesc('quantity')->first();

        $colorName = $preferencesColor['name'] ?? null;
        $sizeName = $preferencesSize['name'] ?? null;
        $categoryName = $preferencesCategory['name'] ?? null;
        $productName = $preferencesProduct['name'] ?? null;
        $masterCategoryName = $preferencesMasterCategory['name'] ?? null;

        $masterProductPreferences = [
            'preference_category' => $masterCategoryName,
        ];

        /**
         * @var array<int, array<string, mixed>> $values
         */
        foreach ($attributes as $values) {
            $attribute = collect($values)->sortByDesc('quantity')->first();
            if ($attribute) {
                $key = array_key_first($attribute);
                $masterProductPreferences['preference_' . strtolower($key)] = reset($attribute);
            }
        }

        return [$colorName, $sizeName, $categoryName, $productName, $masterProductPreferences];
    }
}
