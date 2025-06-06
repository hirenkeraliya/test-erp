<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Media\MediaQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\VoucherConfiguration\DataObjects\VoucherConfigurationData;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\Types;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfigurationTier\VoucherConfigurationTierQueries;
use App\Models\SaleChannel;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VoucherConfigurationQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->voucherConfigurationQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(VoucherConfigurationData $voucherConfigurationData, int $companyId, User $user): void
    {
        $voucherConfigurationDetails = $voucherConfigurationData->all();
        $voucherConfigurationDetails['company_id'] = $companyId;
        $voucherConfigurationDetails['created_by_id'] = $user->id;
        $voucherConfigurationDetails['created_by_type'] = ModelMapping::getCaseName($user::class);

        unset($voucherConfigurationDetails['product_ids'], $voucherConfigurationDetails['category_ids'], $voucherConfigurationDetails['tiers'], $voucherConfigurationDetails['membership_ids'], $voucherConfigurationDetails['image'], $voucherConfigurationDetails['thumbnail'], $voucherConfigurationDetails['sale_channel_ids']);

        $voucherConfiguration = VoucherConfiguration::create($voucherConfigurationDetails);

        $this->updateRelationDetails($voucherConfigurationData, $voucherConfiguration);
        $this->uploadPhoto($voucherConfiguration, $voucherConfigurationData);
    }

    public function getById(int $voucherConfigurationId, int $companyId): VoucherConfiguration
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $voucherConfigurationTierQueries = resolve(VoucherConfigurationTierQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $relations = [
            'products:' . $productQueries->getBasicColumnNames(),
            'categories:' . $categoryQueries->getBasicColumnNames(),
            'voucherConfigurationTiers:' . $voucherConfigurationTierQueries->getBasicColumnNames(),
            'memberships:' . $membershipQueries->getBasicColumnNames(),
            'media:' . $mediaQueries->getBasicColumnNames(),
            'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
        ];

        if (config('app.product_variant')) {
            $relations[] = 'products.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames();

            $relations[] = 'products.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames();
        } else {
            $relations[] = 'products.color:' . $colorQueries->getBasicColumnNames();
            $relations[] = 'products.size:' . $sizeQueries->getBasicColumnNames();
        }

        return VoucherConfiguration::query()
            ->select(
                'id',
                'restricted_by_type',
                'voucher_type',
                'exclude_by_type',
                'issue_minimum_spend_amount',
                'use_minimum_spend_amount',
                'validity_days',
                'discount_type',
                'get_value',
                'start_date',
                'end_date',
                'status',
                'dream_price_applicable',
                'item_wise_promotion_applicable',
                'cart_wide_promotion_applicable',
                'redemption_foot_note',
                'handover_foot_note',
                'title',
                'description',
                'terms_and_conditions',
                'is_available_in_ecommerce'
            )
            ->with($relations)
            ->where('company_id', $companyId)
            ->findOrFail($voucherConfigurationId);
    }

    public function getByIds(array $voucherConfigurationIds, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $voucherConfigurationTierQueries = resolve(VoucherConfigurationTierQueries::class);

        return VoucherConfiguration::query()
            ->with([
                'products:' . $productQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'voucherConfigurationTiers:' . $voucherConfigurationTierQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('id', $voucherConfigurationIds)
            ->get();
    }

    public function update(
        VoucherConfigurationData $voucherConfigurationData,
        int $voucherConfigurationId,
        int $companyId,
        ?bool $status = null
    ): void {
        $voucherConfiguration = $this->getById($voucherConfigurationId, $companyId);

        $voucherConfiguration->categories()->detach();
        $voucherConfiguration->voucherConfigurationTiers()->delete();
        $voucherConfiguration->memberships()->detach();

        $voucherConfigurationDetails = $voucherConfigurationData->all();

        unset($voucherConfigurationDetails['product_ids'], $voucherConfigurationDetails['category_ids'], $voucherConfigurationDetails['tiers'], $voucherConfigurationDetails['membership_ids'], $voucherConfigurationDetails['image'], $voucherConfigurationDetails['thumbnail'], $voucherConfigurationDetails['sale_channel_ids']);

        if (null !== $status) {
            $voucherConfigurationDetails['status'] = $status;
        }

        $voucherConfiguration->update($voucherConfigurationDetails);

        $this->updateRelationDetails($voucherConfigurationData, $voucherConfiguration);
        $this->uploadPhoto($voucherConfiguration, $voucherConfigurationData);
        $this->setUpdatedAt($voucherConfiguration);
    }

    public function setUpdatedAt(VoucherConfiguration $voucherConfiguration): void
    {
        $voucherConfiguration->touch();
    }

    public function getBirthdayVoucherId(int $companyId): ?int
    {
        $voucherConfiguration = VoucherConfiguration::query()
            ->where('voucher_type', VoucherTypes::BIRTHDAY_VOUCHER->value)
            ->where('company_id', $companyId)
            ->first();

        return $voucherConfiguration?->id;
    }

    public function getWelcomeMemberVoucherId(int $companyId): ?int
    {
        $voucherConfiguration = VoucherConfiguration::query()
            ->where('voucher_type', VoucherTypes::WELCOME_MEMBER->value)
            ->where('company_id', $companyId)
            ->first();

        return $voucherConfiguration?->id;
    }

    public function getBirthDayVoucherConfigurationByCompanyId(int $companyId): ?VoucherConfiguration
    {
        return VoucherConfiguration::query()
            ->select(
                'id',
                'use_minimum_spend_amount',
                'validity_days',
                'discount_type',
                'get_value',
                'start_date',
                'end_date',
                'dream_price_applicable',
                'item_wise_promotion_applicable',
                'cart_wide_promotion_applicable',
                'handover_foot_note',
                'redemption_foot_note'
            )
            ->where('voucher_type', VoucherTypes::BIRTHDAY_VOUCHER->value)
            ->where('company_id', $companyId)
            ->where('status', true)
            ->first();
    }

    public function getWelcomeMemberVoucherConfigurationByCompanyId(int $companyId, Carbon $date): ?VoucherConfiguration
    {
        return VoucherConfiguration::query()
            ->select(...$this->getColumnNames())
            ->where('voucher_type', VoucherTypes::WELCOME_MEMBER->value)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->where('status', true)
            ->where('company_id', $companyId)
            ->first();
    }

    public function getListForPosWithRelatedData(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $voucherConfigurationTierQueries = resolve(VoucherConfigurationTierQueries::class);

        return VoucherConfiguration::query()
            ->select(...$this->getColumnNames())
            ->with([
                'products:' . $productQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'voucherConfigurationTiers:' . $voucherConfigurationTierQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->whereNull('mystery_gift_id')
            ->whereNotIn('voucher_type', [VoucherTypes::WELCOME_MEMBER->value, VoucherTypes::LOYALTY_POINT->value])
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            }, function ($query): void {
                $query->where('status', true);
            })
            ->get();
    }

    public function getListLoyaltyPointForPosWithRelatedData(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $voucherConfigurationTierQueries = resolve(VoucherConfigurationTierQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return VoucherConfiguration::query()
            ->select(...$this->getColumnNames())
            ->with([
                'products:' . $productQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'voucherConfigurationTiers:' . $voucherConfigurationTierQueries->getBasicColumnNames(),
                'memberships:' . $membershipQueries->getBasicColumnNames(),
                'media:' . $mediaQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->where('voucher_type', VoucherTypes::LOYALTY_POINT->value)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            }, function ($query): void {
                $query->where('status', true);
            })
            ->get();
    }

    public function getBirthdayVoucherConfiguration(Carbon $date): Collection
    {
        return VoucherConfiguration::query()
            ->select(...$this->getColumnNames())
            ->whereHas('company', function ($query): void {
                $query->where('auto_birthday_voucher_generation', true);
            })
            ->where('voucher_type', VoucherTypes::BIRTHDAY_VOUCHER->value)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->where('status', true)
            ->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function getCompanyColumn(): string
    {
        return 'id,company_id';
    }

    public function getBasicColumnNames(): string
    {
        return 'id,exclude_by_type,restricted_by_type,dream_price_applicable,item_wise_promotion_applicable,cart_wide_promotion_applicable';
    }

    public function getSeasonalSalesBasicColumns(): string
    {
        return 'id,title';
    }

    public function getBasicColumnNamesForPosMemberApi(): string
    {
        return 'id,restricted_by_type,voucher_type,exclude_by_type,status,redemption_foot_note,handover_foot_note,discount_type,title';
    }

    public function getBasicColumnNamesForSalesApi(): string
    {
        return 'id,restricted_by_type,voucher_type,exclude_by_type,discount_type,redemption_foot_note,handover_foot_note';
    }

    public function getBasicColumnNamesForEcommerce(): string
    {
        return 'id,company_id,restricted_by_type,voucher_type,discount_type,title';
    }

    public function getFooterColumns(): string
    {
        return 'id,redemption_foot_note,handover_foot_note';
    }

    public function setStatus(int $voucherConfigurationId, int $companyId, bool $status): void
    {
        $voucherConfiguration = VoucherConfiguration::query()
            ->where('company_id', $companyId)
            ->findOrFail($voucherConfigurationId);
        $voucherConfiguration->status = $status;
        $voucherConfiguration->save();
    }

    public function inactiveVoucherConfiguration(VoucherConfiguration $voucherConfiguration): void
    {
        $voucherConfiguration->status = false;
        $voucherConfiguration->save();
    }

    public function getExpiredBirthdayVoucher(int $companyId): ?VoucherConfiguration
    {
        return VoucherConfiguration::select('id')
            ->where('company_id', $companyId)
            ->where('voucher_type', VoucherTypes::BIRTHDAY_VOUCHER->value)
            ->where('end_date', '<', Carbon::today()->format('Y-m-d'))
            ->first();
    }

    public function getByIdForBirthdayVoucher(
        int $voucherConfigurationId,
        int $companyId,
        Carbon $date
    ): VoucherConfiguration {
        return VoucherConfiguration::query()
            ->select(
                'id',
                'use_minimum_spend_amount',
                'validity_days',
                'discount_type',
                'get_value',
                'dream_price_applicable',
                'item_wise_promotion_applicable',
                'cart_wide_promotion_applicable',
                'start_date',
                'end_date',
            )
            ->where('voucher_type', VoucherTypes::BIRTHDAY_VOUCHER->value)
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->where('status', true)
            ->findOrFail($voucherConfigurationId);
    }

    public function removeSelectedProducts(array $voucherConfigurationData): void
    {
        /** @var VoucherConfiguration $voucherConfiguration */
        $voucherConfiguration = VoucherConfiguration::select('id')->findOrFail($voucherConfigurationData['id']);
        $voucherConfiguration->products()->detach();
    }

    public function getVouchersConfigurationExport(array $filterData, int $companyId): Collection
    {
        return $this->voucherConfigurationQuery($filterData, $companyId)->get();
    }

    public function updateProductIdsInVoucherConfigurationProductPivot(
        int $oldProductId,
        int $newProductId
    ): void {
        DB::table('voucher_configuration_product')
            ->where('product_id', $oldProductId)
            ->update([
                'product_id' => $newProductId,
            ]);
    }

    public function getVouchersConfigurationForApplication(array $filteredData, int $companyId): LengthAwarePaginator
    {
        return VoucherConfiguration::query()
            ->select(
                'id',
                'restricted_by_type',
                'voucher_type',
                'discount_type',
                'get_value',
                'start_date',
                'end_date',
                'status'
            )
            ->where('company_id', $companyId)
            ->where('status', true)
            ->when($filteredData['selected_date'], function ($query) use ($filteredData): void {
                $query->where('start_date', '<=', $filteredData['selected_date'])
                    ->where('end_date', '>=', $filteredData['selected_date']);
            })
            ->when(null !== $filteredData['search_text'], function ($query) use ($filteredData): void {
                $query->where(function ($query) use ($filteredData): void {
                    $query->where('get_value', 'like', '%' . $filteredData['search_text'] . '%')
                        ->orWhereIntegerInRaw(
                            'restricted_by_type',
                            RestrictedByTypes::getMatchingCases($filteredData['search_text'])
                        )
                        ->orWhereIntegerInRaw(
                            'voucher_type',
                            VoucherTypes::getMatchingCases($filteredData['search_text'])
                        )
                        ->orWhereIntegerInRaw(
                            'discount_type',
                            DiscountTypes::getMatchingCases($filteredData['search_text'])
                        );
                });
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->paginate($filteredData['per_page']);
    }

    public function getListForEcommerceWithRelatedData(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $voucherConfigurationTierQueries = resolve(VoucherConfigurationTierQueries::class);

        return VoucherConfiguration::query()
            ->select(...$this->getColumnNames())
            ->with([
                'products:' . $productQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'voucherConfigurationTiers:' . $voucherConfigurationTierQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->whereIn('voucher_type', [VoucherTypes::TIER_VOUCHER->value, VoucherTypes::MULTIPLE_VOUCHER->value])
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            }, function ($query): void {
                $query->where('status', true);
            })
            ->get();
    }

    public function getByOnlyId(int $voucherConfigurationId): VoucherConfiguration
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $voucherConfigurationTierQueries = resolve(VoucherConfigurationTierQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);

        return VoucherConfiguration::query()
            ->select(
                'id',
                'voucher_type',
                'company_id',
                'exclude_by_type',
                'restricted_by_type',
                'issue_minimum_spend_amount',
                'use_minimum_spend_amount',
                'validity_days',
                'discount_type',
                'get_value',
                'start_date',
                'end_date',
                'status',
                'dream_price_applicable',
                'item_wise_promotion_applicable',
                'cart_wide_promotion_applicable',
                'redemption_foot_note',
                'handover_foot_note',
                'title',
                'description',
                'terms_and_conditions'
            )
            ->with([
                'products:' . $productQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'voucherConfigurationTiers:' . $voucherConfigurationTierQueries->getBasicColumnNames(),
                'memberships:' . $membershipQueries->getBasicColumnNames(),
            ])
            ->findOrFail($voucherConfigurationId);
    }

    public function getByIdForEcommerce(int $id): VoucherConfiguration
    {
        return VoucherConfiguration::query()
            ->select('id', 'company_id')
            ->findOrFail($id);
    }

    public function validateVoucherConfigurationSaleChannelMatch(
        VoucherConfiguration $voucherConfiguration,
        SaleChannel $saleChannel
    ): bool {
        return $voucherConfiguration->saleChannels()
            ->wherePivot('sale_channel_id', $saleChannel->id)
            ->exists();
    }

    private function uploadPhoto(
        VoucherConfiguration $voucherConfiguration,
        VoucherConfigurationData $voucherConfigurationData
    ): void {
        if ($voucherConfigurationData->image instanceof UploadedFile) {
            $voucherConfiguration->addMedia($voucherConfigurationData->image)->toMediaCollection('image');
        }

        if ($voucherConfigurationData->thumbnail instanceof UploadedFile) {
            $voucherConfiguration->addMedia($voucherConfigurationData->thumbnail)->toMediaCollection('thumbnail');
        }
    }

    private function voucherConfigurationQuery(array $filterData, int $companyId): Builder
    {
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);

        return VoucherConfiguration::query()
            ->select(
                'id',
                'restricted_by_type',
                'voucher_type',
                'discount_type',
                'get_value',
                'start_date',
                'end_date',
                'mystery_gift_id',
                'status'
            )
            ->with(['vouchers.saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames()])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('get_value', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereIntegerInRaw(
                            'restricted_by_type',
                            RestrictedByTypes::getMatchingCases($filterData['search_text'])
                        )
                        ->orWhereIntegerInRaw(
                            'voucher_type',
                            VoucherTypes::getMatchingCases($filterData['search_text'])
                        )
                        ->orWhereIntegerInRaw(
                            'discount_type',
                            DiscountTypes::getMatchingCases($filterData['search_text'])
                        );
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['restricted_by_type_id'], function ($query) use ($filterData): void {
                $query->where('restricted_by_type', (int) $filterData['restricted_by_type_id']);
            })
            ->when($filterData['voucher_type_id'], function ($query) use ($filterData): void {
                $query->where('voucher_type', (int) $filterData['voucher_type_id']);
            })
            ->when($filterData['discount_type_id'], function ($query) use ($filterData): void {
                $query->where('discount_type', (int) $filterData['discount_type_id']);
            })
            ->when(null !== $filterData['status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status']);
            })
             ->when(
                 (int) $filterData['type'] === Types::SYSTEM_GENERATED->value,
                 function ($query): void {
                     $query->whereNotNull('mystery_gift_id');
                 }
             )
            ->when(
                (int) $filterData['type'] === Types::MANUAL->value,
                function ($query): void {
                    $query->whereNull('mystery_gift_id');
                }
            )
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function updateRelationDetails(
        VoucherConfigurationData $voucherConfigurationData,
        VoucherConfiguration $voucherConfiguration
    ): void {
        if ($voucherConfigurationData->exclude_by_type === ExcludeByTypes::CATEGORIES->value && $voucherConfigurationData->category_ids) {
            $voucherConfiguration->categories()->sync($voucherConfigurationData->category_ids);
        }

        if ($voucherConfigurationData->exclude_by_type === ExcludeByTypes::PRODUCTS->value && $voucherConfigurationData->product_ids) {
            $voucherConfiguration->products()->sync($voucherConfigurationData->product_ids);
        }

        if ($voucherConfigurationData->voucher_type === VoucherTypes::LOYALTY_POINT->value && $voucherConfigurationData->membership_ids) {
            $voucherConfiguration->memberships()->sync($voucherConfigurationData->membership_ids);
        }

        if (
            (
                $voucherConfigurationData->voucher_type === VoucherTypes::TIER_VOUCHER->value
                || $voucherConfigurationData->voucher_type === VoucherTypes::LOYALTY_POINT->value
            )
            && $voucherConfigurationData->tiers
        ) {
            $voucherConfigurationTierData = [];

            foreach ($voucherConfigurationData->tiers as $voucherConfigurationTier) {
                $voucherConfigurationTierData[] = [
                    'voucher_configuration_id' => $voucherConfiguration->id,
                    'minimum_spend_amount' => $voucherConfigurationTier['minimum_spend_amount'],
                    'maximum_spend_amount' => $voucherConfigurationTier['maximum_spend_amount'],
                    'get_value' => $voucherConfigurationTier['get_value'],
                ];
            }

            $voucherConfiguration->voucherConfigurationTiers()->createMany($voucherConfigurationTierData);
        }

        if ($voucherConfigurationData->sale_channel_ids) {
            $voucherConfiguration->saleChannels()->sync($voucherConfigurationData->sale_channel_ids);
        } else {
            $voucherConfiguration->saleChannels()->detach();
        }
    }

    private function getColumnNames(): array
    {
        return [
            'id',
            'company_id',
            'restricted_by_type',
            'voucher_type',
            'exclude_by_type',
            'issue_minimum_spend_amount',
            'use_minimum_spend_amount',
            'validity_days',
            'discount_type',
            'get_value',
            'start_date',
            'end_date',
            'dream_price_applicable',
            'item_wise_promotion_applicable',
            'cart_wide_promotion_applicable',
            'redemption_foot_note',
            'handover_foot_note',
            'title',
            'description',
            'terms_and_conditions',
            'status',
            'is_available_in_ecommerce',
        ];
    }

    public function checkVoucherExistForMysteryGift(int $mysteryGiftId): Collection
    {
        return VoucherConfiguration::query()
            ->with(['voucherConfigurationTiers'])
            ->where('mystery_gift_id', $mysteryGiftId)
            ->get();
    }

    public function getVoucherIdByMysteryGiftId(int $mysteryGiftId, int $discountType): VoucherConfiguration
    {
        return VoucherConfiguration::query()
            ->where('mystery_gift_id', $mysteryGiftId)
            ->where('discount_type', $discountType)
            ->firstOrFail();
    }
}
