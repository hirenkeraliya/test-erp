<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\DigitalInvoice\Services\DigitalInvoiceService;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderReturn\OrderReturnQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;

test('getObject method call and return sale queries class object', function (): void {
    $modelType = ModelMapping::SALE->name;

    $digitalInvoiceService = resolve(DigitalInvoiceService::class);
    $response = $digitalInvoiceService->getObject($modelType);
    expect($response)->toBeInstanceOf(SaleQueries::class);
});

test('getObject method call and return sale return queries class object', function (): void {
    $modelType = ModelMapping::SALE_RETURN->name;

    $digitalInvoiceService = resolve(DigitalInvoiceService::class);
    $response = $digitalInvoiceService->getObject($modelType);
    expect($response)->toBeInstanceOf(SaleReturnQueries::class);
});

test('getObject method call and return booking payment queries class object', function (): void {
    $modelType = ModelMapping::BOOKING_PAYMENT->name;

    $digitalInvoiceService = resolve(DigitalInvoiceService::class);
    $response = $digitalInvoiceService->getObject($modelType);
    expect($response)->toBeInstanceOf(BookingPaymentQueries::class);
});

test('getObject method call and return credit note queries class object', function (): void {
    $modelType = ModelMapping::CREDIT_NOTE->name;

    $digitalInvoiceService = resolve(DigitalInvoiceService::class);
    $response = $digitalInvoiceService->getObject($modelType);
    expect($response)->toBeInstanceOf(CreditNoteQueries::class);
});

test('getObject method call and return order queries class object', function (): void {
    $modelType = ModelMapping::ORDER->name;

    $digitalInvoiceService = resolve(DigitalInvoiceService::class);
    $response = $digitalInvoiceService->getObject($modelType);
    expect($response)->toBeInstanceOf(OrderQueries::class);
});

test('getObject method call and return order return queries class object', function (): void {
    $modelType = ModelMapping::ORDER_RETURN->name;

    $digitalInvoiceService = resolve(DigitalInvoiceService::class);
    $response = $digitalInvoiceService->getObject($modelType);
    expect($response)->toBeInstanceOf(OrderReturnQueries::class);
});
