<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\MasterProduct;

use App\Domains\MasterProduct\MasterProductQueries;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class MasterProductController extends Controller
{
    public function __construct(
        protected MasterProductQueries $masterProductQueries
    ) {
    }

    public function getMasterProductArticleNumbers(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $articleNumbers = $this->masterProductQueries->getMasterProductsArticleNumberForEcommerce(
            $saleChannel->getCompanyId()
        );

        return [
            'article_numbers' => $articleNumbers->pluck('article_number'),
        ];
    }
}
