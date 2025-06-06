<?php

declare(strict_types=1);

namespace App\Domains\Barcode\Services;

use App\Domains\Common\Enums\BarcodePrintColumns;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Product\ProductQueries;
use App\Models\Brand;
use App\Models\Color;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\Size;
use App\Models\Style;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeServices
{
    public function generatePrintSizeOnePdf(string $filePath, Collection $products, ?string $remark): void
    {
        $pdf = Pdf::loadView('prints.35x30', [
            'products' => $products,
            'remark' => $remark,
            'product_variant' => config('app.product_variant'),
        ]);

        $this->prepareDomPdf([0, 0, 300, 86], $pdf, $filePath);
        // sticker size is 35x30mm(3 sticker print)
        // [0,0] The start top-left corner of the page
        // [300] Width
        // [86] Height
    }

    public function generatePrintSizeTwoPdf(string $filePath, Collection $products, ?string $remark): void
    {
        $pdf = Pdf::loadView('prints.45x40', [
            'products' => $products,
            'remark' => $remark,
            'product_variant' => config('app.product_variant'),
        ]);

        $this->prepareDomPdf([0, 0, 260, 113], $pdf, $filePath);
        // sticker size is 45x40mm(2 sticker print)
        // [0,0] The start top-left corner of the page
        // [260] Width
        // [113] Height
    }

    public function generatePrintSizeThreePdf(string $filePath, Collection $products, ?string $remark): void
    {
        $pdf = Pdf::loadView('prints.35x30_2_sticker', [
            'products' => $products,
            'remark' => $remark,
            'product_variant' => config('app.product_variant'),
        ]);

        $this->prepareDomPdf([0, 0, 215, 94], $pdf, $filePath);
        // sticker size is 35x30mm(2 sticker print)
        // [0,0] The start top-left corner of the page
        // [215] Width
        // [94] Height
    }

    public function generatePrintSizeFourPdf(string $filePath, Collection $products, ?string $remark): void
    {
        $pdf = Pdf::loadView('prints.45x40_1_sticker', [
            'products' => $products,
            'remark' => $remark,
            'product_variant' => config('app.product_variant'),
        ]);

        $this->prepareDomPdf([0, 0, 130, 114], $pdf, $filePath);
        // sticker size is 45x40mm(1 sticker print)
        // [0,0] The start top-left corner of the page
        // [130] Width
        // [114] Height
    }

    private function prepareDomPdf(array $paperSizeParameters, DomPDF $pdf, string $filePath): void
    {
        $pdf->setPaper($paperSizeParameters);
        $pdfContent = $pdf->output();
        Storage::put($filePath, $pdfContent);
    }

    public static function helpCenterMessages(): string
    {
        return "Please check the following steps before submitting the barcode related issues:
            <ul class='list-decimal pl-4 mt-2'>
                <li class='text-justify'>
                    Is the barcode printing contents are clear/visible in the PDF?
                </li>

                <ul class='list-disc pl-4'>
                    <li class='text-justify'>
                        If the content is not clear/visible, do you check if this happen to other printer?
                    </li>

                    <li class='text-justify'>
                        Do you try with other UPC in the barcode printing? In the same printer & also in other printer?
                    </li>
                </ul>

                <li class='text-justify'>
                    Make sure to check printer settings/configuration.
                </li>

                <ul class='list-disc pl-4'>
                    <li class='text-justify'>
                        45x40 2 sticker per row printer should have 'horizontal offset 1.5mm'.
                    </li>

                    <li class='text-justify'>
                        Re-installed the latest drivers of printer/barcode scanner device.
                    </li>
                </ul>

                <li class='text-justify'>
                    Check browser options like Margins, Scale, Paper size & Layouts.
                </li>
            </ul>
        ";
    }

    public function prepareProductsPrint(
        int $companyId,
        array $productIds,
        array $printColumns,
        Collection $requestProducts,
        string $productPrice,
    ): Collection {
        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->getSelectedActiveProductsForBarcodePrint($productIds, $companyId);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);
        $currencySymbol = $currency->getSymbol();

        $barcodeGeneratorPNG = resolve(BarcodeGeneratorPNG::class);

        return $products->transform(function (Product $product) use (
            $barcodeGeneratorPNG,
            $printColumns,
            $requestProducts,
            $productPrice,
            $currencySymbol
        ): Product {
            $product['brand_name'] = '';
            $product['color_name'] = '';
            $product['size_name'] = '';
            $product['style_name'] = '';
            $product['product_variant_values'] = [];

            $printQuantity = $requestProducts->firstWhere('product_id', $product->id)['quantity'];

            $product['print_quantity'] = $printQuantity;

            if (config('app.product_variant')) {
                /** @var MasterProduct $masterProduct */
                $masterProduct = $product->masterProduct;

                $product['article_number'] = '';
                if (in_array(BarcodePrintColumns::ARTICLE_NUMBER->value, $printColumns, true)) {
                    $product['article_number'] = $masterProduct->article_number;
                }

                if (in_array(BarcodePrintColumns::BRAND_NAME->value, $printColumns, true)) {
                    /** @var Brand $brand */
                    $brand = $masterProduct->brand;
                    $product['brand_name'] = $brand->name;
                }

                if (in_array(BarcodePrintColumns::ATTRIBUTES->value, $printColumns, true)) {
                    /** @var Collection $productVariantValues */
                    $productVariantValues = $product->productVariantValues;

                    $product['product_variant_values'] = $productVariantValues->pluck('value');
                }
            } else {
                if (in_array(BarcodePrintColumns::BRAND_NAME->value, $printColumns, true)) {
                    /** @var Brand $brand */
                    $brand = $product->brand;
                    $product['brand_name'] = $brand->name;
                }

                if (! in_array(BarcodePrintColumns::ARTICLE_NUMBER->value, $printColumns, true)) {
                    $product->article_number = null;
                }

                if (in_array(BarcodePrintColumns::COLOR->value, $printColumns, true)) {
                    /** @var ?Color $color */
                    $color = $product->color;

                    $product['color_name'] = $color instanceof Color ? $color->getName() : null;
                }

                if (in_array(BarcodePrintColumns::SIZE->value, $printColumns, true)) {
                    /** @var ?Size $size */
                    $size = $product->size;

                    $product['size_name'] = $size instanceof Size ? $size->getName() : null;
                }

                if (in_array(BarcodePrintColumns::STYLE->value, $printColumns, true)) {
                    /** @var ?Style $style */
                    $style = $product->style;

                    $product['style_name'] = $style instanceof Style ? $style->getName() : null;
                }
            }

            $product['barcode'] = base64_encode(
                $barcodeGeneratorPNG->getBarcode($product->upc, $barcodeGeneratorPNG::TYPE_CODE_128, 3, 150)
            );

            $product['price'] = in_array(
                BarcodePrintColumns::PRICE->value,
                $printColumns,
                true
            ) ? $currencySymbol . $product->{$productPrice} : null;

            return $product;
        });
    }
}
