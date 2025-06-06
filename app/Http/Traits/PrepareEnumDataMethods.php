<?php

declare(strict_types=1);

namespace App\Http\Traits;

use App\CommonFunctions;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait PrepareEnumDataMethods
{
    /**
     * @return mixed[]
     */
    public static function formattedForSelection(bool $nameInTitleCase = true): array
    {
        return collect(self::cases())->map(fn ($type): array => [
            'id' => $type->value,
            'name' => $nameInTitleCase ? CommonFunctions::stringTitleLowerCase($type->name) : $type->name,
        ])->toArray();
    }

    /**
     * @return mixed[]
     */
    public static function getList(): array
    {
        return collect(self::cases())->map(fn ($type): array => [
            'id' => $type->value,
            'name' => CommonFunctions::stringTitleLowerCase($type->name),
            'key' => $type->name,
        ])->toArray();
    }

    public static function getListByIds(array $ids): array
    {
        return collect(self::cases())->map(fn ($type): array => [
            'id' => $type->value,
            'name' => CommonFunctions::stringTitleLowerCase($type->name),
        ])->whereIn('id', $ids)->toArray();
    }

    public static function getValues(): string
    {
        return collect(self::cases())->pluck('value')->implode(',');
    }

    public static function getOriginalNames(): string
    {
        return collect(self::cases())->pluck('name')->implode(',');
    }

    public static function getArrayValues(): array
    {
        return collect(self::cases())->pluck('value')->toArray();
    }

    public static function getNames(): string
    {
        return Str::lower(collect(self::cases())->pluck('name')->implode(','));
    }

    public static function getCasesValue(): Collection
    {
        return collect(self::cases())->pluck('value');
    }

    public static function getValueByCaseName(string $name): mixed
    {
        foreach (self::cases() as $type) {
            if ($type->name === $name) {
                return $type->value;
            }

            if (Str::upper(str_replace(' ', '_', $name)) === $type->name) {
                return $type->value;
            }
        }

        return null;
    }

    public static function getFormattedCaseName(int|string $key): string
    {
        /** @var self $case */
        $case = self::tryFrom($key);

        return CommonFunctions::stringTitleLowerCase($case->name);
    }

    public static function getCaseName(int|string $key): string
    {
        /** @var self $case */
        $case = self::tryFrom($key);

        return Str::lower($case->name);
    }

    public static function getCaseNameByValue(int|string $id): string
    {
        /** @var self $case */
        $case = self::tryFrom($id);

        return $case->name;
    }

    /**
     * @return mixed[]
     */
    public static function getMatchingCases(string $name): array
    {
        $cases = [];

        foreach (self::cases() as $case) {
            $caseName = str_replace('_', ' ', strtolower($case->name));

            if (Str::contains($caseName, strtolower($name))) {
                $cases[] = $case->value;
            }
        }

        return $cases;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getFormattedArrayForPos(int|string $id): array
    {
        /** @var self $case */
        $case = self::tryFrom($id);

        return [
            'id' => $id,
            'name' => CommonFunctions::stringTitleLowerCase($case->name),
            'key' => $case->name,
        ];
    }

    public static function getFormattedArrayForStaticUse(): array
    {
        return collect(self::cases())->map(fn ($type): array => [
            CommonFunctions::stringToCamelCase($type->name) => $type->value,
        ])->collapse()->toArray();
    }

    public static function generateStaticCasesArray(): array
    {
        return collect(self::cases())->map(fn ($type): array => [
            CommonFunctions::stringToCamelCase($type->name) => CommonFunctions::stringTitleLowerCase($type->name),
        ])->collapse()->toArray();
    }

    public static function generateStaticCasesWithLowerArray(): array
    {
        return collect(self::cases())->map(fn ($type): array => [
            CommonFunctions::stringToCamelCase($type->name) => CommonFunctions::stringLowerCase($type->name),
        ])->collapse()->toArray();
    }

    public static function getCaseNameWithValue(int $id): ?self
    {
        return collect(self::cases())->where('value', $id)->first();
    }

    public static function getCaseWithName(string $key): ?self
    {
        return collect(self::cases())->where('name', $key)->first();
    }

    public static function getTitleCaseNames(): string
    {
        return Str::title(collect(self::cases())->pluck('name')->implode(','));
    }
}
