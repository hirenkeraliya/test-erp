<?php

declare(strict_types=1);

namespace App\Domains\Attribute\Enums;

use App\Http\Traits\PrepareEnumDataMethods;
use BackedEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Json;

enum FieldType: int
{
    use PrepareEnumDataMethods;

    case TOGGLE = 1;

    case DECIMAL = 2;

    case NUMBER = 3;

    case TEXT = 4;

    case DATE = 5;

    case DATETIME = 6;

    case SELECT = 7;

    case MULTISELECT = 8;

    /**
     * @return array<int, BackedEnum>
     */
    public static function selections(): array
    {
        return [self::SELECT, self::MULTISELECT];
    }

    /**
     * @return array<int, BackedEnum>
     */
    public static function allowFromToFunctionalityFields(): array
    {
        return [self::DECIMAL, self::NUMBER, self::DATE, self::DATETIME];
    }

    public static function prepareValueByFieldType(self $type, ?string $value): bool|float|int|Carbon|array|string|null
    {
        return match ($type) {
            self::TOGGLE => null !== $value && (bool) $value,
            self::DECIMAL => null !== $value ? (float) $value : null,
            self::NUMBER => null !== $value ? (int) $value : null,
            self::DATE => null !== $value ? Carbon::parse($value)->format('Y-m-d') : null,
            self::DATETIME => null !== $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
            self::TEXT => $value,
            self::SELECT => $value,
            self::MULTISELECT => null !== $value ? Json::decode($value) : null,
        };
    }
}
