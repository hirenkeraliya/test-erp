<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Titles: int
{
    use PrepareEnumDataMethods;

    case DATIN = 1;
    case DATIN_SERI = 2;
    case DATO_SRI = 3;
    case DATUK = 4;
    case DR = 5;
    case DATO = 6;
    case MADAM = 7;
    case MR = 8;
    case MRS = 9;
    case MS = 10;
    case PUAN = 11;
    case TAN_SRI = 12;
    case PUAN_SRI = 13;
}
