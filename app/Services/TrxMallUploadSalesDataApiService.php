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
use Illuminate\Support\Str;
use RetailCosmos\TrxMallUploadSalesDataApi\Contracts\TrxSalesService;
use RetailCosmos\TrxMallUploadSalesDataApi\Enums\PaymentType;

class TrxMallUploadSalesDataApiService implements TrxSalesService
{
    /**
     * @return array<int,mixed>
     */
    public function getStores(?string $storeIdentifier = null): array
    {
        $storeIdentifierData = collect([]);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);

        if (null !== $storeIdentifier) {
            return $this->retrieveStoreWithTRXMallConfiguration($storeIdentifier);
        }

        $companies = $companyQueries->getWithIdNameAndTRXMall()->toArray();

        foreach ($companies as $company) {
            $locations = $locationQueries->getStoresWhereAllowTRXMallDataSharingIsTrue($company['id']);
            foreach ($locations as $location) {
                $storeIdentifierData->push($this->prepareStoreIdentifierDetails($location));
            }
        }

        return $storeIdentifierData->toArray();
    }

    /**
     * @return Collection<int,mixed>
     */
    public function getSales(string $date, string $storeIdentifier): Collection
    {
        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getIdByNameForTRXMall($storeIdentifier);
        if (null === $location) {
            return collect([]);
        }

        $saleRecords = $saleQueries->getSalesDataCollectionForTheTRXMall((int) $location->id, $date);
        $saleReturnRecords = $saleReturnQueries->getSaleReturnsDataCollectionForTheTRXMall((int) $location->id, $date);

        $saleCollections = $this->prepareSaleRecords($saleRecords);
        $saleReturnCollections = $this->prepareSaleReturnRecords($saleReturnRecords);

        return $saleCollections->merge($saleReturnCollections);
    }

    private function prepareSaleRecords(Collection $records): Collection
    {
        $data = [];
        foreach ($records as $record) {
            $data[] = [
                'happened_at' => $record->happened_at,
                'net_amount' => (float) $record->total_amount_paid,
                'gst' => (float) $record->total_tax_amount,
                'discount' => (float) $record->total_discount_amount,
                'payments' => [
                    PaymentType::CASH() => $record->payments->where(
                        'paymentType.name',
                        $this->capitalizeName(PaymentType::CASH())
                    )->sum('amount'),
                    PaymentType::TNG() => $record->payments->where(
                        'paymentType.name',
                        $this->capitalizeName(PaymentType::TNG())
                    )->sum('amount'),
                    PaymentType::VISA() => $record->payments->where(
                        'paymentType.name',
                        $this->capitalizeName(PaymentType::VISA())
                    )->sum('amount'),
                    PaymentType::MASTERCARD() => $record->payments->where(
                        'paymentType.name',
                        $this->capitalizeName(PaymentType::MASTERCARD())
                    )->sum('amount'),
                    PaymentType::AMEX() => $record->payments->where(
                        'paymentType.name',
                        $this->capitalizeName(PaymentType::AMEX())
                    )->sum('amount'),
                    PaymentType::VOUCHER() => $record->payments->where(
                        'paymentType.name',
                        $this->capitalizeName(PaymentType::VOUCHER())
                    )->sum('amount'),
                    PaymentType::OTHERS() => $record->payments->whereNotIn('paymentType.name', [
                        $this->capitalizeName(PaymentType::CASH()),
                        $this->capitalizeName(PaymentType::TNG()),
                        $this->capitalizeName(PaymentType::VISA()),
                        $this->capitalizeName(PaymentType::MASTERCARD()),
                        $this->capitalizeName(PaymentType::AMEX()),
                        $this->capitalizeName(PaymentType::VOUCHER()),
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
                'gst' => -$record->total_tax_amount,
                'payments' => [
                    PaymentType::CASH() => 0,
                    PaymentType::TNG() => 0,
                    PaymentType::VISA() => 0,
                    PaymentType::MASTERCARD() => 0,
                    PaymentType::AMEX() => 0,
                    PaymentType::VOUCHER() => 0,
                    PaymentType::OTHERS() => -$record->total_price_paid,
                ],
            ];
        }

        return collect($data);
    }

    private function capitalizeName(string $paymentName): string
    {
        return Str::of($paymentName)->ucfirst()->value();
    }

    private function retrieveStoreWithTRXMallConfiguration(string $storeIdentifier): array
    {
        $storeIdentifierData = collect([]);
        $locationQueries = resolve(LocationQueries::class);

        $location = $locationQueries->getDetailsByNameForTRXMall($storeIdentifier);

        if (null === $location) {
            return $storeIdentifierData->toArray();
        }

        /** @var Company $company */
        $company = $location->company;

        if (! $company->enable_trx_mall_integration) {
            return $storeIdentifierData->toArray();
        }

        $storeIdentifierData->push($this->prepareStoreIdentifierDetails($location));

        return $storeIdentifierData->toArray();
    }

    private function prepareStoreIdentifierDetails(Location $location): array
    {
        return [
            'machine_id' => $location->trx_mall_machine_id ?? null,
            'store_identifier' => $location->name ?? null,
            'gst_registered' => $location->sales_tax_percentage > 0.0,
        ];
    }
}
