<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Tax Invoice</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <div class="row">
        <div class="col-4">
            <img src="{{ $company_logo }}" alt="Company Logo">
        </div>

        <div class="col-7">
            <h4>
                {{ $company->name }} ( {{ $company->code }} )
            </h4>

            <h6>
                {{ $company->address }}
            </h6>
        </div>
    </div>

    <div class="row border-t-4">
        <h3 class="col-12 text-center font-bold">
            {{ $orderType }} Order Tax Invoice
        </h3>
    </div>

    <div class="row">
        <div class="col-6">
            <p class="font-bold">
                Bill To: <br />
                {{ $ordersDetails['member_details']['name'] }} <br />
                {{ $ordersDetails['member_details']['address_line_1'] }} <br />
                {{ $ordersDetails['member_details']['address_line_2'] }}
            </p>

            <p class="font-bold">
                TEL: {{ $ordersDetails['member_details']['mobile_number'] }}
            </p>
        </div>

        <div class="col-6 font-bold">
            <p> DO NO: {{ $ordersDetails['receipt_number'] }} </p>
            <p> Date: {{ $ordersDetails['date'] }} </p>
            <p> # Reference: {{ $ordersDetails['bill_reference_number'] }} </p>
        </div>
    </div>

    <div class="text-center">
        <table class="table">
            <thead>
                <tr>
                    <th> Upc </th>
                    <th> Description </th>
                     @if ($productVariant)
                        <th> Attributes </th>
                    @else
                        <th> Color </th>
                        <th> Size </th>
                    @endif
                    <th> Quantity </th>
                    <th> Unit Price </th>
                    <th> Discount ({{ $currency_symbol }}) </th>
                    <th> Tax ({{ $currency_symbol }}) </th>
                    <th> Amount ({{ $currency_symbol }}) </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($ordersDetails['order_items'] as $item)
                    <tr>
                        <td> {{ $item['upc'] }} </td>
                        <td> {{ $item['product'] }} </td>
                        @if ($productVariant)
                            <td> {{ $item['attributes'] }} </td>
                        @else
                            <td> {{ $item['color'] }} </td>
                            <td> {{ $item['size'] }} </td>
                        @endif
                        <td class="text-center"> @truncateDecimal($item['quantity']) </td>
                        <td class="text-right"> @currencyFormat($item['unit_price']) </td>
                        <td class="text-right"> @currencyFormat($item['total_discount_amount']) </td>
                        <td class="text-right"> @currencyFormat($item['total_tax_amount']) </td>
                        <td class="text-right"> @currencyFormat($item['total_price_paid']) </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="text-right">
            <p>
                Sub-total: {{ $currency_symbol . $ordersDetails['gross_sales'] }}
            </p>
            <p>
                Discount: {{ $currency_symbol . $ordersDetails['total_discount_amount'] }}
            </p>
            <p>
                Tax: {{ $currency_symbol . $ordersDetails['total_tax_amount'] }}
            </p>
            <p>
                Amount: {{ $currency_symbol . $ordersDetails['total_amount_paid'] }}
            </p>
            <p>
                Paid: {{ $currency_symbol . $ordersDetails['total_amount_paid_for_credit_or_layaway'] }}
            </p>
            @if ($ordersDetails['layaway_pending_amount'])
                <p>
                    Layaway Pending: {{ $currency_symbol . $ordersDetails['layaway_pending_amount'] }}
                </p>
            @elseif ($ordersDetails['credit_pending_amount'])
                <p>
                    Credit Pending: {{ $currency_symbol . $ordersDetails['credit_pending_amount'] }}
                </p>
            @endif
        </div>

        <div class="row">
            <p>
                N.B Payment by cheque should be crossed "A/C PAYEE ONLY" and payable to {{ $company->name }}
            </p>
        </div>

        <div class="row border-t-4">
            <div class="col-6">
                <p>
                    For {{ $company->name }} ({{ $company->code }})
                </p>
            </div>

            <div class="col-6">
                <p>
                    Received By:
                </p>
            </div>
        </div>

        <div class="row mt-16">
            <div class="col-6">
                <p class="border-t-4">
                    Authorised Signatory
                </p>
            </div>

            <div class="col-6">
                <p class="border-t-4">
                    Company Stamp and Signature
                </p>
            </div>
        </div>
    </div>
</body>

</html>
