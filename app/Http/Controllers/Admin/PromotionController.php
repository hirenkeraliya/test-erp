<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Common\Enums\Statuses;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\PromoCode\PromotionPromoCodeQueries;
use App\Domains\Promotion\DataObjects\PromotionData;
use App\Domains\Promotion\Enums\AvailabilityType;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\ProductUploadTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Domains\Promotion\Enums\PromotionTypes;
use App\Domains\Promotion\Enums\PromotionUsageTypes;
use App\Domains\Promotion\Enums\PromotionUserRestrictionType;
use App\Domains\Promotion\Enums\Types;
use App\Domains\Promotion\Exports\PromotionExport;
use App\Domains\Promotion\Exports\PromotionProductDetailsExport;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\Promotion\Resources\AdminClonePromotionResource;
use App\Domains\Promotion\Resources\AdminEditPromotionResource;
use App\Domains\Promotion\Resources\AdminPromotionDetailsResource;
use App\Domains\Promotion\Resources\AdminPromotionListResource;
use App\Domains\Promotion\Resources\LimitedByDayOfTheWeekPromotionResource;
use App\Domains\Promotion\Resources\TimeFrameHourOfDayPromotionResource;
use App\Domains\Promotion\Resources\TimeFrameMonthlyPromotionResource;
use App\Domains\Promotion\Resources\TimeFramePromotionResource;
use App\Domains\Promotion\Services\PromotionCheckRequestService;
use App\Domains\Promotion\Services\PromotionService;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Tag\TagQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PromotionController extends Controller
{
    public function __construct(
        protected PromotionQueries $promotionQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $promotionId = $request->get('id');

        return Inertia::render('promotions/Index', [
            'itemWisePromotionType' => [
                'limited_to_products' => ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
                'limited_to_categories' => ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value,
                'buy_3_get_1' => ItemWisePromotionTypes::BUY_3_GET_1->value,
                'buy_2_get_50_off_on_others' => ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
                'buy_any_3_or_more_and_get_30_off' => ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value,
                'percentage_discount_for_next_item' => ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value,
                'flat_discount_for_next_item' => ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value,
                'cheapest_free' => ItemWisePromotionTypes::CHEAPEST_FREE->value,
                'bundle_buy' => ItemWisePromotionTypes::BUNDLE_BUY->value,
                'gift_with_purchase' => ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value,
                'buy_2_get_rm_50_off_on_others' => ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value,
                'buy_any_3_or_more_and_get_rm_30_off' => ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value,
                'buy_2_and_get_1_quantity_at_rm1' => ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
                'limited_to_brands' => ItemWisePromotionTypes::LIMITED_TO_BRANDS->value,
                'as_per_amount_limited_to_brands' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value,
                'as_per_amount_get_off_on_others' => ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
                'as_per_amount_limited_to_price' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE,
                'limited_to_tags' => ItemWisePromotionTypes::LIMITED_TO_TAGS->value,
                'limited_to_product_collection' => ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value,
            ],
            'statuses' => Statuses::getList(),
            'promotionTypes' => PromotionTypes::getList(),
            'locations' => $locations,
            'promotionUserRestrictionType' => PromotionUserRestrictionType::getList(),
            'availabilityType' => AvailabilityType::getList(),
            'types' => Types::getList(),
            'promotionId' => (int) $promotionId,
            'exportPermission' => PermissionList::getExportPermissionName('promotion'),
            'allStatuses' => Statuses::getFormattedArrayForStaticUse(),
            'allTypes' => Types::getFormattedArrayForStaticUse(),
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchPromotions(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'status_value' => $request->get('status_value'),
            'promotion_type' => $request->get('promotion_type'),
            'id' => $request->get('id'),
            'promotion_user_restriction_type' => $request->get('promotion_user_restriction_type'),
            'availability_type' => $request->get('availability_type'),
            'type' => $request->get('type'),
        ];
        $lengthAwarePaginator = $this->promotionQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminPromotionListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        [$locations, $categories, $brands, $memberGroups, $employeeGroups, $tags, $productCollections, $saleChannels, $paymentTypes, $memberships] = $this->fetchCommonRecords(
            session('admin_company_id')
        );

        return Inertia::render('promotions/Add', [
            'locations' => $locations,
            'categories' => $categories,
            'brands' => $brands,
            'memberGroups' => $memberGroups,
            'tags' => $tags,
            'productCollections' => $productCollections,
            'employeeGroups' => $employeeGroups,
            'timeFrames' => PromotionTimeframeTypes::getList(),
            'promotionApplicableTypes' => PromotionApplicableTypes::getList(),
            'staticDetails' => $this->getStaticDetails(),
            'staticProductUploadTypes' => [
                'regularProductUploadType' => ProductUploadTypes::REGULAR->value,
                'buyProductUploadType' => ProductUploadTypes::BUY_PRODUCT->value,
                'getProductUploadType' => ProductUploadTypes::GET_PRODUCT->value,
            ],
            'promotionUsageTypes' => PromotionUsageTypes::getList(),
            'promotionSingleUsage' => PromotionUsageTypes::SINGLE_USE->value,
            'saleChannels' => $saleChannels,
            'paymentTypes' => $paymentTypes,
            'memberships' => $memberships,
        ]);
    }

    public function store(PromotionData $promotionData, Request $request): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $promotionCheckRequestService = resolve(PromotionCheckRequestService::class);

        $promotionCheckRequestService->validateLocationIds($companyId, (array) $promotionData->location_ids);

        $this->checkRequestDetails($companyId, $promotionData);

        DB::beginTransaction();

        try {
            /** @var User $user */
            $user = $request->user();

            $promotionDetails = $this->getPromotionDetails($promotionData->toArray());
            $this->promotionQueries->addNew($promotionDetails, $companyId, $user);

            DB::commit();

            return to_route('admin.promotions.index')->with('success', 'Promotion added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Promotion', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function edit(int $promotionId): Response
    {
        $companyId = session('admin_company_id');
        $categoryQueries = resolve(CategoryQueries::class);
        $promotionService = resolve(PromotionService::class);
        $promotion = $this->promotionQueries->getByIdWithRelations($promotionId, $companyId);

        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getAllByCompanyId($companyId);

        $paymentTypesQueries = resolve(PaymentTypeQueries::class);
        $paymentTypes = $paymentTypesQueries->getAllByCompanyId($companyId);

        $membershipQueries = resolve(MembershipQueries::class);
        $memberships = $membershipQueries->getWithBasicColumns($companyId);

        return Inertia::render('promotions/Edit', [
            'promotion' => new AdminEditPromotionResource($promotion),
            'categories' => $categoryQueries->getMainCategoriesWithBasicColumns($companyId),
            'memberGroups' => $memberGroupQueries->getByCompanyId($companyId),
            'tags' => $tagQueries->getWithBasicColumns($companyId),
            'productCollections' => $productCollectionQueries->getProductCollections($companyId),
            'employeeGroups' => $employeeGroupQueries->getByCompanyId($companyId),
            'timeFrames' => PromotionTimeframeTypes::getList(),
            'promotionName' => $promotionService->getPromotionTypeLabel($promotion),
            'staticDetails' => $this->getStaticDetails(),
            'staticProductUploadTypes' => [
                'regularProductUploadType' => ProductUploadTypes::REGULAR->value,
                'buyProductUploadType' => ProductUploadTypes::BUY_PRODUCT->value,
                'getProductUploadType' => ProductUploadTypes::GET_PRODUCT->value,
            ],
            'promotionUsageTypes' => PromotionUsageTypes::getList(),
            'promotionSingleUsage' => PromotionUsageTypes::SINGLE_USE->value,
            'saleChannels' => $saleChannels,
            'paymentTypes' => $paymentTypes,
            'memberships' => $memberships,
        ]);
    }

    public function update(PromotionData $promotionData, int $promotionId): RedirectResponse
    {
        $companyId = session('admin_company_id');

        $promotion = $this->promotionQueries->getById($promotionId, $companyId);

        if (false === $promotion->status) {
            abort(417, 'This promotion is currently inactive.');
        }

        $this->checkRequestDetails($companyId, $promotionData, $promotionId);

        DB::beginTransaction();

        try {
            $promotionDetails = $this->getPromotionDetails($promotionData->toArray());

            $this->promotionQueries->update($promotionDetails, $promotion);

            DB::commit();

            return to_route('admin.promotions.index')->with('success', 'Promotion has been successfully updated.');
        } catch (Throwable $throwable) {
            Log::error('Update Promotion', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again');
        }
    }

    public function setStatus(int $promotionId, bool $status): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $this->promotionQueries->setStatus($promotionId, session('admin_company_id'), $status);

            DB::commit();

            return to_route('admin.promotions.index')->with('success', 'Status changed successfully.');
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('Promotion Status Change', [
                'error_message' => $exception->getMessage(),
                'error_code' => 'Error code: ' . $exception->getCode(),
                'file' => 'File: ' . $exception->getFile(),
                'line' => 'Line: ' . $exception->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($exception->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$exception],
            ]);

            abort(417, 'An error occurred. Please try again.');
        }
    }

    /**
     * @return mixed[]
     * Public instead of the private because we are directly access on the tests
     */
    public function getPromotionDetails(array $validatedData): array
    {
        $details = [
            'name' => $validatedData['name'],
            'location_ids' => $validatedData['location_ids'],
            'member_group_ids' => $validatedData['member_group_ids'],
            'employee_group_ids' => $validatedData['employee_group_ids'],
            'brand_ids' => $validatedData['brand_ids'],
            'tag_ids' => $validatedData['tag_ids'],
            'product_collection_ids' => $validatedData['product_collection_ids'],
            'regular_product_ids' => null,
            'buy_product_ids' => null,
            'get_product_ids' => null,
            'category_ids' => null,
            'start_date' => null,
            'end_date' => null,
            'week_days' => null,
            'month_dates' => null,
            'start_time' => null,
            'end_time' => null,
            'promotion_applicable_type_id' => $validatedData['promotion_applicable_type_id'],
            'discount_type_id' => $validatedData['discount_type_id'],
            'cart_wide_promotion_type_id' => $validatedData['cart_wide_promotion_type_id'],
            'item_wise_promotion_type_id' => $validatedData['item_wise_promotion_type_id'],
            'timeframe_type_id' => $validatedData['timeframe_type_id'],
            'percentage' => null,
            'flat_amount' => null,
            'tiers' => null,
            'allow_employee' => $validatedData['allow_employee'],
            'allow_registered_member' => $validatedData['allow_registered_member'],
            'allow_walk_in_member' => $validatedData['allow_walk_in_member'],
            'is_membership_required' => $validatedData['is_membership_required'],
            'dream_price_applicable' => $validatedData['dream_price_applicable'],
            'is_available_in_pos' => $validatedData['is_available_in_pos'],
            'is_available_in_ecommerce' => $validatedData['is_available_in_ecommerce'],
            'is_automatic' => $validatedData['is_automatic'],
            'usage_type' => $validatedData['usage_type'],
            'promo_codes' => $validatedData['promo_codes'],
            'sale_channel_ids' => $validatedData['sale_channel_ids'],
            'payment_type_ids' => $validatedData['payment_type_ids'],
            'membership_ids' => $validatedData['membership_ids'],
        ];

        $details = [...$details, ...$this->getTimeframeDetails($validatedData)];

        if (
            PromotionApplicableTypes::CART_WIDE->value === $validatedData['promotion_applicable_type_id']
            && in_array(
                $validatedData['cart_wide_promotion_type_id'],
                [CartWidePromotionTypes::AS_PER_AMOUNT->value, CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value])
        ) {
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value === $validatedData['item_wise_promotion_type_id']) {
            $details['regular_product_ids'] = $validatedData['regular_product_ids'];

            if (DiscountTypes::PERCENTAGE->value === $details['discount_type_id']) {
                $details['percentage'] = $validatedData['percentage'];
            }

            if (DiscountTypes::FLAT->value === $details['discount_type_id']) {
                $details['flat_amount'] = $validatedData['flat_amount'];
            }

            return $details;
        }

        if (ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value === $validatedData['item_wise_promotion_type_id']) {
            $details['category_ids'] = $validatedData['category_ids'];

            if (DiscountTypes::PERCENTAGE->value === $details['discount_type_id']) {
                $details['percentage'] = $validatedData['percentage'];
            }

            if (DiscountTypes::FLAT->value === $details['discount_type_id']) {
                $details['flat_amount'] = $validatedData['flat_amount'];
            }

            return $details;
        }

        if (ItemWisePromotionTypes::LIMITED_TO_BRANDS->value === $validatedData['item_wise_promotion_type_id']) {
            $details['brand_ids'] = $validatedData['brand_ids'];

            if (DiscountTypes::PERCENTAGE->value === $details['discount_type_id']) {
                $details['percentage'] = $validatedData['percentage'];
            }

            if (DiscountTypes::FLAT->value === $details['discount_type_id']) {
                $details['flat_amount'] = $validatedData['flat_amount'];
            }

            return $details;
        }

        if (ItemWisePromotionTypes::LIMITED_TO_TAGS->value === $validatedData['item_wise_promotion_type_id']) {
            $details['tag_ids'] = $validatedData['tag_ids'];

            if (DiscountTypes::PERCENTAGE->value === $details['discount_type_id']) {
                $details['percentage'] = $validatedData['percentage'];
            }

            if (DiscountTypes::FLAT->value === $details['discount_type_id']) {
                $details['flat_amount'] = $validatedData['flat_amount'];
            }

            return $details;
        }

        if (ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value === $validatedData['item_wise_promotion_type_id']) {
            $details['product_collection_ids'] = $validatedData['product_collection_ids'];

            if (DiscountTypes::PERCENTAGE->value === $details['discount_type_id']) {
                $details['percentage'] = $validatedData['percentage'];
            }

            if (DiscountTypes::FLAT->value === $details['discount_type_id']) {
                $details['flat_amount'] = $validatedData['flat_amount'];
            }

            return $details;
        }

        if (ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value === $validatedData['item_wise_promotion_type_id']) {
            $details['brand_ids'] = $validatedData['brand_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::BUY_3_GET_1->value === $validatedData['item_wise_promotion_type_id']) {
            $details['buy_product_ids'] = $validatedData['buy_product_ids'];
            $details['get_product_ids'] = $validatedData['get_product_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value === $validatedData['item_wise_promotion_type_id']) {
            $details['buy_product_ids'] = $validatedData['buy_product_ids'];
            $details['get_product_ids'] = $validatedData['get_product_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value === $validatedData['item_wise_promotion_type_id']) {
            $details['buy_product_ids'] = $validatedData['buy_product_ids'];
            $details['get_product_ids'] = $validatedData['get_product_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::CHEAPEST_FREE->value === $validatedData['item_wise_promotion_type_id']) {
            $details['regular_product_ids'] = $validatedData['regular_product_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value === $validatedData['item_wise_promotion_type_id']) {
            $details['regular_product_ids'] = $validatedData['regular_product_ids'];
            $details['tiers'] = $validatedData['tiers'];
        }

        if (ItemWisePromotionTypes::BUNDLE_BUY->value === $validatedData['item_wise_promotion_type_id']) {
            $details['regular_product_ids'] = $validatedData['regular_product_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value === $validatedData['item_wise_promotion_type_id']) {
            $details['regular_product_ids'] = $validatedData['regular_product_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value === $validatedData['item_wise_promotion_type_id']) {
            $details['regular_product_ids'] = $validatedData['regular_product_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value === $validatedData['item_wise_promotion_type_id']) {
            $details['regular_product_ids'] = $validatedData['regular_product_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value === $validatedData['item_wise_promotion_type_id']) {
            $details['regular_product_ids'] = $validatedData['regular_product_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value === $validatedData['item_wise_promotion_type_id']) {
            $details['buy_product_ids'] = $validatedData['buy_product_ids'];
            $details['get_product_ids'] = $validatedData['get_product_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value === $validatedData['item_wise_promotion_type_id']) {
            $details['regular_product_ids'] = $validatedData['regular_product_ids'];
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        if (ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value === $validatedData['item_wise_promotion_type_id']) {
            $details['tiers'] = $validatedData['tiers'];

            return $details;
        }

        return $details;
    }

    public function clone(int $promotionId): Response
    {
        $companyId = session('admin_company_id');
        $promotionService = resolve(PromotionService::class);
        $promotion = $this->promotionQueries->getByIdForClone($promotionId, $companyId);

        [$locations, $categories, $brands, $memberGroups, $employeeGroups, $tags, $productCollections, $saleChannels, $paymentTypes, $memberships] = $this->fetchCommonRecords(
            $companyId
        );

        return Inertia::render('promotions/Add', [
            'locations' => $locations,
            'brands' => $brands,
            'memberGroups' => $memberGroups,
            'tags' => $tags,
            'employeeGroups' => $employeeGroups,
            'clonePromotion' => new AdminClonePromotionResource($promotion),
            'categories' => $categories,
            'timeFrames' => PromotionTimeframeTypes::getList(),
            'promotionName' => $promotionService->getPromotionTypeLabel($promotion),
            'promotionApplicableTypes' => PromotionApplicableTypes::getList(),
            'staticDetails' => $this->getStaticDetails(),
            'promotionUsageTypes' => PromotionUsageTypes::getList(),
            'promotionSingleUsage' => PromotionUsageTypes::MULTIPLE_USE->value,
            'staticProductUploadTypes' => [
                'regularProductUploadType' => ProductUploadTypes::REGULAR->value,
                'buyProductUploadType' => ProductUploadTypes::BUY_PRODUCT->value,
                'getProductUploadType' => ProductUploadTypes::GET_PRODUCT->value,
            ],
            'productCollections' => $productCollections,
            'saleChannels' => $saleChannels,
            'paymentTypes' => $paymentTypes,
            'memberships' => $memberships,
        ]);
    }

    public function removeSelectedProducts(Request $request): void
    {
        $validatedData = $request->validate([
            'id' => ['required', 'exists:promotions,id'],
            'type' => ['required', 'integer', 'in:' . ProductUploadTypes::getValues()],
        ]);

        $this->promotionQueries->removeSelectedProducts($validatedData);
    }

    public function exportPromotions(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'status_value' => $request->get('status_value'),
            'promotion_type' => $request->get('promotion_type'),
            'promotion_user_restriction_type' => $request->get('promotion_user_restriction_type'),
            'id' => $request->get('id'),
            'availability_type' => $request->get('availability_type'),
            'type' => $request->get('type'),
        ];

        $promotions = $this->promotionQueries->getPromotionsExport($filterData, session('admin_company_id'));

        return Excel::download(new PromotionExport($promotions), $filename);
    }

    public function exportPromotionsProductsDetails(int $id, string $filename, Request $request): BinaryFileResponse
    {
        $validatedData = $request->validate([
            'type' => ['required', 'integer', 'in:' . ProductUploadTypes::getValues()],
        ]);

        $promotion = $this->promotionQueries->fetchPromotionProducts($id);

        return Excel::download(new PromotionProductDetailsExport($promotion, (int) $validatedData['type']), $filename);
    }

    /**
     * @return array<string, AdminPromotionDetailsResource>
     */
    public function fetchPromotionDetailsById(int $promotionId): array
    {
        $promotionDetails = $this->promotionQueries->getByIdWithRelations($promotionId, session('admin_company_id'));

        return [
            'promotion_details' => new AdminPromotionDetailsResource($promotionDetails),
        ];
    }

    public function generatePromoCodes(int $totalPromoCodes): array
    {
        $promoCodes = [];

        for ($i = 0; $i < $totalPromoCodes; $i++) {
            $promoCodes[] = $this->generateUniquePromoCode();
        }

        return [
            'promo_codes' => $promoCodes,
        ];
    }

    public function generateUniquePromoCode(): string
    {
        $promoCode = CommonFunctions::getTwelveDigitNumber();

        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $existPromoCode = $promotionPromoCodeQueries->existsByPromoCode($promoCode, session('admin_company_id'));

        if ($existPromoCode) {
            return $this->generateUniquePromoCode();
        }

        return $promoCode;
    }

    public function existsPromoCodes(Request $request, ?int $promotionId = null): array
    {
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);
        $existPromoCode = $promotionPromoCodeQueries->doPromoCodeExists(
            $request->promoCodes,
            session('admin_company_id'),
            $promotionId
        );

        return [
            'exists_promo_codes' => $existPromoCode,
        ];
    }

    public function fetchCalender(): Response
    {
        $companyId = session('admin_company_id');

        $promotionLimitedDates = $this->promotionQueries->getTimeFramePromotion(
            $companyId,
            PromotionTimeframeTypes::LIMITED_BY_DATES->value
        );

        $promotionLimitByHourOfTheDay = $this->promotionQueries->getTimeFramePromotion(
            $companyId,
            PromotionTimeframeTypes::LIMIT_BY_HOUR_OF_THE_DAY->value
        );

        $promotionWeekly = $this->promotionQueries->getTimeBasedPromotions(
            $companyId,
            PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value
        );

        $promotionMonthly = $this->promotionQueries->getTimeBasedPromotions(
            $companyId,
            PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value
        );

        return Inertia::render('promotions/PromotionCalendar', [
            'promotionLimitedDates' => TimeFramePromotionResource::collection($promotionLimitedDates),
            'promotionWeekly' => LimitedByDayOfTheWeekPromotionResource::collection($promotionWeekly),
            'promotionMonthly' => TimeFrameMonthlyPromotionResource::collection($promotionMonthly),
            'promotionHourOfTheDay' => TimeFrameHourOfDayPromotionResource::collection($promotionLimitByHourOfTheDay),
        ]);
    }

    private function checkRequestDetails(int $companyId, PromotionData $promotionData, ?int $promotionId = null): void
    {
        $promotionCheckRequestService = resolve(PromotionCheckRequestService::class);

        if ($promotionData->category_ids) {
            $promotionCheckRequestService->validateCategoryIds($companyId, $promotionData->category_ids);
        }

        if ($promotionData->tag_ids) {
            $promotionCheckRequestService->validateTagIds($companyId, $promotionData->tag_ids);
        }

        if ($promotionData->sale_channel_ids) {
            $promotionCheckRequestService->validateSaleChannelIds($companyId, $promotionData->sale_channel_ids);
        }

        if ($promotionData->regular_product_ids) {
            $promotionCheckRequestService->validateRegularProductIds($companyId, $promotionData->regular_product_ids);
        }

        if (
            $promotionData->regular_product_ids
            && (
                ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value === $promotionData->item_wise_promotion_type_id
                || ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value === $promotionData->item_wise_promotion_type_id
            )
        ) {
            $promotionCheckRequestService->validateRegularProductPrice($companyId, $promotionData->regular_product_ids);
        }

        if ($promotionData->buy_product_ids) {
            $promotionCheckRequestService->validateBuyProductIds($companyId, $promotionData->buy_product_ids);
        }

        if ($promotionData->get_product_ids) {
            $promotionCheckRequestService->validateGetProductIds($companyId, $promotionData->get_product_ids);
        }

        if ($promotionData->promo_codes) {
            $promotionCheckRequestService->validatePromCodes($companyId, $promotionData->promo_codes, $promotionId);
        }
    }

    private function getTimeframeDetails(array $validatedData): array
    {
        if (PromotionTimeframeTypes::LIMITED_BY_DATES->value === $validatedData['timeframe_type_id']) {
            return [
                'start_date' => $validatedData['start_date'],
                'end_date' => $validatedData['end_date'],
            ];
        }

        if (PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value === $validatedData['timeframe_type_id']) {
            return [
                'week_days' => $validatedData['week_days'],
            ];
        }

        if (PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value === $validatedData['timeframe_type_id']) {
            return [
                'month_dates' => $validatedData['month_dates'],
            ];
        }

        if (PromotionTimeframeTypes::LIMIT_BY_HOUR_OF_THE_DAY->value === $validatedData['timeframe_type_id']) {
            return [
                'start_time' => $validatedData['start_time'],
                'end_time' => $validatedData['end_time'],
                'start_date' => $validatedData['start_date'],
            ];
        }

        return [];
    }

    /**
     * @return array<int, mixed[]>|Collection[]
     */
    private function fetchCommonRecords(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $memberGroupQueries = resolve(MemberGroupQueries::class);

        $memberGroups = $memberGroupQueries->getByCompanyId($companyId);

        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        $employeeGroups = $employeeGroupQueries->getByCompanyId($companyId);

        $categoryQueries = resolve(CategoryQueries::class);

        $categories = $categoryQueries->getMainCategoriesWithBasicColumns(session('admin_company_id'));

        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getWithBasicColumns();

        $tagQueries = resolve(TagQueries::class);
        $tags = $tagQueries->getWithBasicColumns($companyId);

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getAllByCompanyId($companyId);

        $paymentTypesQueries = resolve(PaymentTypeQueries::class);
        $paymentTypes = $paymentTypesQueries->getAllByCompanyId($companyId);

        $membershipQueries = resolve(MembershipQueries::class);
        $memberships = $membershipQueries->getWithBasicColumns($companyId);

        return [
            $locations,
            $categories,
            $brands,
            $memberGroups,
            $employeeGroups,
            $tags,
            $productCollections,
            $saleChannels,
            $paymentTypes,
            $memberships,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getStaticDetails(): array
    {
        return [
            'type_percentage' => DiscountTypes::PERCENTAGE,
            'type_flat' => DiscountTypes::FLAT,
            'cart_type_as_per_amount' => CartWidePromotionTypes::AS_PER_AMOUNT,
            'cart_type_as_per_payment_type' => CartWidePromotionTypes::AS_PER_PAYMENT_TYPE,
            'timeframe_manually_anytime' => PromotionTimeframeTypes::NO_LIMIT,
            'timeframe_date_wise' => PromotionTimeframeTypes::LIMITED_BY_DATES,
            'timeframe_every_week_day' => PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK,
            'timeframe_every_month_day' => PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH,
            'timeframe_every_hour_day' => PromotionTimeframeTypes::LIMIT_BY_HOUR_OF_THE_DAY,
            'limited_cart_wide' => PromotionApplicableTypes::CART_WIDE,
            'limited_item_wise' => PromotionApplicableTypes::ITEM_WISE,
            'limited_to_products' => ItemWisePromotionTypes::LIMITED_TO_PRODUCTS,
            'limited_to_categories' => ItemWisePromotionTypes::LIMITED_TO_CATEGORIES,
            'limited_to_brands' => ItemWisePromotionTypes::LIMITED_TO_BRANDS,
            'limited_to_tags' => ItemWisePromotionTypes::LIMITED_TO_TAGS,
            'limited_to_product_collection' => ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION,
            'quantity_buy_three_get_one' => ItemWisePromotionTypes::BUY_3_GET_1,
            'quantity_buy_two_get_50_off' => ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS,
            'quantity_buy_any_three_get_percentage_off' => ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF,
            'percentage_discount_for_next_item' => ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM,
            'flat_discount_for_next_item' => ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM,
            'quantity_buy_two_get_RM_50_off' => ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS,
            'quantity_buy_any_three_get_flat_off' => ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF,
            'quantity_cheapest_free' => ItemWisePromotionTypes::CHEAPEST_FREE,
            'gift_with_purchase' => ItemWisePromotionTypes::GIFT_WITH_PURCHASE,
            'quantity_bundle_buy' => ItemWisePromotionTypes::BUNDLE_BUY,
            'quantity_buy_two_and_get_one_quantity_at_rm_one' => ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1,
            'as_per_amount_limited_to_brands' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS,
            'as_per_amount_get_off_on_others' => ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS,
            'as_per_amount_limited_to_price' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE,
        ];
    }
}
