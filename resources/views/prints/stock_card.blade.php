<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Stock Card Report</title>

    <style>
        .flex {
            display: flex;
            justify-content: space-between
        }
    </style>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Stock Card Report" reportType="" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <p> Location: <strong> {{ $locationName }} </strong> </p>

    @forelse($storeInventories as $storeInventory)
        <div class="flex">
            <p>
                <b>Name:</b> {{ $storeInventory['name'] }}
            </p>

            <p>
                <b>UPC:</b> {{ $storeInventory['upc'] }}
            </p>
            @if(config('app.product_variant'))
                <p>
                    <b>Attributes:</b> {{ $storeInventory['attributes'] }}
                </p>
            @else
                <p>
                    <b>Color:</b> {{ $storeInventory['color'] }}
                </p>

                <p>
                    <b>Size:</b> {{ $storeInventory['size'] }}
                </p>
            @endif
        </div>

        <table class="table table-bordered">
            <thead>
                <tr class="page-break-inside-avoid">
                    <th class="text-center">Transaction <br>Date</th>
                    <th class="text-center item">Post <br>Date</th>
                    <th class="text-center item">Type</th>
                    <th class="text-center item">Document No</th>
                    <th class="text-center item">Description</th>
                    <th class="text-center mt-2" >Qty In</th>
                    <th class="text-center mt-2" >Qty Out</th>
                    <th class="text-center mt-2" >Balance</th>
                </tr>
            </thead>

            <tbody>
                @forelse($storeInventory['inventories'] as $product)
                    <tr>
                        <td>{{ $product['transaction_date'] }}</td>
                        <td>{{ $product['post_date'] }}</td>
                        <td>{{ $product['type'] }}</td>
                        <td>{{ $product['document_no'] }}</td>
                        <td>{{ $product['description'] }}</td>
                        <td class="text-center mt-2">{{ $product['qty_in'] }}</td>
                        <td class="text-center mt-2">{{ abs((float)$product['qty_out']) }}</td>
                        <td class="text-right mt-2">{{ $product['balance'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No Records</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @empty
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th class="text-center">
                        No Record Found.
                    </th>
                </tr>
            </tbody>
        </table>
    @endforelse
    @if($grandTotals)
        <table class="table table-bordered">
            <tfoot>
                <tr>
                    <td class="text-center" colspan="4"></td>
                    <th class="text-center">Opening Balance</th>
                    <th class="text-center">Qty In</th>
                    <th class="text-center">Qty Out</th>
                    <th class="text-center">Balance</th>
                </tr>
                <tr>
                    <th class="text-center" colspan="4" >Grand Total</th>
                    <td class="text-center">{{ $grandTotals['total_opening_balance'] }}</td>
                    <td class="text-center">{{ $grandTotals['total_qty_in'] }}</td>
                    <td class="text-center">{{ $grandTotals['total_qty_out'] }}</td>
                    <td class="text-center">{{ $grandTotals['total_balance'] }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
</body>
</html>
