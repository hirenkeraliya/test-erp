<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Resources;

use App\Domains\Sale\Enums\CreditAndLayawaySaleStatuses;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

class PaymentTransactionListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $transaction = $this->resource;
        $currentRouteName = Route::currentRouteName();
        $url = null;

        $prefix = 'admin';
        if (null !== $currentRouteName && str_starts_with($currentRouteName, 'store_manager.')) {
            $prefix = 'store_manager';
        }

        if ('Sale Payment' === $transaction->payment_type) {
            $url = route($prefix . '.sales.index', [
                'offline_sale_id' => $transaction->receipt_id,
            ]);
        }

        if ('Void Sale Payment' === $transaction->payment_type) {
            $url = route($prefix . '.void_sales.index', [
                'offline_sale_id' => $transaction->receipt_id,
            ]);
        }

        if ('Pending Layaway Sale Payment' === $transaction->payment_type) {
            $url = route(
                $prefix . '.layaway_sales.index',
                [
                    'offline_sale_id' => $transaction->receipt_id,
                    'status_id' => CreditAndLayawaySaleStatuses::PENDING->value,
                ]
            );
        }

        if ('Complete Layaway Sale Payment' === $transaction->payment_type) {
            $url = route(
                $prefix . '.layaway_sales.index',
                [
                    'offline_sale_id' => $transaction->receipt_id,
                    'status_id' => CreditAndLayawaySaleStatuses::COMPLETE->value,
                ]
            );
        }

        if ('Cancel Layaway Sale Payment' === $transaction->payment_type) {
            $url = route(
                $prefix . '.cancel_layaway_sales.index',
                [
                    'offline_sale_id' => $transaction->receipt_id,
                ]
            );
        }

        if ('Pending Credit Sale Payment' === $transaction->payment_type) {
            $url = route(
                $prefix . '.credit_sales.index',
                [
                    'offline_sale_id' => $transaction->receipt_id,
                    'status_id' => CreditAndLayawaySaleStatuses::PENDING->value,
                ]
            );
        }

        if ('Complete Credit Sale Payment' === $transaction->payment_type) {
            $url = route(
                $prefix . '.credit_sales.index',
                [
                    'offline_sale_id' => $transaction->receipt_id,
                    'status_id' => CreditAndLayawaySaleStatuses::COMPLETE->value,
                ]
            );
        }

        if ('Cancel Credit Sale Payment' === $transaction->payment_type) {
            $url = route($prefix . '.sales.index', [
                'offline_sale_id' => $transaction->receipt_id,
            ]);
        }

        if ('Booking Payment' === $transaction->payment_type || 'Booking Payment refund' === $transaction->payment_type) {
            $url = route($prefix . '.booking_payments.index', [
                'receipt_id' => $transaction->receipt_id,
            ]);
        }

        if ('Credit Note Refund' === $transaction->payment_type) {
            $url = route($prefix . '.credit_notes.index', [
                'credit_note_id' => $transaction->receipt_id,
            ]);
        }

        return [
            'receipt_id' => $transaction->receipt_id,
            'payment_type' => $transaction->payment_type,
            'amount' => $transaction->amount,
            'url' => $url,
        ];
    }
}
