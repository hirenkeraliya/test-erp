<?php

declare(strict_types=1);

namespace App\Domains\BookingPaymentProduct;

use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Product\ProductQueries;
use App\Models\BookingPaymentProduct;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingPaymentProductQueries
{
    public function getBasicColumnNames(): string
    {
        return 'id,booking_payment_id,product_id,quantity,box_product_id,product_box_package_type_id,product_box_units,price';
    }

    public function createMany(array $products, int $bookingPaymentId): void
    {
        foreach ($products as $product) {
            $productBoxId = null;
            $productBoxPackageTypeId = null;
            $productBoxUnits = null;

            if (array_key_exists('box_product_id', $product) || array_key_exists('product_bundle_id', $product)) {
                $productBoxId = $product['box_product_id'] ?? $product['product_bundle_id'];
                $boxProductQueries = resolve(BoxProductQueries::class);
                $productBox = $boxProductQueries->getById((int) $productBoxId);

                $productBoxPackageTypeId = $productBox->package_type_id;
                $productBoxUnits = $productBox->units;
            }

            $bookingPaymentProduct = BookingPaymentProduct::create([
                'booking_payment_id' => $bookingPaymentId,
                'product_id' => $product['product_id'],
                'quantity' => $product['quantity'],
                'price' => $product['price'] ?? null,
                'box_product_id' => $productBoxId,
                'product_box_package_type_id' => $productBoxPackageTypeId,
                'product_box_units' => $productBoxUnits,
            ]);

            if (! array_key_exists('promoter_ids', $product)) {
                continue;
            }

            if (! $product['promoter_ids']) {
                continue;
            }

            $bookingPaymentProduct->promoters()->attach($product['promoter_ids']);
        }
    }

    public function deleteBookingPaymentProducts(int $bookingPaymentId): void
    {
        BookingPaymentProduct::where('booking_payment_id', $bookingPaymentId)->delete();
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $productQueries = resolve(ProductQueries::class);

        $bookingPaymentProducts = BookingPaymentProduct::query()
            ->select('id', 'booking_payment_id', 'product_id')
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereHas('product', $productQueries->filterByCompany($companyId))
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($bookingPaymentProducts as $bookingPaymentProduct) {
            $bookingPaymentProduct->product_id = $newProductId;
            $bookingPaymentProduct->save();
        }
    }
}
