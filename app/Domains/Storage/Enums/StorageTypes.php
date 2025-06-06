<?php

declare(strict_types=1);

namespace App\Domains\Storage\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StorageTypes: string
{
    use PrepareEnumDataMethods;

    case OCI = 'oci';
    case LOCAL = 'local';
    case PUBLIC = 'public';
}
