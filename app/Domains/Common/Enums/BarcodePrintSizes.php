<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum BarcodePrintSizes: string
{
    use PrepareEnumDataMethods;

    case PRINT_SIZE_ONE = '35x30mm (3 per row)';
    case PRINT_SIZE_TWO = '45x40mm (2 per row)';
    case PRINT_SIZE_THREE = '35x30mm (2 per row)';
    case PRINT_SIZE_FOUR = '45x40mm (1 per row)';
}
