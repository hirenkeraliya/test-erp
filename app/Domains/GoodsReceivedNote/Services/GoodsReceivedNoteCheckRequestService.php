<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Services;

use App\Domains\Batch\BatchQueries;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\Batch;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GoodsReceivedNoteCheckRequestService
{
    public function validateGrnReference(string $grnReferenceNumber, int $companyId): void
    {
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);
        $grnReferenceExist = $goodsReceivedNoteQueries->grnReferenceExists($grnReferenceNumber, $companyId);

        if ($grnReferenceExist) {
            throw new RedirectBackWithErrorException('GRN reference already exists.');
        }
    }

    public function validateProducts(
        Collection $uploadedProducts,
        Collection $actualProducts,
        int $companyId,
        Collection $derivatives
    ): void {
        $batchQueries = resolve(BatchQueries::class);
        $batchNumbers = array_unique(array_filter($uploadedProducts->pluck('batch_number')->toArray()));
        $batches = $batchQueries->getByNumbers($batchNumbers, $companyId);

        foreach ($uploadedProducts as $uploadedProduct) {
            $matchProduct = $actualProducts->firstWhere('upc', $uploadedProduct['upc']);

            if (! $matchProduct->unit_of_measure_id && $this->isDerivativeNameAttached($uploadedProduct)) {
                throw new RedirectBackWithErrorException(
                    'Derivate name is not required due to unit of measure does not set for the product with UPC ' . $uploadedProduct['upc'] . '.'
                );
            }

            if ($matchProduct->unit_of_measure_id && $this->isDerivativeNameAttached($uploadedProduct)) {
                $derivative = $derivatives->firstWhere('name', $uploadedProduct['derivative_name']);

                if (! $derivative) {
                    throw new RedirectBackWithErrorException(
                        'Derivate name `' . $uploadedProduct['derivative_name'] . '` does not exists in our records for the product with UPC ' . $uploadedProduct['upc'] . '.'
                    );
                }

                if ($matchProduct->unit_of_measure_id !== $derivative->unit_of_measure_id) {
                    throw new RedirectBackWithErrorException(
                        'Derivate name `' . $uploadedProduct['derivative_name'] . '` have UOM `' . $derivative->unitOfMeasure->name . '` does not match with the product UPC ' . $uploadedProduct['upc'] . ' have UOM `' . $matchProduct->unitOfMeasure->name
                    );
                }
            }

            if (! $matchProduct->has_batch && (! array_key_exists(
                'batch_number',
                $uploadedProduct
            ) || ! array_key_exists(
                'batch_expiry_date',
                $uploadedProduct
            ) || $uploadedProduct['batch_number'] || $uploadedProduct['batch_expiry_date'])) {
                throw new RedirectBackWithErrorException(
                    'Batch number is not required for the product with UPC ' . $uploadedProduct['upc'] . '.'
                );
            }

            if (! $matchProduct->has_batch) {
                continue;
            }

            if (! array_key_exists('batch_number', $uploadedProduct) || ! $uploadedProduct['batch_number']) {
                throw new RedirectBackWithErrorException(
                    'Batch number is required for the product with UPC ' . $uploadedProduct['upc'] . '.'
                );
            }

            if (! array_key_exists('batch_expiry_date', $uploadedProduct) || ! $uploadedProduct['batch_expiry_date']) {
                throw new RedirectBackWithErrorException(
                    'Batch expiry date is required for the product with UPC ' . $uploadedProduct['upc'] . '.'
                );
            }

            if ($uploadedProduct['batch_expiry_date'] < Carbon::now()->format('Y-m-d')) {
                throw new RedirectBackWithErrorException(
                    'Batch expiry date must be a date in the future. But the specified date is ' . $uploadedProduct['batch_expiry_date'] . '.'
                );
            }

            /** @var Batch $batch */
            $batch = $batches->firstWhere('number', $uploadedProduct['batch_number']);

            if (! $batch instanceof Batch) {
                continue;
            }

            if ($batch->product_id !== $matchProduct->id) {
                throw new RedirectBackWithErrorException(
                    'Batch number of the product with UPC: ' . $uploadedProduct['upc'] . ' is already used for another product.'
                );
            }

            if ($batch->expiry_date !== $uploadedProduct['batch_expiry_date']) {
                throw new RedirectBackWithErrorException(
                    'The provided expiry date' . $uploadedProduct['batch_expiry_date'] . ' does not match with the current expiry date of the batch with number: ' . $batch->number
                );
            }
        }
    }

    private function isDerivativeNameAttached(array $uploadedProduct): bool
    {
        return array_key_exists('derivative_name', $uploadedProduct) && $uploadedProduct['derivative_name'];
    }
}
