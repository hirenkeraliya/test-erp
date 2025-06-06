<?php

declare(strict_types=1);

namespace App\Domains\Banner;

use App\Domains\Banner\DataObjects\BannerData;
use App\Domains\Banner\Enums\ActionTypes;
use App\Domains\Media\MediaQueries;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\Data;
use Throwable;

class BannerQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getBanners($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(BannerData $bannerData, int $companyId): void
    {
        DB::beginTransaction();
        try {
            $data = $bannerData->all();

            $data['company_id'] = $companyId;

            unset($data['image']);
            $banner = Banner::create($data);
            $this->uploadImage($banner, $bannerData);

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error([
                'error_name' => 'Banner create error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }

    public function getById(int $bannerId, int $companyId): Banner
    {
        $mediaQueries = resolve(MediaQueries::class);

        return Banner::select('id', 'company_id', 'name', 'description', 'action_type_id', 'custom_url', 'status')
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->findOrFail($bannerId);
    }

    public function getFirstForEcommerceSync(int $companyId, int $saleChannelId): ?Banner
    {
        return Banner::select('id')
            ->whereDoesntHave('bannerChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('company_id', $companyId)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getLastForEcommerceSync(int $companyId, int $saleChannelId): ?Banner
    {
        return Banner::select('id')
            ->whereDoesntHave('bannerChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getBannerEcommerceChannelByStartAndEndId(
        int $companyId,
        int $startId,
        int $endId,
        int $saleChannelId
    ): Collection {
        $mediaQueries = resolve(MediaQueries::class);

        return Banner::select(
            'id',
            'company_id',
            'updated_at',
            'created_at',
            'name',
            'description',
            'custom_url',
            'status',
            'action_type_id'
        )
        ->with('media:' . $mediaQueries->getBasicColumnNames())
        ->whereDoesntHave('bannerChannelReferences', function ($query) use ($saleChannelId): void {
            $query->where('sale_channel_id', $saleChannelId);
        })
            ->where('company_id', $companyId)
            ->where('id', '>=', $startId)
            ->where('id', '<=', $endId)
            ->get();
    }

    public function update(BannerData $bannerData, int $bannerId, int $companyId): void
    {
        $banner = $this->getById($bannerId, $companyId);
        $bannerDetails = $bannerData->all();

        $bannerDetails['custom_url'] = $bannerData->action_type_id === ActionTypes::CUSTOM_URL->value ? $bannerData->custom_url :
            null;
        unset($bannerDetails['image']);
        $banner->update($bannerDetails);
        $this->uploadImage($banner, $bannerData);
        $this->setUpdatedAt($banner);
    }

    public function setUpdatedAt(Banner $banner): void
    {
        $banner->touch();
    }

    public function getListInEcommerce(array $filterData, int $companyId): Collection
    {
        $mediaQueries = resolve(MediaQueries::class);

        return Banner::query()
            ->select('id', 'name', 'description', 'action_type_id', 'custom_url', 'status', 'created_at', 'updated_at')
            ->where('company_id', $companyId)
            ->where('status', true)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->whereAny(['name', 'description'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->get();
    }

    private function getBanners(array $filterData, int $companyId): Builder
    {
        $mediaQueries = resolve(MediaQueries::class);

        return Banner::query()
            ->select('id', 'name', 'description', 'action_type_id', 'custom_url', 'status', 'created_at', 'updated_at')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'description'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function updateStatus(int $bannerId, int $companyId, bool $status): void
    {
        $banner = Banner::query()
            ->select('id', 'status')
            ->where('company_id', $companyId)
            ->findOrFail($bannerId);

        $banner->status = $status;
        $banner->save();
    }

    private function uploadImage(Banner $banner, Data $bannerData): void
    {
        $data = $bannerData->all();

        if ($data['image'] instanceof UploadedFile) {
            $banner->addMedia($data['image'])->toMediaCollection('banner');
        }
    }

    public function refresh(Banner $banner): Banner
    {
        return $banner->refresh();
    }
}
