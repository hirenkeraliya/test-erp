<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Credit Sale Report</title>

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
                Credit Sale Tax Invoice
            </h4>
        </div>
    </div>

    <div class="row">
        <div class="col-6 date-display">
            <p>
                Date: {{ $date }}
            </p>

            <p>
                Location: {{ $location->name }} ( {{ $location->code }} )
            </p>
        </div>

        <div class="col-6">
            <p>
                Credit Sale Id: {{ $salesDetails['receipt_number'] }}
            </p>
        </div>
    </div>

    <div>
        <h4>
            Items
        </h4>
        <table class="table">
            <thead>
                <tr>
                    <th class="text-center"> UPC </th>
                    <th class="text-center"> Product </th>
                    @if ($productVariant)
                        <th class="text-center"> Attributes </th>
                    @else
                        <th class="text-center"> Color </th>
                        <th class="text-center"> Size </th>
                    @endif
                    <th class="text-center"> Quantity </th>
                    <th class="text-center"> Unit Price {{ '(' . $currencySymbol . ')' }} </th>
                    <th class="text-center"> Subtotal {{ '(' . $currencySymbol . ')' }} </th>
                    <th class="text-center"> Discount {{ '(' . $currencySymbol . ')' }} </th>
                    <th class="text-center"> Tax {{ '(' . $currencySymbol . ')' }} </th>
                    <th class="text-center"> Paid {{ '(' . $currencySymbol . ')' }} </th>
                    <th class="text-center"> Pending {{ '(' . $currencySymbol . ')' }} </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($salesDetails['sale_items'] as $item)
                    <tr>
                        <td> {{ $item['upc'] }} </td>
                        <td> {{ $item['product'] }} </td>
                        @if ($productVariant)
                            <td> {{ $item['attributes'] }} </td>
                        @else
                            <td> {{ $item['color'] }} </td>
                            <td> {{ $item['size'] }} </td>
                        @endif
                        <td class="text-center"> {{ $item['quantity'] }} </td>
                        <td class="text-right"> {{ $item['unit_price'] }} </td>
                        <td class="text-right"> {{ $item['subtotal'] }} </td>
                        <td class="text-right"> {{ $item['total_discount_amount'] }} </td>
                        <td class="text-right"> {{ $item['total_tax_amount'] }} </td>
                        <td class="text-right"> {{ $item['total_price_paid'] }} </td>
                        <td class="text-right"> {{ $item['total_pending_credit_amount'] }} </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h4>
            Payment Modes
        </h4>

        <table class="table">
            <thead>
                <tr>
                    <th> Payment Type </th>
                    <th> Amount </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($salesDetails['payments'] as $payment)
                    <tr>
                        <td> {{ $payment['payment_type'] }} </td>
                        <td> {{ $payment['amount'] }} </td>
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
                <div>
                    <p class="border-t">Member Signature</p>
                </div>
                <div>
                    <p class="border-t">Member Name</p>
                </div>
            </div>
            <div class="col-6 text-center">
                <div>
                    <p class="border-t">Signature</p>
                </div>
                <div>
                    <p class="border-t">Name</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
