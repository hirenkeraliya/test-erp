<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\CommonFunctions;
use App\Http\Traits\PrepareEnumDataMethods;
use Illuminate\Support\Collection;

enum GeneralSalesReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_ITEM_AND_RECEIPT = 1;
    case BY_RECEIPT_AND_ITEM = 2;
    case BY_COLOR_AND_SIZE = 3;
    case BY_PRODUCT = 4;
    case BY_SUMMARY = 5;
    case BY_PROMOTER_SUMMARY = 6;
    case BY_DATE_AND_BRAND = 7;
    case BY_CURRENT_DAY_VS_PREVIOUS_DAY = 8;
    case BY_SUMMARY_MONTH = 9;
    case BY_ATTRIBUTE = 10;

    public static function formattedForSelection(): array
    {
        return self::filteredCases()
            ->map(fn ($case): array => [
                'id' => $case->value,
                'name' => self::formatCaseName($case->name),
            ])
            ->values()
            ->toArray();
    }

    public static function getFormattedArrayForStaticUse(): array
    {
        $variantsEnabled = config('app.product_variant');

        return collect(self::cases())
            ->filter(function ($type) use ($variantsEnabled): bool {
                if ($variantsEnabled) {
                    return self::BY_COLOR_AND_SIZE != $type;
                }

                return self::BY_ATTRIBUTE != $type;
            })
            ->map(fn ($type): array => [
                CommonFunctions::stringToCamelCase($type->name) => $type->value,
            ])
            ->collapse()
            ->toArray();
    }

    private static function filteredCases(): Collection
    {
        $productVariantEnabled = config('app.product_variant');

        return collect(self::cases())
            ->filter(fn ($case) => match ($case) {
                self::BY_ATTRIBUTE => $productVariantEnabled,
                self::BY_COLOR_AND_SIZE => ! $productVariantEnabled,
                default => true,
            });
    }

    private static function formatCaseName(string $name): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $name)));
    }
}
