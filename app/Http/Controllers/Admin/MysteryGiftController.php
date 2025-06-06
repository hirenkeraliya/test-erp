<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\MysteryGift\DataObjects\MysteryGiftData;
use App\Domains\MysteryGift\Exports\MysteryGiftProductDetailsExport;
use App\Domains\MysteryGift\MysteryGiftQueries;
use App\Domains\MysteryGift\Resources\AdminEditMysteryGiftResource;
use App\Domains\MysteryGift\Resources\AdminMysteryGiftListResource;
use App\Domains\MysteryGift\Services\MysteryGiftService;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class MysteryGiftController extends Controller
{
    public function __construct(
        protected MysteryGiftQueries $mysteryGiftQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('mystery_gifts/Index');
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchMysteryGifts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->mysteryGiftQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminMysteryGiftListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('mystery_gifts/Manage');
    }

    public function store(MysteryGiftData $mysteryGiftData, Request $request): RedirectResponse
    {
        /** @var Admin $user */
        $user = $request->user();

        $companyId = session('admin_company_id');

        DB::beginTransaction();

        try {
            $mysteryGift = $this->mysteryGiftQueries->addNew($mysteryGiftData, $companyId);
            $mysteryGiftService = resolve(MysteryGiftService::class);
            $mysteryGiftService->generateVoucherForMysteryGift($mysteryGift, $user);

            DB::commit();

            return to_route('admin.mystery_gifts.index')->with('success', 'Mystery Gift added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Promotion', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function edit(int $mysteryGiftId): Response
    {
        $companyId = session('admin_company_id');
        $mysteryGift = $this->mysteryGiftQueries->getById($mysteryGiftId, $companyId);

        return Inertia::render('mystery_gifts/Manage', [
            'mysteryGift' => new AdminEditMysteryGiftResource($mysteryGift),
        ]);
    }

    public function update(MysteryGiftData $mysteryGiftData, int $mysteryGiftId, Request $request): RedirectResponse
    {
        /** @var Admin $user */
        $user = $request->user();

        $companyId = session('admin_company_id');

        $mysteryGift = $this->mysteryGiftQueries->getById($mysteryGiftId, $companyId);

        if (false === $mysteryGift->status) {
            abort(417, 'This promotion is currently inactive.');
        }

        DB::beginTransaction();

        try {
            $mysteryGift = $this->mysteryGiftQueries->update($mysteryGiftData, $mysteryGift);

            $mysteryGiftService = resolve(MysteryGiftService::class);
            $mysteryGiftService->generateVoucherForMysteryGift($mysteryGift, $user);

            DB::commit();

            return to_route('admin.mystery_gifts.index')->with('success', 'Promotion has been successfully updated.');
        } catch (Throwable $throwable) {
            Log::error('Update Promotion', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again');
        }
    }

    public function setStatus(int $mysteryGIftId, bool $status): RedirectResponse
    {
        $this->mysteryGiftQueries->setStatus($mysteryGIftId, session('admin_company_id'), $status);

        return to_route('admin.mystery_gifts.index')->with('success', 'Status changed successfully.');
    }

    public function removeSelectedProducts(Request $request): void
    {
        $validatedData = $request->validate([
            'id' => ['required', 'exists:mystery_gifts,id'],
        ]);

        $this->mysteryGiftQueries->removeSelectedProducts($validatedData);
    }

    public function exportMysteryGiftsProductsDetails(int $id, string $filename): BinaryFileResponse
    {
        $mysteryGift = $this->mysteryGiftQueries->fetchPromotionProducts($id);

        return Excel::download(new MysteryGiftProductDetailsExport($mysteryGift), $filename);
    }

    public function generateQrCode(): ?HtmlString
    {
        $qrCode = QrCode::style('round')->format('png')->size(2000)->margin(10)->generate(
            route('front.mystery_gift.index')
        );

        return $qrCode instanceof HtmlString ? $qrCode : null;
    }
}
