<?php

declare(strict_types=1);

namespace App\Domains\Promotion;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PromoCode\PromotionPromoCodeQueries;
use App\Domains\Promotion\Enums\AvailabilityType;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\ProductUploadTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Domains\Promotion\Enums\PromotionTypes;
use App\Domains\Promotion\Enums\PromotionUserRestrictionType;
use App\Domains\Promotion\Enums\Types;
use App\Domains\Promotion\Events\PromotionUpdateEvent;
use App\Domains\PromotionTier\PromotionTierQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Tag\TagQueries;
use App\Models\Location;
use App\Models\Promotion;
use App\Models\PromotionMonthDate;
use App\Models\PromotionTier;
use App\Models\PromotionWeekDay;
use App\Models\SaleChannel;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromotionQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->promotionQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(array $promotionData, int $companyId, User $user): void
    {
        $data = $promotionData;
        unset($data['location_ids']);
        unset($data['member_group_ids']);
        unset($data['employee_group_ids']);
        unset($data['brand_ids']);
        unset($data['tag_ids']);
        unset($data['product_collection_ids']);
        unset($data['regular_product_ids']);
        unset($data['buy_product_ids']);
        unset($data['get_product_ids']);
        unset($data['category_ids']);
        unset($data['week_days']);
        unset($data['month_dates']);
        unset($data['tiers']);
        unset($data['promo_codes']);
        unset($data['sale_channel_ids']);
        unset($data['payment_type_ids']);
        unset($data['membership_ids']);

        $data['company_id'] = $companyId;
        $data['status'] = true;
        $data['created_by_id'] = $user->id;
        $data['created_by_type'] = ModelMapping::getCaseName($user::class);
        $promotion = Promotion::create($data);

        $promotion->locations()->attach($promotionData['location_ids']);
        $this->updatePromotionRelationDetails($promotion, $promotionData);
        $this->updateSaleChannels($promotion, $promotionData);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function setStatus(int $promotionId, int $companyId, bool $status): void
    {
        $promotion = Promotion::query()
            ->where('company_id', $companyId)
            ->findOrFail($promotionId);
        $promotion->status = $status;
        $promotion->save();
    }

    public function getByIdWithRelations(int $promotionId, int $companyId): Promotion
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);

        if (config('app.product_variant')) {
            return Promotion::query()
                ->select(...$this->getColumnNames())
                ->with(
                    'regularProducts:' . $productQueries->getBasicColumnNames(),
                    'regularProducts.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'regularProducts.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'buyProducts:' . $productQueries->getBasicColumnNames(),
                    'buyProducts.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'buyProducts.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'getProducts:' . $productQueries->getBasicColumnNames(),
                    'getProducts.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'getProducts.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'categories:' . $categoryQueries->getBasicColumnNames(),
                    'brands:' . $brandQueries->getBasicColumnNames(),
                    'tags:' . $tagQueries->getBasicColumnNames(),
                    'productCollections:' . $productCollectionQueries->getBasicColumnNames(),
                    'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                    'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                    'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                    'monthly',
                    'weekly',
                    'promotionPromoCodes:' . $promotionPromoCodeQueries->getBasicColumnNames(),
                    'locations:' . $locationQueries->getBasicColumnNames(),
                    'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
                    'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
                )
                ->where('company_id', $companyId)
                ->findOrFail($promotionId);
        }

        return Promotion::query()
            ->select(...$this->getColumnNames())
            ->with(
                'regularProducts:' . $productQueries->getBasicColumnNames(),
                'regularProducts.color:' . $colorQueries->getBasicColumnNames(),
                'regularProducts.size:' . $sizeQueries->getBasicColumnNames(),
                'buyProducts:' . $productQueries->getBasicColumnNames(),
                'buyProducts.color:' . $colorQueries->getBasicColumnNames(),
                'buyProducts.size:' . $sizeQueries->getBasicColumnNames(),
                'getProducts:' . $productQueries->getBasicColumnNames(),
                'getProducts.color:' . $colorQueries->getBasicColumnNames(),
                'getProducts.size:' . $sizeQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'productCollections:' . $productCollectionQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'monthly',
                'weekly',
                'promotionPromoCodes:' . $promotionPromoCodeQueries->getBasicColumnNames(),
                'locations:' . $locationQueries->getBasicColumnNames(),
                'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
                'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
            )
            ->where('company_id', $companyId)
            ->findOrFail($promotionId);
    }

    public function getByIdForClone(int $promotionId, int $companyId): Promotion
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $paymentTypesQueries = resolve(PaymentTypeQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);

        return Promotion::query()
            ->select(...$this->getColumnNames())
            ->with(
                'regularProducts:' . $productQueries->getBasicColumnNames(),
                'buyProducts:' . $productQueries->getBasicColumnNames(),
                'getProducts:' . $productQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'locations:' . $locationQueries->getNameColumnName(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'monthly',
                'weekly',
                'promotionPromoCodes:' . $promotionPromoCodeQueries->getBasicColumnNames(),
                'productCollections:' . $productCollectionQueries->getBasicColumnNames(),
                'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
                'paymentTypes:' . $paymentTypesQueries->getBasicColumnNames(),
                'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
            )
            ->where('company_id', $companyId)
            ->findOrFail($promotionId);
    }

    public function getByIdsWithRelations(array $promotionIds, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);

        return Promotion::query()
            ->select(...$this->getColumnNames())
            ->with(
                'locations:' . $locationQueries->getNameColumnName(),
                'regularProducts:' . $productQueries->getBasicColumnNames(),
                'buyProducts:' . $productQueries->getBasicColumnNames(),
                'getProducts:' . $productQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'monthly',
                'weekly',
                'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
                'promotionPromoCodes:' . $promotionPromoCodeQueries->getBasicColumnNames(),
                'paymentTypes:' . $paymentTypeQueries->getBasicColumnNames(),
            )
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('id', $promotionIds)
            ->get();
    }

    public function getById(int $promotionId, int $companyId): Promotion
    {
        return Promotion::query()
            ->select(...$this->getColumnNames())
            ->where('company_id', $companyId)
            ->findOrFail($promotionId);
    }

    public function update(array $promotionData, Promotion $promotion): void
    {
        $data = $promotionData;
        unset($data['location_ids']);
        unset($data['member_group_ids']);
        unset($data['employee_group_ids']);
        unset($data['brand_ids']);
        unset($data['tag_ids']);
        unset($data['product_collection_ids']);
        unset($data['regular_product_ids']);
        unset($data['buy_product_ids']);
        unset($data['get_product_ids']);
        unset($data['category_ids']);
        unset($data['week_days']);
        unset($data['month_dates']);
        unset($data['tiers']);
        unset($data['promo_codes']);
        unset($data['sale_channel_ids']);
        unset($data['payment_type_ids']);
        unset($data['membership_ids']);

        $promotion->categories()->detach();
        $promotion->brands()->detach();
        $promotion->memberGroups()->detach();
        $promotion->employeeGroups()->detach();
        $promotion->tags()->detach();
        $promotion->promotionTiers()->delete();
        $promotion->monthly()->delete();
        $promotion->weekly()->delete();
        $promotion->regularProducts()->detach();
        $promotion->buyProducts()->detach();
        $promotion->getProducts()->detach();
        $promotion->promotionPromoCodes()->delete();
        $promotion->paymentTypes()->detach();
        $promotion->memberships()->detach();

        $promotion->update($data);
        $this->updatePromotionRelationDetails($promotion, $promotionData);
        $this->updateSaleChannels($promotion, $promotionData);
    }

    public function getListForPosAsPerTimeFrameWithRelatedData(
        Location $location,
        ?string $afterUpdatedAt = null
    ): Collection {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $productCollectionsQueries = resolve(ProductCollectionQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);

        $date = now();

        return Promotion::query()
            ->with([
                'regularProducts:' . $productQueries->getProductColumnsForPos(),
                'buyProducts:' . $productQueries->getProductColumnsForPos(),
                'getProducts:' . $productQueries->getProductColumnsForPos(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'productCollections:' . $productCollectionsQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'promotionPromoCodes:' . $promotionPromoCodeQueries->getBasicColumnNames(),
                'saleItemDiscountPromotionPromoCodes:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleDiscountPromotionPromoCodes:' . $saleDiscountQueries->getBasicColumnNames(),
                'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
                'monthly',
                'weekly',
            ])
            ->whereHas('locations', function ($query) use ($location): void {
                $query->select('id')->where('id', $location->id);
            })
            ->where('company_id', $location->company_id)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            }, function ($query) use ($date): void {
                $query->where('status', true)
                    ->where('is_automatic', true)
                    ->where('is_available_in_pos', true)
                    ->where(function ($query) use ($date): void {
                        $query->where('timeframe_type_id', PromotionTimeframeTypes::getValueByCaseName('NO_LIMIT'))
                            ->orWhere(function ($query) use ($date): void {
                                $query->where(
                                    'timeframe_type_id',
                                    PromotionTimeframeTypes::getValueByCaseName('LIMITED_BY_DATES')
                                )
                                    ->where('start_date', '<=', $date->format('Y-m-d'))
                                    ->where('end_date', '>=', $date->format('Y-m-d'));
                            })->orWhere(function ($query) use ($date): void {
                                $query->where(
                                    'timeframe_type_id',
                                    PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_DAY_OF_THE_WEEK')
                                )
                                    ->whereHas('weekly', function ($query) use ($date): void {
                                        $query->where('week_day', $date->dayOfWeek);
                                    });
                            })->orWhere(function ($query) use ($date): void {
                                $query->where(
                                    'timeframe_type_id',
                                    PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_DAY_OF_THE_MONTH')
                                )
                                    ->whereHas('monthly', function ($query) use ($date): void {
                                        $query->where('month_date', $date->day);
                                    });
                            })->orWhere(function ($query) use ($date): void {
                                $query->where(
                                    'timeframe_type_id',
                                    PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_HOUR_OF_THE_DAY')
                                )
                                    ->where('start_date', '<=', $date->format('Y-m-d'));
                            });
                    });
            })
            ->get();
    }

    public function getListForPosAsPerTimeFrameWithRelatedDataAndManualPromotionOnly(
        Location $location,
        array $filterData
    ): PaginationLengthAwarePaginator {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $productCollectionsQueries = resolve(ProductCollectionQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);

        $date = now();

        return Promotion::query()
            ->with([
                'regularProducts:' . $productQueries->getProductColumnsForPos(),
                'buyProducts:' . $productQueries->getProductColumnsForPos(),
                'getProducts:' . $productQueries->getProductColumnsForPos(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'productCollections:' . $productCollectionsQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'monthly',
                'weekly',
                'promotionPromoCodes:' . $promotionPromoCodeQueries->getBasicColumnNames(),
                'saleItemDiscountPromotionPromoCodes:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleDiscountPromotionPromoCodes:' . $saleDiscountQueries->getBasicColumnNames(),
                'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
            ])
            ->whereHas('locations', function ($query) use ($location): void {
                $query->select('id')->where('id', $location->id);
            })
            ->where('company_id', $location->company_id)
            ->when(array_key_exists('search_text', $filterData) && $filterData['search_text'], function ($query) use (
                $filterData,
                $promotionPromoCodeQueries
            ): void {
                $query->where('name', 'like', '%' . $filterData['search_text'] . '%')
                    ->orWhere(function ($query) use ($filterData, $promotionPromoCodeQueries): void {
                        $query->whereHas(
                            'promotionPromoCodes',
                            $promotionPromoCodeQueries->searchByPromoCode($filterData['search_text'])
                        );
                    });
            })
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            }, function ($query) use ($date): void {
                $query->where('status', true)
                    ->where('is_automatic', false)
                    ->where(function ($query) use ($date): void {
                        $query->where('timeframe_type_id', PromotionTimeframeTypes::getValueByCaseName('NO_LIMIT'))
                            ->orWhere(function ($query) use ($date): void {
                                $query->where(
                                    'timeframe_type_id',
                                    PromotionTimeframeTypes::getValueByCaseName('LIMITED_BY_DATES')
                                )
                                    ->where('start_date', '<=', $date->format('Y-m-d'))
                                    ->where('end_date', '>=', $date->format('Y-m-d'));
                            })->orWhere(function ($query) use ($date): void {
                                $query->where(
                                    'timeframe_type_id',
                                    PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_DAY_OF_THE_WEEK')
                                )
                                    ->whereHas('weekly', function ($query) use ($date): void {
                                        $query->where('week_day', $date->dayOfWeek);
                                    });
                            })->orWhere(function ($query) use ($date): void {
                                $query->where(
                                    'timeframe_type_id',
                                    PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_DAY_OF_THE_MONTH')
                                )
                                    ->whereHas('monthly', function ($query) use ($date): void {
                                        $query->where('month_date', $date->day);
                                    });
                            })->orWhere(function ($query) use ($date): void {
                                $query->where(
                                    'timeframe_type_id',
                                    PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_HOUR_OF_THE_DAY')
                                )
                                    ->where('start_date', '<=', $date->format('Y-m-d'));
                            });
                    });
            })
            ->paginate($filterData['per_page']);
    }

    public function getPromotionOfProvidedPromoCode(Location $location, string $promoCode): ?Promotion
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $productCollectionsQueries = resolve(ProductCollectionQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);

        $date = now();

        return Promotion::query()
            ->with([
                'regularProducts:' . $productQueries->getProductColumnsForPos(),
                'buyProducts:' . $productQueries->getProductColumnsForPos(),
                'getProducts:' . $productQueries->getProductColumnsForPos(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'productCollections:' . $productCollectionsQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'monthly',
                'weekly',
                'promotionPromoCodes' => function ($query) use ($promotionPromoCodeQueries, $promoCode): void {
                    $columns = explode(',', $promotionPromoCodeQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->where('promo_code', $promoCode);
                },
                'saleItemDiscountPromotionPromoCodes:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleDiscountPromotionPromoCodes:' . $saleDiscountQueries->getBasicColumnNames(),
                'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
            ])
            ->whereHas('locations', function ($query) use ($location): void {
                $query->select('id')->where('id', $location->id);
            })
            ->whereHas('promotionPromoCodes', function ($query) use ($promoCode): void {
                $query->where('promo_code', $promoCode);
            })
            ->where('company_id', $location->company_id)
            ->where('status', true)
            ->where('is_automatic', false)
            ->where(function ($query) use ($date): void {
                $query->where('timeframe_type_id', PromotionTimeframeTypes::getValueByCaseName('NO_LIMIT'))
                    ->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMITED_BY_DATES')
                        )
                            ->where('start_date', '<=', $date->format('Y-m-d'))
                            ->where('end_date', '>=', $date->format('Y-m-d'));
                    })->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_DAY_OF_THE_WEEK')
                        )
                            ->whereHas('weekly', function ($query) use ($date): void {
                                $query->where('week_day', $date->dayOfWeek);
                            });
                    })->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_DAY_OF_THE_MONTH')
                        )
                            ->whereHas('monthly', function ($query) use ($date): void {
                                $query->where('month_date', $date->day);
                            });
                    })->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_HOUR_OF_THE_DAY')
                        )
                            ->where('start_date', '<=', $date->format('Y-m-d'));
                    });
            })
            ->first();
    }

    public function removeSelectedProducts(array $promotionData): void
    {
        $promotion = Promotion::select('id')->findOrFail($promotionData['id']);

        if (! $promotion instanceof Promotion) {
            return;
        }

        if (ProductUploadTypes::REGULAR->value === $promotionData['type']) {
            $promotion->regularProducts()->detach();
        }

        if (ProductUploadTypes::BUY_PRODUCT->value === $promotionData['type']) {
            $promotion->buyProducts()->detach();
        }

        if (ProductUploadTypes::GET_PRODUCT->value === $promotionData['type']) {
            $promotion->getProducts()->detach();
        }
    }

    public function getPromotionsExport(array $filterData, int $companyId): Collection
    {
        return $this->promotionQuery($filterData, $companyId)->get();
    }

    public function fetchPromotionProducts(int $id): Promotion
    {
        $productQueries = new ProductQueries();
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Promotion::query()
                ->select('id', 'name')
                ->with([
                    'regularProducts:' . $productQueries->getBasicColumnNames(),
                    'regularProducts.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'regularProducts.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'buyProducts:' . $productQueries->getBasicColumnNames(),
                    'buyProducts.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'buyProducts.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'getProducts:' . $productQueries->getBasicColumnNames(),
                    'getProducts.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'getProducts.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ])
                ->findOrFail($id);
        }

        return Promotion::query()
            ->select('id', 'name')
            ->with([
                'regularProducts:' . $productQueries->getBasicColumnNames(),
                'regularProducts.color:' . $colorQueries->getBasicColumnNames(),
                'regularProducts.size:' . $sizeQueries->getBasicColumnNames(),
                'buyProducts:' . $productQueries->getBasicColumnNames(),
                'buyProducts.color:' . $colorQueries->getBasicColumnNames(),
                'buyProducts.size:' . $sizeQueries->getBasicColumnNames(),
                'getProducts:' . $productQueries->getBasicColumnNames(),
                'getProducts.color:' . $colorQueries->getBasicColumnNames(),
                'getProducts.size:' . $sizeQueries->getBasicColumnNames(),
            ])
            ->findOrFail($id);
    }

    public function getTimeFramePromotion(int $companyId, int $timeFrameTypeId): Collection
    {
        return Promotion::query()
            ->select('id', 'name', 'timeframe_type_id', 'start_date', 'end_date', 'start_time', 'end_time')
            ->where('company_id', $companyId)
            ->where('timeframe_type_id', $timeFrameTypeId)
            ->onlyActive()
            ->get();
    }

    public function getTimeBasedPromotions(int $companyId, int $timeFrameTypeId): Collection
    {
        return Promotion::query()
            ->select('id', 'name', 'timeframe_type_id', 'start_date', 'end_date')
            ->with(['monthly', 'weekly'])
            ->where('company_id', $companyId)
            ->where('timeframe_type_id', $timeFrameTypeId)
            ->whereNull('start_date')
            ->whereNull('end_date')
            ->onlyActive()
            ->get();
    }

    public function updateProductIdsInProductPromotionPivot(int $oldProductId, int $newProductId): void
    {
        DB::table('product_promotion')
            ->where('product_id', $oldProductId)
            ->update([
                'product_id' => $newProductId,
            ]);
    }

    public function getPromotionsForApplication(array $filteredData, int $companyId): LengthAwarePaginator
    {
        $locationQueries = new LocationQueries();
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $productCollectionsQueries = resolve(ProductCollectionQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return Promotion::query()
            ->with([
                'regularProducts:' . $productQueries->getProductColumnsForPos(),
                'buyProducts:' . $productQueries->getProductColumnsForPos(),
                'getProducts:' . $productQueries->getProductColumnsForPos(),
                'regularProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'buyProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'getProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'productCollections:' . $productCollectionsQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'monthly',
                'weekly',
                'promotionPromoCodes:' . $promotionPromoCodeQueries->getBasicColumnNames(),
                'saleItemDiscountPromotionPromoCodes:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleDiscountPromotionPromoCodes:' . $saleDiscountQueries->getBasicColumnNames(),
                'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
            ])
            ->where('company_id', $companyId)
            ->onlyActive()
            ->where('is_automatic', true)
            ->where('is_available_in_pos', true)
            ->when($filteredData['location_id'], function ($query) use ($locationQueries, $filteredData): void {
                $query->whereHas('locations', function ($query) use ($locationQueries, $filteredData): void {
                    $query->where(
                        $locationQueries->filterById((int) $filteredData['location_id'], LocationTypes::STORE->value)
                    );
                });
            })
            ->when(null !== $filteredData['search_text'], function ($query) use ($filteredData): void {
                $query->where('name', 'like', '%' . $filteredData['search_text'] . '%');
            })
            ->when(null !== $filteredData['after_updated_at'], function ($query) use ($filteredData): void {
                $query->where('updated_at', '>=', $filteredData['after_updated_at']);
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->paginate($filteredData['per_page']);
    }

    public function getManualPromotionsForApplication(array $filteredData, int $companyId): LengthAwarePaginator
    {
        $locationQueries = new LocationQueries();
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $productCollectionsQueries = resolve(ProductCollectionQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return Promotion::query()
            ->with([
                'regularProducts:' . $productQueries->getProductColumnsForPos(),
                'buyProducts:' . $productQueries->getProductColumnsForPos(),
                'getProducts:' . $productQueries->getProductColumnsForPos(),
                'regularProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'buyProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'getProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'productCollections:' . $productCollectionsQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'monthly',
                'weekly',
                'promotionPromoCodes:' . $promotionPromoCodeQueries->getBasicColumnNames(),
                'saleItemDiscountPromotionPromoCodes:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleDiscountPromotionPromoCodes:' . $saleDiscountQueries->getBasicColumnNames(),
                'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
            ])
            ->where('company_id', $companyId)
            ->onlyActive()
            ->where('is_automatic', false)
            ->where('is_available_in_pos', true)
            ->when($filteredData['location_id'], function ($query) use ($locationQueries, $filteredData): void {
                $query->whereHas('locations', function ($query) use ($locationQueries, $filteredData): void {
                    $query->where(
                        $locationQueries->filterById((int) $filteredData['location_id'], LocationTypes::STORE->value)
                    );
                });
            })
            ->when(null !== $filteredData['search_text'], function ($query) use ($filteredData): void {
                $query->where('name', 'like', '%' . $filteredData['search_text'] . '%');
            })
            ->when(null !== $filteredData['after_updated_at'], function ($query) use ($filteredData): void {
                $query->where('updated_at', '>=', $filteredData['after_updated_at']);
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->paginate($filteredData['per_page']);
    }

    public function promotionExistsForProduct(int $productId, int $companyId): bool
    {
        $date = now();

        return Promotion::query()
            ->select('id', 'item_wise_promotion_type_id')
            ->where('company_id', $companyId)
            ->where(function ($query): void {
                $query->where(
                    'item_wise_promotion_type_id',
                    ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value
                )
                    ->orWhere(
                        'item_wise_promotion_type_id',
                        ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value
                    );
            })
            ->where(function ($query) use ($date): void {
                $query->where('timeframe_type_id', PromotionTimeframeTypes::getValueByCaseName('NO_LIMIT'))
                    ->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMITED_BY_DATES')
                        )
                            ->where('start_date', '<=', $date->format('Y-m-d'))
                            ->where('end_date', '>=', $date->format('Y-m-d'));
                    })->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_DAY_OF_THE_WEEK')
                        )
                            ->whereHas('weekly', function ($query) use ($date): void {
                                $query->where('week_day', $date->dayOfWeek);
                            });
                    })->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_DAY_OF_THE_MONTH')
                        )
                            ->whereHas('monthly', function ($query) use ($date): void {
                                $query->where('month_date', $date->day);
                            });
                    })->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_HOUR_OF_THE_DAY')
                        )
                            ->where('start_date', '<=', $date->format('Y-m-d'));
                    });
            })
            ->whereHas('regularProducts', function ($query) use ($productId): void {
                $query->select('id')
                    ->where('id', $productId);
            })
            ->onlyActive()
            ->exists();
    }

    public function getListForEcommerceAsPerTimeFrameWithRelatedData(int $companyId, int $locationId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $date = now();

        return Promotion::query()
            ->with([
                'regularProducts:' . $productQueries->getProductColumnsForPos(),
                'buyProducts:' . $productQueries->getProductColumnsForPos(),
                'getProducts:' . $productQueries->getProductColumnsForPos(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'monthly',
                'weekly',
                'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
            ])
            ->whereHas('locations', function ($query) use ($locationId): void {
                $query->select('id')->where('id', $locationId);
            })
            ->where('company_id', $companyId)
            ->where(function ($query) use ($date): void {
                $query->where('timeframe_type_id', PromotionTimeframeTypes::getValueByCaseName('NO_LIMIT'))
                    ->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMITED_BY_DATES')
                        )
                            ->where('start_date', '<=', $date->format('Y-m-d'))
                            ->where('end_date', '>=', $date->format('Y-m-d'));
                    })->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_DAY_OF_THE_WEEK')
                        )
                            ->whereHas('weekly', function ($query) use ($date): void {
                                $query->where('week_day', $date->dayOfWeek);
                            });
                    })->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_DAY_OF_THE_MONTH')
                        )
                            ->whereHas('monthly', function ($query) use ($date): void {
                                $query->where('month_date', $date->day);
                            });
                    })->orWhere(function ($query) use ($date): void {
                        $query->where(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getValueByCaseName('LIMIT_BY_HOUR_OF_THE_DAY')
                        )
                            ->where('start_date', '<=', $date->format('Y-m-d'));
                    });
            })
            ->where('status', true)
            ->where('is_automatic', true)
            ->where('is_available_in_ecommerce', true)
            ->get();
    }

    public function getPromotionsStoreWiseForApplication(
        int $companyId,
        int $locationId,
        ?string $afterUpdatedAt = null
    ): Collection {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $productCollectionsQueries = resolve(ProductCollectionQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return Promotion::query()
            ->with([
                'regularProducts:' . $productQueries->getProductColumnsForPos(),
                'buyProducts:' . $productQueries->getProductColumnsForPos(),
                'getProducts:' . $productQueries->getProductColumnsForPos(),
                'regularProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'buyProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'getProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'productCollections:' . $productCollectionsQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'monthly',
                'weekly',
                'promotionPromoCodes:' . $promotionPromoCodeQueries->getBasicColumnNames(),
                'saleItemDiscountPromotionPromoCodes:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleDiscountPromotionPromoCodes:' . $saleDiscountQueries->getBasicColumnNames(),
                'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
            ])
            ->where('company_id', $companyId)
            ->onlyActive()
            ->where('is_automatic', true)
            ->where('is_available_in_pos', true)
            ->whereHas('locations', function ($query) use ($locationId): void {
                $query->select('id')->where('id', $locationId);
            })
            ->when(null !== $afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function getManualPromotionsStoreWiseForApplication(
        int $companyId,
        int $locationId,
        ?string $afterUpdatedAt = null
    ): Collection {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $productCollectionsQueries = resolve(ProductCollectionQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return Promotion::query()
            ->with([
                'regularProducts:' . $productQueries->getProductColumnsForPos(),
                'buyProducts:' . $productQueries->getProductColumnsForPos(),
                'getProducts:' . $productQueries->getProductColumnsForPos(),
                'regularProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'buyProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'getProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'productCollections:' . $productCollectionsQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'monthly',
                'weekly',
                'promotionPromoCodes:' . $promotionPromoCodeQueries->getBasicColumnNames(),
                'saleItemDiscountPromotionPromoCodes:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleDiscountPromotionPromoCodes:' . $saleDiscountQueries->getBasicColumnNames(),
                'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
            ])
            ->where('company_id', $companyId)
            ->onlyActive()
            ->where('is_automatic', false)
            ->where('is_available_in_pos', true)
            ->whereHas('locations', function ($query) use ($locationId): void {
                $query->select('id')->where('id', $locationId);
            })
            ->when(null !== $afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function getPromotionsOfProvidedPromoCodeForApplication(
        int $companyId,
        int $locationId,
        string $promoCode
    ): ?Promotion {
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $promotionTierQueries = resolve(PromotionTierQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $productCollectionsQueries = resolve(ProductCollectionQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return Promotion::query()
            ->with([
                'regularProducts:' . $productQueries->getProductColumnsForPos(),
                'buyProducts:' . $productQueries->getProductColumnsForPos(),
                'getProducts:' . $productQueries->getProductColumnsForPos(),
                'regularProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'buyProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'getProducts.media:' . $mediaQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'productCollections:' . $productCollectionsQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'promotionTiers:' . $promotionTierQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'tags:' . $tagQueries->getBasicColumnNames(),
                'monthly',
                'weekly',
                'promotionPromoCodes' => function ($query) use ($promotionPromoCodeQueries, $promoCode): void {
                    $columns = explode(',', $promotionPromoCodeQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->where('promo_code', $promoCode);
                },
                'saleItemDiscountPromotionPromoCodes:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleDiscountPromotionPromoCodes:' . $saleDiscountQueries->getBasicColumnNames(),
                'memberships:' . $membershipQueries->getColumnNamesForMemberApi(),
            ])
            ->where('company_id', $companyId)
            ->onlyActive()
            ->where('is_automatic', false)
            ->where('is_available_in_pos', true)
            ->whereHas('locations', function ($query) use ($locationId): void {
                $query->select('id')->where('id', $locationId);
            })
            ->whereHas('promotionPromoCodes', function ($query) use ($promoCode): void {
                $query->where('promo_code', $promoCode);
            })
            ->first();
    }

    public function getPromotionById(int $promotionId): Promotion
    {
        return Promotion::query()
            ->select(
                'id',
                'company_id',
                'name',
                'promotion_applicable_type_id',
                'cart_wide_promotion_type_id',
                'timeframe_type_id',
                'discount_type_id',
                'percentage',
                'flat_amount',
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'status'
            )
            ->with([
                'monthly:' . $this->getBasicPromotionMonthlyColumn(),
                'weekly:' . $this->getBasicWeeklyColumn(),
                'promotionTiers:' . $this->getBasicPromotionTiersColumn(),
            ])
            ->findOrFail($promotionId);
    }

    public function getPromotionByIdForEcommerce(int $promotionId): Promotion
    {
        return Promotion::query()
            ->select('id', 'company_id')
            ->findOrFail($promotionId);
    }

    public function validateLocationAndSaleChannelMatch(Promotion $promotion, SaleChannel $saleChannel): bool
    {
        return Promotion::select('id')->where('id', $promotion->id)
                ->whereHas(
                    'locations',
                    fn ($query) => $query->select(
                        'location_id'
                    )->where('location_id', $saleChannel->default_location_id)
                )
                ->whereHas(
                    'saleChannels',
                    fn ($query) => $query->select('sale_channel_id')->where('sale_channel_id', $saleChannel->id)
                )
                ->exists();
    }

    public function getBasicPromotionMonthlyColumn(): string
    {
        return 'id,promotion_id,month_date';
    }

    public function getBasicWeeklyColumn(): string
    {
        return 'id,promotion_id,week_day';
    }

    public function getBasicPromotionTiersColumn(): string
    {
        return 'id,promotion_id,buy_value,get_value,get_quantity,max_value';
    }

    public function checkPromotionExistForMysteryGiftProduct(int $mysteryGiftId): ?Promotion
    {
        return Promotion::query()
            ->select('id', 'company_id', 'promotion_applicable_type_id', 'start_date', 'end_date')
            ->where('mystery_gift_id', $mysteryGiftId)
            ->Where('promotion_applicable_type_id', PromotionApplicableTypes::ITEM_WISE->value)
            ->first();
    }

    public function checkPromotionExistForMysteryGiftProductWithQuantity(
        int $mysteryGiftId,
        int $productId
    ): ?Promotion {
        return Promotion::query()
            ->select('id', 'company_id', 'promotion_applicable_type_id', 'start_date', 'end_date')
            ->where('mystery_gift_id', $mysteryGiftId)
            ->where('promotion_applicable_type_id', PromotionApplicableTypes::ITEM_WISE->value)
            ->whereHas('regularProducts', function ($query) use ($productId): void {
                $query->where('product_id', $productId);
            })
            ->first();
    }

    public function checkPromotionAndTierExistForMysteryGift(
        int $mysteryGiftId,
        int $applicableTypeId,
        int $discountTypeId,
        int $amount
    ): ?Promotion {
        return Promotion::query()
            ->select('promotion_tiers.id')
            ->leftJoin('promotion_tiers', 'promotion_tiers.promotion_id', 'promotions.id')
            ->where([
                'mystery_gift_id' => $mysteryGiftId,
                'promotion_applicable_type_id' => $applicableTypeId,
                'discount_type_id' => $discountTypeId,
                'get_value' => $amount,
            ])
            ->first();
    }

    public function getBasicColumnNamesForMysteryGift(): string
    {
        return 'id,name,mystery_gift_id,company_id,promotion_applicable_type_id,discount_type_id,cart_wide_promotion_type_id,item_wise_promotion_type_id,timeframe_type_id,percentage,flat_amount,start_date,end_date,start_time,end_time,allow_walk_in_member,status,allow_registered_member,allow_employee,dream_price_applicable,is_automatic,usage_type,is_available_in_pos,is_available_in_ecommerce,is_membership_required';
    }

    private function promotionQuery(array $filterData, int $companyId): Builder
    {
        $locationQueries = resolve(LocationQueries::class);

        $promotionAccess = PromotionUserRestrictionType::getPromotionUseRestrictionCondition(
            (int) $filterData['promotion_user_restriction_type']
        );

        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);

        return Promotion::query()
            ->select(
                'id',
                'mystery_gift_id',
                'name',
                'promotion_applicable_type_id',
                'discount_type_id',
                'cart_wide_promotion_type_id',
                'item_wise_promotion_type_id',
                'timeframe_type_id',
                'flat_amount',
                'percentage',
                'start_date',
                'end_date',
                'status',
                'start_time',
                'end_time',
            )
            ->with([
                'monthly',
                'weekly',
                'saleDiscountPromotion:' . $saleDiscountQueries->getBasicColumnNames(),
                'saleItemDiscountPromotion:' . $saleItemDiscountQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData, $locationQueries): void {
                $query->where(function ($query) use ($filterData, $locationQueries): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereIntegerInRaw(
                            'promotion_applicable_type_id',
                            PromotionApplicableTypes::getMatchingCases($filterData['search_text'])
                        )
                        ->orWhereIntegerInRaw(
                            'discount_type_id',
                            DiscountTypes::getMatchingCases($filterData['search_text'])
                        )
                        ->orWhereIntegerInRaw(
                            'timeframe_type_id',
                            PromotionTimeframeTypes::getMatchingCases($filterData['search_text'])
                        )
                        ->orWhereHas('locations', $locationQueries->searchByName($filterData['search_text']));
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['location_ids'], function ($query) use ($locationQueries, $filterData): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterByIds((array) $filterData['location_ids'], LocationTypes::STORE->value)
                );
            })
            ->when($filterData['promotion_type'], function ($query) use ($filterData): void {
                if ($promotionTypes = PromotionTypes::getPromotionTypeCondition((int) $filterData['promotion_type'])) {
                    $query->where($promotionTypes);
                }
            })
            ->when([] !== $promotionAccess, function ($query) use ($promotionAccess): void {
                $query->where($promotionAccess);
            })
            ->when(null !== $filterData['status_value'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status_value']);
            })
            ->when(
                (int) $filterData['availability_type'] === AvailabilityType::AVAILABLE_IN_POS->value,
                function ($query): void {
                    $query->where('is_available_in_pos', true);
                }
            )
            ->when(
                (int) $filterData['availability_type'] === AvailabilityType::AVAILABLE_IN_ECOMMERCE->value,
                function ($query): void {
                    $query->where('is_available_in_ecommerce', true);
                }
            )
            ->when(
                array_key_exists(
                    'type',
                    $filterData
                ) && null !== $filterData['type'] && (int) $filterData['type'] === Types::SYSTEM_GENERATED->value,
                function ($query): void {
                    $query->whereNotNull('mystery_gift_id');
                }
            )
            ->when(
                array_key_exists(
                    'type',
                    $filterData
                ) && null !== $filterData['type'] && (int) $filterData['type'] === Types::MANUAL->value,
                function ($query): void {
                    $query->whereNull('mystery_gift_id');
                }
            )
            ->when($filterData['id'], function ($query) use ($filterData): void {
                $query->where('id', (int) $filterData['id']);
            })
            ->when(
                $filterData['sort_by'],
                function ($query) use ($filterData): void {
                    $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
                },
                function ($query): void {
                    $query->orderBy('id', 'desc');
                }
            );
    }

    private function updatePromotionRelationDetails(Promotion $promotion, array $promotionData): void
    {
        $promotion->categories()->detach();
        if ($promotionData['category_ids']) {
            $promotion->categories()->attach($promotionData['category_ids']);
        }

        $promotion->brands()->detach();
        if ($promotionData['brand_ids']) {
            $promotion->brands()->attach($promotionData['brand_ids']);
        }

        $promotion->tags()->detach();
        if ($promotionData['tag_ids']) {
            $promotion->tags()->attach($promotionData['tag_ids']);
        }

        $promotion->paymentTypes()->detach();
        if ($promotionData['payment_type_ids']) {
            $promotion->paymentTypes()->attach($promotionData['payment_type_ids']);
        }

        $promotion->productCollections()->detach();
        if ($promotionData['product_collection_ids']) {
            $promotion->productCollections()->attach($promotionData['product_collection_ids']);
        }

        $promotion->memberGroups()->detach();
        if ($promotionData['member_group_ids']) {
            $promotion->memberGroups()->attach($promotionData['member_group_ids']);
        }

        $promotion->employeeGroups()->detach();
        if ($promotionData['employee_group_ids']) {
            $promotion->employeeGroups()->attach($promotionData['employee_group_ids']);
        }

        $promotion->memberships()->detach();
        if ($promotionData['membership_ids']) {
            $promotion->memberships()->attach($promotionData['membership_ids']);
        }

        if ($promotionData['regular_product_ids']) {
            $promotion->uploadedProducts()->syncWithPivotValues(
                $promotionData['regular_product_ids'],
                [
                    'type' => ProductUploadTypes::REGULAR->value,
                ]
            );
        }

        if (array_key_exists('buy_product_ids', $promotionData) && $promotionData['buy_product_ids']) {
            $promotion->uploadedProducts()->syncWithPivotValues(
                $promotionData['buy_product_ids'],
                [
                    'type' => ProductUploadTypes::BUY_PRODUCT->value,
                ]
            );
        }

        if (array_key_exists('get_product_ids', $promotionData) && $promotionData['get_product_ids']) {
            $promotion->uploadedProducts()->syncWithPivotValues(
                $promotionData['get_product_ids'],
                [
                    'type' => ProductUploadTypes::GET_PRODUCT->value,
                ]
            );
        }

        if ($promotionData['tiers']) {
            foreach ($promotionData['tiers'] as $promotionTier) {
                PromotionTier::create([
                    'promotion_id' => $promotion->id,
                    'buy_value' => $promotionTier['buy_value'],
                    'get_value' => $promotionTier['get_value'],
                    'get_quantity' => $promotionTier['get_quantity'] ?? null,
                    'max_value' => $promotionTier['max_value'] ?? null,
                ]);
            }
        }

        if ($promotionData['week_days']) {
            foreach ($promotionData['week_days'] as $week) {
                PromotionWeekDay::create([
                    'promotion_id' => $promotion->id,
                    'week_day' => $week,
                ]);
            }
        }

        if ($promotionData['month_dates']) {
            foreach ($promotionData['month_dates'] as $monthDate) {
                PromotionMonthDate::create([
                    'promotion_id' => $promotion->id,
                    'month_date' => $monthDate,
                ]);
            }
        }

        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        if ($promotionData['promo_codes']) {
            foreach ($promotionData['promo_codes'] as $promoCode) {
                if ($promoCode) {
                    $promotionPromoCodeQueries->addNew($promotion->id, (string) $promoCode);
                }
            }
        }

        event(new PromotionUpdateEvent($promotion));
    }

    private function updateSaleChannels(Promotion $promotion, array $promotionData): void
    {
        if (! array_key_exists('sale_channel_ids', $promotionData)) {
            return;
        }

        if (null === $promotionData['sale_channel_ids']) {
            return;
        }

        $promotion->saleChannels()->sync($promotionData['sale_channel_ids']);
    }

    private function getColumnNames(): array
    {
        return [
            'id',
            'name',
            'promotion_applicable_type_id',
            'discount_type_id',
            'cart_wide_promotion_type_id',
            'item_wise_promotion_type_id',
            'timeframe_type_id',
            'percentage',
            'flat_amount',
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'allow_walk_in_member',
            'status',
            'allow_registered_member',
            'allow_employee',
            'dream_price_applicable',
            'is_automatic',
            'usage_type',
            'is_available_in_pos',
            'is_available_in_ecommerce',
            'is_membership_required',
        ];
    }

    public function getSeasonalSalesBasicColumns(): Closure
    {
        return fn ($query) => $query->select('id', 'name');
    }
}
