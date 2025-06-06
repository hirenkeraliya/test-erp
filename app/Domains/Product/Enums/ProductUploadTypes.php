<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ProductUploadTypes: int
{
    use PrepareEnumDataMethods;

    case THUMBNAIL = 1;
    case IMAGES = 2;
    case VIDEOS = 3;
}
