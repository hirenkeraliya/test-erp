<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title> Cancel Layaway Sale Report</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <div class="row">
        <div class="col-7">
            <h4>
                {{ $company->name }} ( {{ $company->code }} )
            </h4>
        </div>

        <div class="col-5">
            <h4 class="pl-5">
                Cancel Layaway Sales
            </h4>
        </div>
    </div>

    <div class="date-display">

        <p>
            Date: {{ $date }}
        </p>

        <p>
            Location: {{ $location->name }} ( {{ $location->code }} )
        </p>

        <p>
            Receipt Id: {{ $salesDetails['offline_sale_id'] }}
        </p>

        <p>
            Credit Note Id: {{ $salesDetails['credit_note_id'] }}
        </p>

        <p>
            # Reference: {{ $salesDetails['bill_reference_number'] }}
        </p>
    </div>

    <div>
        <h4>
            Items
        </h4>
        <table class="table">
            <thead>
                <tr>
                    <th class="text-left"> Product </th>
                    @if ($productVariant)
                        <th class="text-left"> Attributes </th>
                    @else
                        <th class="text-left"> Color </th>
                        <th class="text-left"> Size </th>
                    @endif
                    <th class="text-left"> Upc </th>
                    <th class="text-right"> Quantity </th>
                    <th class="text-right"> Unit Price {{ '(' . $currencySymbol . ')' }} </th>
                    <th class="text-right"> Subtotal {{ '(' . $currencySymbol . ')' }} </th>
                    <th class="text-right"> Discount {{ '(' . $currencySymbol . ')' }} </th>
                    <th class="text-right"> Tax {{ '(' . $currencySymbol . ')' }} </th>
                    <th class="text-right"> Payable Amount {{ '(' . $currencySymbol . ')' }} </th>
                    <th class="text-right"> Paid {{ '(' . $currencySymbol . ')' }} </th>
                    <th class="text-right"> Pending {{ '(' . $currencySymbol . ')' }} </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($salesDetails['sale_items'] as $item)
                    <tr>
                        <td class="text-left"> {{ $item['product'] }} </td>
                        @if ($productVariant)
                            <td class="text-left"> {{ $item['attributes'] }} </td>
                        @else
                            <td class="text-left"> {{ $item['color'] }} </td>
                            <td class="text-left"> {{ $item['size'] }} </td>
                        @endif
                        <td class="text-left"> {{ $item['upc'] }} </td>
                        <td class="text-right"> {{ $item['quantity'] }} </td>
                        <td class="text-right"> {{ number_format($item['unit_price'], 2) }} </td>
                        <td class="text-right"> {{ number_format($item['subtotal'], 2) }} </td>
                        <td class="text-right"> {{ number_format($item['total_discount_amount'], 2) }} </td>
                        <td class="text-right"> {{ number_format($item['total_tax_amount'], 2) }} </td>
                        <td class="text-right"> {{ number_format($item['unit_price'] - $item['total_discount_amount'], 2) }} </td>
                        <th class="text-right"> {{ number_format($item['total_price_paid'], 2) }} </th>
                        <th class="text-right"> {{ number_format($item['total_pending_layaway_amount'], 2) }} </th>
                    </tr>
                @endforeach
                    <tr>
                        <th class="text-right" colspan="12">
                            <p>Payable : {{$currencySymbol}}{{ number_format($salesDetails['total_amount_paid'] + $salesDetails['layaway_pending_amount'], 2) }}</p>
                            <p>Paid : {{$currencySymbol}}{{ number_format($salesDetails['total_amount_paid'], 2) }}</p>
                            <p>Pending : {{$currencySymbol}}{{ number_format($salesDetails['layaway_pending_amount'], 2) }}</p>
                        </th>
                    </tr>
            </tbody>
        </table>

        <h4>
            Payment Modes
        </h4>

        <table class="table">
            <thead>
                <tr>
                    <th> Payment Type </th>
                    <th class="text-right"> Amount </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($salesDetails['payments'] as $payment)
                    <tr>
                        <td> {{ $payment['payment_type'] }} </td>
                        <td class="text-right"> {{$currencySymbol}}{{ number_format($payment['amount'], 2) }} </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mb-8">
            <p>
                Member Name: {{ $salesDetails['user_details']['name'] }}
            </p>
            <p>
                Address: {{ $salesDetails['user_details']['address_line_1'] }}
                {{ $salesDetails['user_details']['address_line_2'] }}
                {{ $salesDetails['user_details']['city'] }}
                {{ $salesDetails['user_details']['area_code'] }}
            </p>
        </div>

        <div class="row">
            <div class="col-6 text-center">
                <div><p class="border-t">Member Signature</p></div>
                <div><p class="border-t">Member Name</p></div>
            </div>
            <div class="col-6 text-center">
                <div><p class="border-t">Signature</p></div>
                <div><p class="border-t">Name</p></div>
            </div>
        </div>
    </div>
</body>

</html>
