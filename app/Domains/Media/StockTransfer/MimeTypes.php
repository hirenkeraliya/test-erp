<?php

declare(strict_types=1);

namespace App\Domains\Media\StockTransfer;

use App\Http\Traits\PrepareEnumDataMethods;

enum MimeTypes: string
{
    use PrepareEnumDataMethods;

    case VIDEO_MP4 = 'video/mp4';
    case VIDEO_MPEG = 'video/mpeg';
    case VIDEO_QUICK_TIME = 'video/quicktime';
    case IMAGE_JPEG = 'image/jpeg';
    case IMAGE_GIF = 'image/gif';
    case IMAGE_PNG = 'image/png';
}
