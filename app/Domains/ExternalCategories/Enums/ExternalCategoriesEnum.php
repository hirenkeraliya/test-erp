<?php

declare(strict_types=1);

namespace App\Domains\ExternalCategories\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ExternalCategoriesEnum: string
{
    use PrepareEnumDataMethods;

    case GET_CATEGORY_LIST_URL = '/product/category/get_category_list';
}
