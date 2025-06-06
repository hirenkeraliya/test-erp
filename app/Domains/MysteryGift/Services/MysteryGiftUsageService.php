<?php

declare(strict_types=1);

namespace App\Domains\MysteryGift\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MysteryGift\MysteryGiftQueries;
use App\Domains\MysteryGift\MysteryGiftUsagesQueries;
use App\Domains\MysteryGiftProduct\MysteryGiftProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Models\Company;
use App\Models\MysteryGift;
use App\Models\Sale;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class MysteryGiftUsageService
{
    public function checkRequestDetails(string $receiptId, string $locationUUId): array
    {
        try {
            $saleQueries = resolve(SaleQueries::class);
            $sale = $saleQueries->getSaleByOfflineId($receiptId);
            if (! $sale || ! in_array($sale->status, [
                SaleStatus::REGULAR_SALE->value,
                SaleStatus::COMPLETE_LAYAWAY_SALE->value,
                SaleStatus::COMPLETE_CREDIT_SALE->value,
            ])) {
                return $this->generateResponse('Nice try, You are not eligible for this discount.');
            }

            if (! isset($sale->member_id)) {
                return [
                    'status' => 'redirect',
                    'route' => 'front.mystery_gift.add_member',
                    'params' => [
                        'locationId' => $locationUUId,
                        'receiptId' => $receiptId,
                    ],
                ];
            }

            $mysteryGiftUsages = $sale->MysteryGiftUsages;
            $mysteryGift = empty($mysteryGiftUsages->MysteryGift) ? null : $mysteryGiftUsages->MysteryGift;

            if ($this->isGiftExpired($mysteryGift)) {
                return $this->generateResponse('Sorry, you are late this offer is already expired.');
            }

            $mysteryGiftQueries = resolve(MysteryGiftQueries::class);
            if ($mysteryGift instanceof MysteryGift) {
                /** @var Voucher $voucher */
                $voucher = $mysteryGiftUsages->voucher;

                $title = '';
                $subtitle = '';

                if (null !== $voucher && $voucher->discount_type == DiscountTypes::FLAT->value) {
                    $title = 'You Get Flat ' . $voucher->flat_amount . ' Off On Next Purchase.';
                    $subtitle = 'only valid till ' . Carbon::parse($voucher->expiry_date)->format('d M Y');
                }

                if (null !== $voucher && $voucher->discount_type == DiscountTypes::PERCENTAGE->value) {
                    $title = 'You Get ' . $voucher->percentage . '% Off On Next Purchase.';
                    $subtitle = 'only valid till ' . Carbon::parse($voucher->expiry_date)->format('d M Y');
                }

                return [
                    'status' => 'view',
                    'view' => 'front/MysteryGift/index',
                    'data' => [
                        'promotion' => [
                            'title' => $title,
                            'subtitle' => $subtitle,
                            'promo_code' => $mysteryGiftUsages->coupon_code,
                        ],
                    ],
                ];
            }

            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getIdByRefIdAndRef($locationUUId);

            /** @var Company $company */
            $company = $locations->company;

            $mysteryGift = $mysteryGiftQueries->getMysteryGiftConfigurations($company->id);

            if (! $mysteryGift instanceof MysteryGift) {
                return $this->generateResponse('Sorry, no offer available for you.');
            }

            if (false === $this->isDatesValid($mysteryGift, $sale->happened_at)) {
                return $this->generateResponse('Sorry, you are late this offer is already expired.');
            }

            if ($sale->total_amount_paid < $mysteryGift->minimum_spend) {
                return $this->generateResponse('Sorry, you are not eligible for this discount.');
            }

            $promotion = $this->generateVoucherOrPromotion($mysteryGift, $sale);

            return [
                'status' => 'view',
                'view' => 'front/MysteryGift/index',
                'data' => [
                    'promotion' => $promotion,
                ],
            ];
        } catch (Throwable $throwable) {
            Log::error('Mystery-Gift', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            return $this->generateResponse('An error occurred. Please try again later.');
        }
    }

    private function generateResponse(string $message): array
    {
        return [
            'status' => 'view',
            'view' => 'front/MysteryGift/index',
            'data' => [
                'message' => $message,
            ],
        ];
    }

    public function isGiftExpired(?MysteryGift $mysteryGift): bool
    {
        return $mysteryGift instanceof MysteryGift && $mysteryGift->end_date && Carbon::parse(
            $mysteryGift->end_date
        )->startOfDay()->lessThan(Carbon::now()->startOfDay());
    }

    public function isDatesValid(MysteryGift $mysteryGift, string $happenedAt): bool
    {
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);

        return Carbon::parse($mysteryGift->start_date)->lessThanOrEqualTo($happenedAtFormat)
            && Carbon::parse($mysteryGift->end_date)->greaterThanOrEqualTo($happenedAtFormat);
    }

    public function generateVoucherOrPromotion(MysteryGift $mysteryGift, Sale $sale): array
    {
        $randomType = $mysteryGift->type[array_rand($mysteryGift->type)];

        return match ($randomType) {
            'is_flat_amount' => $this->generateRandomFlatAmount($mysteryGift, $sale),
            'is_percentage' => $this->generateRandomPercentage($mysteryGift, $sale),
            'is_free_product' => $this->getFreeProductPromoCode($mysteryGift, $sale),
            default => [],
        };
    }

    public function generateRandomFlatAmount(MysteryGift $mysteryGift, Sale $sale): array
    {
        $amount = random_int((int) $mysteryGift->min_flat_amount, (int) $mysteryGift->max_flat_amount);

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherConfiguration = $voucherConfigurationQueries->getVoucherIdByMysteryGiftId(
            $mysteryGift->id,
            DiscountTypes::FLAT->value,
        );

        $voucher = $this->generateVoucher(
            $voucherConfiguration,
            $amount,
            DiscountTypes::FLAT->value,
            null,
            $sale->member_id,
            null,
            $sale->id,
            null,
            null,
        );

        $mysteryGiftUsages = [
            'mystery_gift_id' => $mysteryGift->id,
            'member_id' => $sale->member_id,
            'sale_id' => $sale->id,
            'voucher_id' => $voucher->id,
            'coupon_code' => $voucher->number,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];

        $this->addMysteryGiftUsage($mysteryGiftUsages);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($mysteryGift->company_id);

        return [
            'type' => 'flat',
            'status' => true,
            'value' => $currency->getSymbol() . $amount,
            'description' => 'You Get Flat ' . $amount . ' Off On Next Purchase.',
            'subtitle' => 'only valid till ' . Carbon::parse($voucher->expiry_date)->format('d M Y'),
            'coupon_code' => $voucher->number,
        ];
    }

    public function generateRandomPercentage(MysteryGift $mysteryGift, Sale $sale): array
    {
        $amount = random_int((int) $mysteryGift->min_percentage, (int) $mysteryGift->max_percentage);

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherConfiguration = $voucherConfigurationQueries->getVoucherIdByMysteryGiftId(
            $mysteryGift->id,
            DiscountTypes::PERCENTAGE->value,
        );

        $voucher = $this->generateVoucher(
            $voucherConfiguration,
            $amount,
            DiscountTypes::PERCENTAGE->value,
            null,
            $sale->member_id,
            null,
            $sale->id,
            null,
            null,
        );

        $mysteryGiftUsages = [
            'mystery_gift_id' => $mysteryGift->id,
            'member_id' => $sale->member_id,
            'sale_id' => $sale->id,
            'voucher_id' => $voucher->id,
            'coupon_code' => $voucher->number,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];

        $this->addMysteryGiftUsage($mysteryGiftUsages);

        return [
            'type' => 'discount',
            'status' => true,
            'value' => $amount . '%',
            'description' => 'You Get ' . $amount . '% Off On Next Purchase.',
            'subtitle' => 'only valid till ' . Carbon::parse($voucher->expiry_date)->format('d M Y'),
            'coupon_code' => $voucher->number,
        ];
    }

    public function getFreeProductPromoCode(MysteryGift $mysteryGift, Sale $sale): array
    {
        $productId = $this->getRandomProductId($mysteryGift);

        if (! $productId) {
            return [
                'status' => false,
                'message' => 'Nice try, You are not eligible for this discount.',
            ];
        }

        $productQueries = resolve(ProductQueries::class);
        $product = $productQueries->getByIdOnlyNameAndUpc($productId, $mysteryGift->company_id);
        $couponCode = $this->generateUniqueMysteryCouponCode();
        $mysteryGiftUsages = [
            'mystery_gift_id' => $mysteryGift->id,
            'member_id' => $sale->member_id,
            'sale_id' => $sale->id,
            'product_id' => $productId,
            'coupon_code' => $couponCode,
        ];

        $this->addMysteryGiftUsage($mysteryGiftUsages);

        return [
            'type' => 'product',
            'status' => true,
            'value' => 'Free ' . $product->name,
            'image' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
            'description' => '',
            'subtitle' => 'Only valid till ' . Carbon::parse($mysteryGift->end_date)->format('d M Y'),
            'coupon_code' => $couponCode,
        ];
    }

    private function generateUniqueMysteryCouponCode(): string
    {
        $couponCode = CommonFunctions::getTwelveDigitNumber();

        $promotionPromoCodeQueries = resolve(MysteryGiftUsagesQueries::class);
        $existCouponCode = $promotionPromoCodeQueries->existsByCouponCode($couponCode);

        if ($existCouponCode) {
            return $this->generateUniqueMysteryCouponCode();
        }

        return $couponCode;
    }

    private function addMysteryGiftUsage(array $mysteryGiftUsages): void
    {
        $mysteryGiftUsageQuery = resolve(MysteryGiftUsagesQueries::class);
        $mysteryGiftUsageQuery->addNew($mysteryGiftUsages);
    }

    private function generateVoucher(
        VoucherConfiguration $voucherConfiguration,
        float $getValue,
        int $discountType,
        ?Carbon $expiryDate,
        ?int $memberId = null,
        ?string $voucherNumber = null,
        ?int $saleId = null,
        ?int $locationId = null,
        ?int $orderId = null,
    ): Voucher {
        $voucherQueries = resolve(VoucherQueries::class);

        return $voucherQueries->addNew(
            $voucherConfiguration,
            $getValue,
            $discountType,
            $expiryDate,
            $memberId,
            $voucherNumber,
            $saleId,
            $locationId,
            $orderId
        );
    }

    private function getRandomProductId(MysteryGift $mysteryGift): ?int
    {
        $mysteryGiftProductQueries = resolve(MysteryGiftProductQueries::class);
        $mysteryGiftProduct = $mysteryGiftProductQueries->getRandomProductId($mysteryGift->id);

        return $mysteryGiftProduct?->product_id;
    }
}
