@component('mail::message',['emailLogos' => $emailLogos])

Dear {{ $sale->member->getFullName() }},

# Member Sale Invoice

Your sale details are given below.

@component('mail::table')
@php
    $allItemSubtotal = 0;
@endphp

| Product Name | Quantity | Unit Price | Discount | Tax | Total |
| ------------ |:--------:| ----------:| --------:| -------:| ---------:|
@foreach ($sale->saleItems as $saleItem)
@php

    $subtotal = $saleItem->quantity * $saleItem->original_price_per_unit;
    $allItemSubtotal += $subtotal;

@endphp
| {{ $saleItem->product->name }} | {{ $saleItem->quantity }} | {{ $currencySymbol . $saleItem->original_price_per_unit }} | {{ $saleItem->total_discount_amount }} | {{ $currencySymbol . $saleItem->total_tax_amount }} | {{ $currencySymbol . $saleItem->total_price_paid }} |
@endforeach
|             |          |            |          | GROSS TOTAL  | {{ $currencySymbol . $allItemSubtotal }} |
|             |          |            |          | TOTAL AMOUNT | {{ $currencySymbol . $sale->total_amount_paid }} |
@endcomponent

<br/>

@component('mail::table')
| Payment Type | Amount Paid |
|:------------:| -----------:|
@foreach ($sale->payments as $payment)
| {{ $payment->paymentType->name }} | {{ $currencySymbol . $payment->amount }} |
@endforeach
| TOTAL | {{ $currencySymbol . $sale->payments->sum('amount') }} |
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
