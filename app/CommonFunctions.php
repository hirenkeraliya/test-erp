<?php

declare(strict_types=1);

namespace App;

use App\Domains\Brand\BrandQueries;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\City\CityQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughFilterTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughIncludeTypes;
use App\Domains\Size\SizeQueries;
use App\Domains\State\StateQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Models\Cashier;
use App\Models\Color;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Department;
use App\Models\Location;
use App\Models\Style;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CommonFunctions
{
    public function anyRecordsMissing(string $modal, string $column, array $requestRecords): bool
    {
        if ([] === $requestRecords) {
            return false;
        }

        $filteredRequestRecords = array_unique(array_filter($requestRecords));
        if ([] === $filteredRequestRecords) {
            return false;
        }

        $totalRecords = $modal::query()->whereInCaseSensitive($column, $filteredRequestRecords)->count();

        return $totalRecords < count($filteredRequestRecords);
    }

    public static function numberFormat(float $amount, int $toDigit = 2): float
    {
        return (float) self::numberFormatString($amount, $toDigit);
    }

    public static function numberFormatString(float $amount, int $toDigit = 2): string
    {
        return number_format($amount, $toDigit, '.', '');
    }

    public static function getCashierCompanyId(Cashier $cashier): int
    {
        $cashierQueries = resolve(CashierQueries::class);

        return $cashierQueries->getCashierCompanyId($cashier);
    }

    public static function getCurrencySymbol(int $companyId): ?string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getCountryCurrencySymbol($companyId);

        $country = $company->defaultCountry;

        if (! $country instanceof Country) {
            return null;
        }

        $currency = $country->currency;

        if (! $currency instanceof Currency) {
            return null;
        }

        return $currency->getSymbol();
    }

    public static function compareFloatNumbers(float $number1, float $number2): bool
    {
        $epsilon = 0.0001;

        return abs($number1 - $number2) < $epsilon;
    }

    public static function getTwelveDigitNumber(): string
    {
        return Str::upper(Str::random(4)) . random_int(1111, 9999) . Str::upper(Str::random(4));
    }

    public static function currencyFormat(float $amount, int $toDigit = 2): string
    {
        return number_format($amount, $toDigit, '.', ',');
    }

    public static function currencyFormatInteger(int $amount): string
    {
        return number_format($amount, 0, '.', ',');
    }

    public static function truncateDecimal(float $quantity): string
    {
        return (string) round($quantity, 2);
    }

    public static function getProjectCodeFolders(): array
    {
        // Default will be all the files inside the app and tests folders.
        $rootFolderDirectoryPath = dirname(__DIR__);

        $paths = [
            $rootFolderDirectoryPath . '/app',
            $rootFolderDirectoryPath . '/database',
            $rootFolderDirectoryPath . '/tests',
            $rootFolderDirectoryPath . '/routes',
        ];

        $filePath = 'file-changed-list.txt';

        if (is_file($filePath)) {
            $changedFiles = file($filePath);
            if (is_countable($changedFiles) && [] !== $changedFiles) {
                $paths = [];

                foreach ($changedFiles as $changedFile) {
                    if (! static::isFileInAllowedDirectories($changedFile)) {
                        continue;
                    }

                    $paths[] = str_replace("\n", '', $changedFile);
                }
            }
        }

        return $paths;
    }

    public static function isFileInAllowedDirectories(string $changedFile): bool
    {
        $directories = ['app', 'database', 'tests', 'routes'];

        foreach ($directories as $directory) {
            if (str_starts_with($changedFile, $directory)) {
                return true;
            }
        }

        return false;
    }

    public static function stringLowerCase(string $column): string
    {
        return Str::lower(str_replace(' ', '_', $column));
    }

    public static function stringToKebabCase(string $column): string
    {
        return Str::lower(str_replace(' ', '-', $column));
    }

    public static function addStartTime(string $date): string
    {
        return $date . ' 00:00:00';
    }

    public static function addEndTime(string $date): string
    {
        return $date . ' 23:59:59';
    }

    public static function checkMobileNumber(string $mobileNumber): bool
    {
        if (! config('app.validate_mobile_number')) {
            return true;
        }

        return (bool) preg_match(config('app.mobile_number_regex'), $mobileNumber);
    }

    public static function stringTitleLowerCase(string $name): string
    {
        return Str::title(str_replace('_', ' ', $name));
    }

    public static function currencySymbolDisplayWithAmount(
        string $currencySymbol,
        string|float $amount,
        bool $isAmountNegative = false,
        int $toDigit = 2,
        bool $withCurrencyFormat = false,
    ): string {
        if ($amount < 0) {
            $amount = str_replace('-', '', (string) $amount);
            $isAmountNegative = true;
        }

        if ($isAmountNegative) {
            return '-' . $currencySymbol . self::currencyFormat((float) $amount, $toDigit);
        }

        if ($withCurrencyFormat) {
            return $currencySymbol . self::currencyFormat((float) $amount, $toDigit);
        }

        return $currencySymbol . $amount;
    }

    public static function displayAmountWithPercentageSymbol(
        string|float $amount,
        bool $isAmountNegative = false
    ): string {
        if ($amount < 0) {
            $amount = str_replace('-', '', (string) $amount);
            $isAmountNegative = true;
        }

        if ($isAmountNegative) {
            return '-' . self::numberFormat((float) $amount) . '%';
        }

        return self::numberFormat((float) $amount) . '%';
    }

    public static function addMismatchOrAbort(Collection $saleMismatches, string $mismatchMessage): void
    {
        if (config('app.env') === 'production') {
            $saleMismatches->push($mismatchMessage);

            return;
        }

        abort(412, $mismatchMessage);
    }

    public static function generateFilteredCacheKeyWithExpiration(
        array $filterData,
        string $functionName,
        int $companyId
    ): array {
        $kebabFunctionName = Str::kebab($functionName);

        $string = self::flattenToString($filterData);

        return [$kebabFunctionName . '-' . $string . '-' . $companyId, now()->addMinutes(20)];
    }

    public static function flattenToString(array $data): string
    {
        $string = '';

        foreach ($data as $value) {
            if (is_array($value)) {
                $string .= self::flattenToString($value); // recursive call
            } elseif ('' !== $value && null !== $value) {
                $string .= (string) $value;
            }
        }

        return $string;
    }

    public static function stringToCamelCase(string $column): string
    {
        return Str::camel(Str::lower($column));
    }

    public static function getFilterLabels(array $filterData, int $companyId): array
    {
        $filters = [];

        if (null !== $filterData['product_id']) {
            $productQueries = resolve(ProductQueries::class);
            $product = $productQueries->getProductByIdAndCompanyId((int) $filterData['product_id'], $companyId);
            $filters['Product'] = $product->name;
        }

        if (null !== $filterData['product_collection_id']) {
            $productCollectionQueries = resolve(ProductCollectionQueries::class);
            $productCollection = $productCollectionQueries->getProductCollectionByIdAndCompanyId(
                (int) $filterData['product_collection_id'],
                $companyId
            );
            $filters['Product Collection'] = $productCollection->name;
        }

        if (null !== $filterData['category_id']) {
            $categoryQueries = resolve(CategoryQueries::class);
            $category = $categoryQueries->getCategoryByIdAndCompanyId((int) $filterData['category_id'], $companyId);
            $filters['Category'] = $category->name;
        }

        if (null !== $filterData['brand_id']) {
            $brandQueries = resolve(BrandQueries::class);
            $brand = $brandQueries->getById((int) $filterData['brand_id']);
            $filters['Brand'] = $brand->name;
        }

        if (null !== $filterData['size_id']) {
            $sizeQueries = resolve(SizeQueries::class);
            $size = $sizeQueries->getById((int) $filterData['size_id'], $companyId);
            $filters['Size'] = $size->name;
        }

        if (null !== $filterData['color_ids']) {
            $colorQueries = resolve(ColorQueries::class);
            $colors = $colorQueries->getColorNamesByIds($companyId, $filterData['color_ids']);
            if ($colors instanceof Color) {
                $filters['Colors'] = $colors['names'];
            }
        }

        if ([] !== $filterData['department_ids']) {
            $departmentQueries = resolve(DepartmentQueries::class);
            $departments = $departmentQueries->getDepartmentNamesByIds($companyId, $filterData['department_ids']);
            if ($departments instanceof Department) {
                $filters['Departments'] = $departments['names'];
            }
        }

        if ([] !== $filterData['tag_ids']) {
            $tagQueries = resolve(TagQueries::class);
            $tags = $tagQueries->getTagNamesByIds($companyId, $filterData['tag_ids']);
            if ($tags instanceof Tag) {
                $filters['Tags'] = $tags['names'];
            }
        }

        if ([] !== $filterData['article_numbers']) {
            $filters['Article Numbers'] = implode(', ', $filterData['article_numbers']);
        }

        if ([] !== $filterData['style_ids']) {
            $styleQueries = resolve(StyleQueries::class);
            $styles = $styleQueries->getStyleNamesByIds($companyId, $filterData['style_ids']);
            if ($styles instanceof Style) {
                $filters['Styles'] = $styles['names'];
            }
        }

        $inclusions = [];

        if (array_key_exists('include_by', $filterData) && [] !== $filterData['include_by']) {
            $locationQueries = resolve(LocationQueries::class);
            $sellThroughIncludeTypes = SellThroughIncludeTypes::getListByIds($filterData['include_by']);
            foreach ($sellThroughIncludeTypes as $type) {
                $inclusions[$type['name']] = '';

                if ($type['id'] === SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value && [] !== $filterData['includes_by_goods_receive_note_in_location_ids'] && ! empty($filterData['includes_by_goods_receive_note_in_location_ids'])) {
                    $location = $locationQueries->getLocationNamesWithCodesByIds(
                        $companyId,
                        $filterData['includes_by_goods_receive_note_in_location_ids']
                    );
                    if ($location instanceof Location) {
                        $inclusions[$type['name']] = '('.$location['getNamesWithCodes'].')';
                    }
                }

                if ($type['id'] === SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value && [] !== $filterData['includes_by_goods_receive_note_out_location_ids'] && ! empty($filterData['includes_by_goods_receive_note_out_location_ids'])) {
                    $location = $locationQueries->getLocationNamesWithCodesByIds(
                        $companyId,
                        $filterData['includes_by_goods_receive_note_out_location_ids']
                    );
                    if ($location instanceof Location) {
                        $inclusions[$type['name']] = '(' . $location['getNamesWithCodes'] . ')';
                    }
                }

                if ($type['id'] === SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value && [] !== $filterData['includes_by_stock_adjustment_in_location_ids'] && ! empty($filterData['includes_by_stock_adjustment_in_location_ids'])) {
                    $location = $locationQueries->getLocationNamesWithCodesByIds(
                        $companyId,
                        $filterData['includes_by_stock_adjustment_in_location_ids']
                    );
                    if ($location instanceof Location) {
                        $inclusions[$type['name']] = '('.$location['getNamesWithCodes'].')';
                    }
                }

                if ($type['id'] === SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value && [] !== $filterData['includes_by_stock_adjustment_out_location_ids'] && ! empty($filterData['includes_by_stock_adjustment_out_location_ids'])) {
                    $location = $locationQueries->getLocationNamesWithCodesByIds(
                        $companyId,
                        $filterData['includes_by_stock_adjustment_out_location_ids']
                    );
                    if ($location instanceof Location) {
                        $inclusions[$type['name']] = '('.$location['getNamesWithCodes'].')';
                    }
                }

                if ($type['id'] === SellThroughIncludeTypes::STOCK_TRANSFER_IN->value && [] !== $filterData['includes_by_stock_transfer_in_location_ids'] && ! empty($filterData['includes_by_stock_transfer_in_location_ids'])) {
                    $location = $locationQueries->getLocationNamesWithCodesByIds(
                        $companyId,
                        $filterData['includes_by_stock_transfer_in_location_ids']
                    );
                    if ($location instanceof Location) {
                        $inclusions[$type['name']] = '('.$location['getNamesWithCodes'].')';
                    }
                }

                if ($type['id'] === SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value && [] !== $filterData['includes_by_stock_transfer_out_location_ids'] && ! empty($filterData['includes_by_stock_transfer_out_location_ids'])) {
                    $location = $locationQueries->getLocationNamesWithCodesByIds(
                        $companyId,
                        $filterData['includes_by_stock_transfer_out_location_ids']
                    );
                    if ($location instanceof Location) {
                        $inclusions[$type['name']] = '('.$location['getNamesWithCodes'].')';
                    }
                }

                if ($type['id'] === SellThroughIncludeTypes::DELIVERY_ORDER_IN->value && [] !== $filterData['includes_by_delivery_order_in_location_ids'] && ! empty($filterData['includes_by_delivery_order_in_location_ids'])) {
                    $location = $locationQueries->getLocationNamesWithCodesByIds(
                        $companyId,
                        $filterData['includes_by_delivery_order_in_location_ids']
                    );
                    if ($location instanceof Location) {
                        $inclusions[$type['name']] = '('.$location['getNamesWithCodes'].')';
                    }
                }

                if ($type['id'] !== SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value) {
                    continue;
                }

                if ([] === $filterData['includes_by_delivery_order_out_location_ids']) {
                    continue;
                }

                if (! empty($filterData['includes_by_delivery_order_out_location_ids'])) {
                    $location = $locationQueries->getLocationNamesWithCodesByIds(
                        $companyId,
                        $filterData['includes_by_delivery_order_out_location_ids']
                    );

                    if ($location instanceof Location) {
                        $inclusions[$type['name']] = '('.$location['getNamesWithCodes'].')';
                    }
                }
            }
        }

        if ([] !== $inclusions) {
            $formattedInclusions = [];

            foreach ($inclusions as $key => $value) {
                $formattedInclusions[] = '' === $value ? $key : $key . ' ' . $value;
            }

            $string = implode(', ', $formattedInclusions);

            $filters['Inclusions For Stock Received'] = $string;
        }

        if ($filterData['filter_by'] > 0) {
            $sellThroughFilterTypes = SellThroughFilterTypes::getFormattedCaseName($filterData['filter_by']);

            $filters['Filter By'] = $sellThroughFilterTypes;
        }

        return $filters;
    }

    public static function dateFormat(string $date, string $format): string
    {
        /** @var Carbon $carbon */
        $carbon = Carbon::createFromFormat('Y-m-d', $date);

        return $carbon->format($format);
    }

    public static function checkIfCounterIsOpen(Cashier $cashier): void
    {
        abort_unless((bool) $cashier->getCounterUpdateId(), 412, 'The counter has not been opened yet.');
    }

    public static function logErrorDetails(Throwable $throwable, string $title): void
    {
        Log::error($title, [
            'error_message' => 'Error message: ' . $throwable->getMessage(),
            'error_code' => 'Error code: ' . $throwable->getCode(),
            'file' => 'File: ' . $throwable->getFile(),
            'line' => 'Line: ' . $throwable->getLine(),
            'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            'Full error' => [$throwable],
        ]);
    }

    public static function logChannelErrorDetails(
        Throwable $throwable,
        string $channelName,
        string $title,
        array $additionalParameters = []
    ): void {
        Log::channel($channelName)->error($title, [
            'error_message' => 'Error message: ' . $throwable->getMessage(),
            'error_code' => 'Error code: ' . $throwable->getCode(),
            'file' => 'File: ' . $throwable->getFile(),
            'line' => 'Line: ' . $throwable->getLine(),
            'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            'Full error' => [$throwable],
            ...$additionalParameters,
        ]);
    }

    public static function getMaxWorkersByCores(): int
    {
        // Try to get CPU cores using safer methods first
        $totalCores = self::getCpuCoreCount();

        // Modern CPU theoretically support the Hyper Threading
        $totalWorkers = $totalCores * 2;

        // Calculate 60% of cores for max processes
        return intval(ceil($totalWorkers * 0.60));
    }

    /**
     * Get CPU core count using multiple fallback methods for security and reliability
     */
    private static function getCpuCoreCount(): int
    {
        // Method 1: Try to read from /proc/cpuinfo (Linux only, but safer than shell_exec)
        if (is_readable('/proc/cpuinfo')) {
            $cpuInfo = file_get_contents('/proc/cpuinfo');
            if ($cpuInfo !== false) {
                $coreCount = substr_count($cpuInfo, 'processor');
                if ($coreCount > 0) {
                    return $coreCount;
                }
            }
        }

        // Method 2: Try shell_exec with proper validation and error handling
        if (function_exists('shell_exec')) {
            $output = shell_exec('nproc 2>/dev/null');
            if ($output !== null && $output !== false) {
                $output = trim($output);
                // Validate that output is a positive integer
                if (ctype_digit($output) && (int)$output > 0) {
                    return (int)$output;
                }
            }
        }

        // Method 3: Try alternative shell commands with validation
        if (function_exists('shell_exec')) {
            // Try alternative commands for different systems
            $commands = [
                'grep -c ^processor /proc/cpuinfo 2>/dev/null',
                'sysctl -n hw.ncpu 2>/dev/null', // macOS
                'wmic cpu get NumberOfCores /value 2>/dev/null | grep NumberOfCores | cut -d= -f2', // Windows
            ];

            foreach ($commands as $command) {
                $output = shell_exec($command);
                if ($output !== null && $output !== false) {
                    $output = trim($output);
                    if (ctype_digit($output) && (int)$output > 0) {
                        return (int)$output;
                    }
                }
            }
        }

        // Method 4: Use PHP's built-in function if available (PHP 8.1+)
        if (function_exists('hrtime') && defined('PHP_OS_FAMILY')) {
            // This is a basic fallback - in real scenarios you might want to use
            // more sophisticated detection methods
        }

        // Fallback: Return a reasonable default based on common server configurations
        // This ensures the function never returns 0 which could cause issues
        return 4; // Conservative default for most modern servers
    }

    public static function forgotAllSession(): void
    {
        session()->forget('url.intended');

        session()->forget([
            'super_admin_two_factor_authenticated',
            'admin_two_factor_authenticated',
            'admin_company_id',
            'store_manager_two_factor_authenticated',
            'store_manager_selected_location_id',
            'store_manager_selected_location_company_id',
            'warehouse_manager_two_factor_authenticated',
            'warehouse_manager_selected_location_id',
            'warehouse_manager_selected_location_company_id',
        ]);
    }

    public static function getCityStateCountryNames(int $cityId, int $stateId, int $countryId): array
    {
        $cityQueries = resolve(CityQueries::class);
        $stateQueries = resolve(StateQueries::class);
        $countryQueries = resolve(CountryQueries::class);

        $cityName = $cityQueries->getCityNameById($cityId);
        $stateName = $stateQueries->getStateNameById($stateId);
        $countryName = $countryQueries->getCountryNameById($countryId);

        return [$cityName, $stateName, $countryName];
    }

    public static function printNestedAttributes(array $attributes): string
    {
        if (isset($attributes['attributeString'])) {
            return self::formatAttributeRow($attributes);
        }

        foreach ($attributes as $attribute) {
            if (isset($attribute['attributeString'])) {
                return self::formatAttributeRow($attribute);
            }

            self::printNestedAttributes($attribute);
        }

        return '';
    }

    private static function formatAttributeRow(array $attribute): string
    {
        return
            '<td class="border-top-none text-left">' . $attribute['product_no'] . '</td>' .
            '<td class="border-top-none text-left">' . $attribute['attributeString'] . '</td>' .
            '<td class="border-top-none text-center">' . $attribute['qty'] . '</td>';
    }

    public static function getExportColumnsArray(Collection $exportColumns): array
    {
        return $exportColumns->map(fn ($columns): array => [
            [
                'label' => $columns['label'] ?? Str::title(str_replace('_', ' ', $columns['key'])),
                'key' => $columns['key'],
                'bodyClass' => $columns['bodyClass'] ?? 'text-left',
            ],
        ])->collapse()->toArray();
    }
}
