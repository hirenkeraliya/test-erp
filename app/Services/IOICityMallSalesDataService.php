<?php

declare(strict_types=1);

namespace App\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Support\Collection;

class IOICityMallSalesDataService
{
    public function storesList(?string $storeIdentifier = null): Collection
    {
        $storeIdentifierData = collect([]);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);

        if (null !== $storeIdentifier) {
            return $this->retrieveStoreWithIOICityMallConfiguration($storeIdentifier);
        }

        $companies = $companyQueries->getWithIdNameAndIOICityMall()->toArray();

        foreach ($companies as $company) {
            $locations = $locationQueries->getStoresWhereAllowIOICityMallDataSharingIsTrue($company['id']);
            foreach ($locations as $location) {
                $storeIdentifierData->push($this->prepareStoreIdentifierDetails($location));
            }
        }

        return $storeIdentifierData;
    }

    public function salesData(string $storeIdentifier, string $date): Collection
    {
        $saleQueries = resolve(SaleQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $location = $locationQueries->getIdByNameForIOICityMall($storeIdentifier);
        if (null === $location) {
            return collect([]);
        }

        $saleRecords = $saleQueries->getSalesDataCollectionForTheIOICityMall((int) $location->id, $date);
        $saleReturnRecords = $saleReturnQueries->getSaleReturnsDataCollectionForTheIOICityMall(
            (int) $location->id,
            $date
        );

        $salesCollections = $this->prepareSaleRecords($saleRecords);
        $saleReturnCollections = $this->prepareSaleReturnRecords($saleReturnRecords);

        return $salesCollections->merge($saleReturnCollections);
    }

    private function prepareSaleRecords(Collection $records): Collection
    {
        $data = [];
        foreach ($records as $record) {
            $data[] = [
                'happened_at' => $record->happened_at,
                'net_amount' => $record->total_amount_paid,
                'discount' => $record->total_discount_amount,
                'SST' => $record->total_tax_amount,
                'payments' => [
                    'cash' => $record->payments->where('paymentType.name', 'Cash')->sum('amount'),
                    'tng' => $record->payments->where('paymentType.name', 'Tng')->sum('amount'),
                    'visa' => $record->payments->where('paymentType.name', 'Visa')->sum('amount'),
                    'mastercard' => $record->payments->where('paymentType.name', 'Mastercard')->sum('amount'),
                    'amex' => $record->payments->where('paymentType.name', 'Amex')->sum('amount'),
                    'voucher' => $record->payments->where('paymentType.name', 'Voucher')->sum('amount'),
                    'others' => $record->payments->whereNotIn('paymentType.name', [
                        'Cash',
                        'Tng',
                        'Visa',
                        'Mastercard',
                        'Amex',
                        'Voucher',
                    ])->sum('amount'),
                ],
            ];
        }

        return collect($data);
    }

    private function prepareSaleReturnRecords(Collection $records): Collection
    {
        $data = [];
        foreach ($records as $record) {
            $data[] = [
                'happened_at' => $record->happened_at,
                'net_amount' => -$record->total_price_paid,
                'discount' => -$record->total_discount_amount,
                'SST' => -$record->total_tax_amount,
                'payments' => [
                    'cash' => 0,
                    'tng' => 0,
                    'visa' => 0,
                    'mastercard' => 0,
                    'amex' => 0,
                    'voucher' => 0,
                    'others' => -$record->total_price_paid,
                ],
            ];
        }

        return collect($data);
    }

    private function prepareStoreIdentifierDetails(Location $location): array
    {
        return [
            'store_identifier' => $location->name ?? null,
            'machine_id' => $location->ioi_city_mall_machine_id ?? null,
            'sst_registered' => $location->sales_tax_percentage > 0.0,
        ];
    }

    private function retrieveStoreWithIOICityMallConfiguration(string $storeIdentifier): Collection
    {
        $storeIdentifierData = collect([]);
        $locationQueries = resolve(LocationQueries::class);

        $location = $locationQueries->getDetailsByNameForIOICityMall($storeIdentifier);

        if (null === $location) {
            return $storeIdentifierData;
        }

        /** @var Company $company */
        $company = $location->company;

        if (! $company->enable_ioi_city_mall_integration) {
            return $storeIdentifierData;
        }

        $storeIdentifierData->push($this->prepareStoreIdentifierDetails($location));

        return $storeIdentifierData;
    }
}
