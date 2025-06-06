<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum BulkProductMergeImportColumns: string
{
    use PrepareEnumDataMethods;

    case OLD_UPC = 'old_upc';
    case NEW_UPC = 'new_upc';
}
