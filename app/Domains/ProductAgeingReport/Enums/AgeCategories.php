<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum AgeCategories: int
{
    use PrepareEnumDataMethods;

    case ONE_MONTH = 1;
    case TWO_MONTH = 2;
    case THREE_MONTH = 3;
    case SIX_MONTH = 4;
    case ONE_YEAR = 5;
    case TWO_YEAR = 6;
    case THREE_YEAR = 7;
    case MORE_THAN_THREE_YEAR = 8;

    public static function getAgeCategories(int $ageCategory): string
    {
        if (self::ONE_MONTH->value === $ageCategory) {
            return self::getFormattedCaseName(self::ONE_MONTH->value) . ' (0-30 days)';
        }

        if (self::TWO_MONTH->value === $ageCategory) {
            return self::getFormattedCaseName(self::TWO_MONTH->value) . ' (31-60 days)';
        }

        if (self::THREE_MONTH->value === $ageCategory) {
            return self::getFormattedCaseName(self::THREE_MONTH->value) . ' (61-90 days)';
        }

        if (self::SIX_MONTH->value === $ageCategory) {
            return self::getFormattedCaseName(self::SIX_MONTH->value);
        }

        if (self::ONE_YEAR->value === $ageCategory) {
            return self::getFormattedCaseName(self::ONE_YEAR->value);
        }

        if (self::TWO_YEAR->value === $ageCategory) {
            return self::getFormattedCaseName(self::TWO_YEAR->value);
        }

        if (self::THREE_YEAR->value === $ageCategory) {
            return self::getFormattedCaseName(self::THREE_YEAR->value);
        }

        return self::getFormattedCaseName(self::MORE_THAN_THREE_YEAR->value);
    }

    public static function getAgeCategoriesByDays(int $ageOfTheProduct): string
    {
        if ($ageOfTheProduct <= 30) {
            return 'One Month (0-30 days)';
        }

        if ($ageOfTheProduct <= 60) {
            return 'Two Month (31-60 days)';
        }

        if ($ageOfTheProduct <= 90) {
            return 'Three Month (61-90 days)';
        }

        if ($ageOfTheProduct <= 180) {
            return 'Six Month';
        }

        if ($ageOfTheProduct <= 360) {
            return 'One Year';
        }

        if ($ageOfTheProduct <= 720) {
            return 'Two Year';
        }

        if ($ageOfTheProduct <= 1080) {
            return 'Three Year';
        }

        return 'More Than Three Year';
    }

    public static function getDays(int $ageCategory): array
    {
        if (self::ONE_MONTH->value === $ageCategory) {
            return [0, 30];
        }

        if (self::TWO_MONTH->value === $ageCategory) {
            return [31, 60];
        }

        if (self::THREE_MONTH->value === $ageCategory) {
            return [61, 90];
        }

        if (self::SIX_MONTH->value === $ageCategory) {
            return [91, 180];
        }

        if (self::ONE_YEAR->value === $ageCategory) {
            return [181, 360];
        }

        if (self::TWO_YEAR->value === $ageCategory) {
            return [361, 720];
        }

        if (self::THREE_YEAR->value === $ageCategory) {
            return [721, 1080];
        }

        return [1081, 0];
    }
}
