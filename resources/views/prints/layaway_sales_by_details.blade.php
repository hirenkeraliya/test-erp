<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Layaway Sales </title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Details Of Layaway Sales" reportType="By Details" :date="$date"
        :dateRange="$dateRange" />

    @forelse($layawaySalesData as $seasonalSaleData)
        <h4>
            Location Name {{ $seasonalSaleData['location_name'] }}
        </h4>

        <table class="table table-bordered bordered">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th class="text-center">
                            {{ $column }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse($seasonalSaleData['products'] as $saleData)
                    <tr class="page-break-inside-avoid ">
                        <td> {{ $saleData['receipt_number'] }}</td>
                        <td> {{ $saleData['status'] }}</td>
                        <td> {{ $saleData['counter'] }}</td>
                        <td> {{ $saleData['cashier'] }}</td>
                        <td> {{ $saleData['layaway_authorizer'] }}</td>
                        <td class="text-right" style="width: 8%"> @currencyFormat($saleData['total_amount'])</td>
                        <td class="text-right" style="width: 8%"> @currencyFormat($saleData['total_amount_paid'])</td>
                        <td class="text-right" style="width: 8%"> @currencyFormat($saleData['layaway_pending_amount'])</td>
                    </tr>
                    @if (array_key_exists('items', $saleData))
                        <tr class="page-break-inside-avoid">
                            <td colspan="8">
                                <table class="table table-bordered mt-3">
                                    <thead>
                                        <tr>
                                            <th> Product Name </th>
                                            <th> Product UPC </th>
                                            @if(config('app.product_variant'))
                                                <th>Attributes</th>
                                            @else
                                                <th> Color </th>
                                                <th> Size </th>
                                            @endif
                                            <th> Quantity </th>
                                            <th> Unit Price </th>
                                            <th> SubTotal </th>
                                            <th> Discount </th>
                                            <th> Tax </th>
                                            <th> Paid </th>
                                            <th> Due </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($saleData['items'] as $item)
                                            <tr>
                                                <td> {{ $item['product_name'] }} </td>
                                                <td> {{ $item['product_upc'] }} </td>
                                                @if(config('app.product_variant'))
                                                    <td> {{ $item['attributes'] }} </td>
                                                @else
                                                    <td> {{ $item['color'] }} </td>
                                                    <td> {{ $item['size'] }} </td>
                                                @endif
                                                <td> {{ $item['quantity'] }} </td>
                                                <td class="text-right"> {{ $item['unit_price'] }} </td>
                                                <td class="text-right"> {{ $item['subtotal'] }} </td>
                                                <td class="text-right"> {{ $item['total_discount_amount'] }} </td>
                                                <td class="text-right"> {{ $item['total_tax_amount'] }} </td>
                                                <td class="text-right"> {{ $item['total_amount_paid'] }} </td>
                                                <td class="text-right"> {{ $item['total_pending_amount'] }} </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endif
                @empty
                    <td colspan="8" class="text-center">No Records</td>
                @endforelse

                @if (array_key_exists('totals', $seasonalSaleData))
                    <tr>
                        <th colspan="5">
                            Total
                        </th>

                        <th class="text-right" style="width: 8%">
                            @currencyFormat($seasonalSaleData['totals']['total_amount'])
                        </th>

                        <th class="text-right" style="width: 8%">
                            @currencyFormat($seasonalSaleData['totals']['total_amount_paid'])
                        </th>

                        <th class="text-right" style="width: 8%">
                            @currencyFormat($seasonalSaleData['totals']['total_layaway_pending_amount'])
                        </th>
                    </tr>
                @endif
            </tbody>
        </table>
    @empty
        <span class="text-center">No Records</span>
    @endforelse

    <table class="table table-bordered bordered">
        <thead>
            <tr>
                <th class="page-break-inside-avoid" colspan="4">
                    Grand Total:
                </th>

                <th class="text-right" style="width: 8%">
                    @currencyFormat($grandTotal['total_amount'])
                </th>

                <th class="text-right" style="width: 8%">
                    @currencyFormat($grandTotal['total_amount_paid'])
                </th>

                <th class="text-right" style="width: 8%">
                    @currencyFormat($grandTotal['total_layaway_pending_amount'])
                </th>
            </tr>
        </thead>
    </table>
</body>

</html>
