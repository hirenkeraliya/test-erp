<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\GiftCard\DataObjects\GiftCardData;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Domains\GiftCard\Exports\GiftCardExport;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCard\Resources\GiftCardListResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class GiftCardController extends Controller
{
    public function __construct(
        protected GiftCardQueries $giftCardQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('gift_cards/Index', [
            'giftCardStatuses' => GiftCardStatuses::getList(),
            'giftCardTypes' => GiftCardTypes::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('gift_card'),
        ]);
    }

    public function uploadGiftCardView(): Response
    {
        return Inertia::render('gift_cards/Manage', [
            'giftCardTypes' => GiftCardTypes::getList(),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchGiftCard(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'status' => $request->get('status'),
            'type' => $request->get('type'),
            'created_date' => $request->get('created_date'),
            'expiry_date' => $request->get('expiry_date'),
        ];
        $lengthAwarePaginator = $this->giftCardQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => GiftCardListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function upload(GiftCardData $giftCardData): RedirectResponse
    {
        $giftCard = collect($giftCardData->gift_cards);

        $this->validateUploadedGiftCardsKeys($giftCard->first());

        $numbers = $giftCard->pluck('number')->toArray();

        if ($this->giftCardQueries->checkExistingNumbers($numbers, session('admin_company_id'))) {
            throw new RedirectBackWithErrorException('Some of the numbers already exist in our records.');
        }

        $giftCards = $giftCard->map(function (array $giftCard) use ($giftCardData): array {
            $giftCard['company_id'] = session('admin_company_id');
            $giftCard['type_id'] = $giftCardData->type_id;
            $giftCard['total_amount'] = $giftCard['amount'];
            $giftCard['available_amount'] = $giftCard['amount'];

            return $giftCard;
        });

        DB::beginTransaction();

        try {
            $this->giftCardQueries->createMany($giftCards->toArray());

            DB::commit();

            return to_route('admin.gift_cards.index')
                ->with('success', 'Gift cards added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Gift-Cards', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            throw new RedirectBackWithErrorException($throwable->getMessage());
        }
    }

    public function exportGiftCards(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'status' => $request->get('status'),
            'type' => $request->get('type'),
            'created_date' => $request->get('created_date'),
            'expiry_date' => $request->get('expiry_date'),
        ];

        $giftCards = $this->giftCardQueries->getGiftCardsForExport($filterData, session('admin_company_id'));

        return Excel::download(new GiftCardExport($giftCards), $filename);
    }

    private function validateUploadedGiftCardsKeys(array $uploadedProducts): void
    {
        $headerColumns = ['number', 'expiry_date', 'amount'];

        $missingKeys = array_diff($headerColumns, array_keys($uploadedProducts));
        if ([] !== $missingKeys) {
            $missingColumns = implode(', ', $missingKeys);
            $errorMessage = sprintf('The following columns are missing: %s', $missingColumns);
            throw new RedirectBackWithErrorException($errorMessage);
        }
    }
}
