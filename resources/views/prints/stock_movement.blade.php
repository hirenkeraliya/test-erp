<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Stock Movements Report</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Stock Movements Report" :reportType="$reportType" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    <h4 class="text-bold">
        Movement Cost
    </h4>

    @foreach($stockMovements as $key => $stockMovement)
    @if($key != 'grand_total')
    <p> Location : <strong> {{ $stockMovement['location_name'] }} </strong> </p>
    @endif

    @if(array_key_exists('products',$stockMovement))
    @forelse($stockMovement['products'] as $products)
    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="text-center">UPC</th>
                <th class="text-center">Product Name</th>
                <th class="text-center">Article Number</th>
                <th class="text-center">Brand</th>
                <th class="text-center">Department</th>
                @if(config('app.product_variant'))
                    <th class="text-center">Attributes</th>
                @else
                    <th class="text-center">Color</th>
                    <th class="text-center">Size</th>
                @endif
                <th class="text-center">Opening Stock</th>
                <th class="text-center">GRN (+)</th>
                <th class="text-center">GRN (-)</th>
                <th class="text-center">Transfer In (+)</th>
                <th class="text-center">Transfer Out (-)</th>
                <th class="text-center">Purchase Order In (+)</th>
                <th class="text-center">Purchase Order Out (-)</th>
                <th class="text-center">+ Stock Adj Qty</th>
                <th class="text-center">- Stock Adj Qty</th>
                <th class="text-center">Sales Qty</th>
                <th class="text-center">Sale Return Qty</th>
                <th class="text-center">Orders Qty</th>
                <th class="text-center">Order Return Qty</th>
                <th class="text-center">Qty IN</th>
                <th class="text-center">Qty OUT</th>
                <th class="text-center">Closing Stock</th>
            </tr>
        </thead>

        <tbody>
            @foreach($products as $inventoryUpdate)
            <tr class="page-break-inside-avoid">
                <td class="text-left">{{ $inventoryUpdate['upc'] }}</td>
                <td class="text-left">{{ $inventoryUpdate['product_name'] }}</td>
                <td class="text-left">{{ $inventoryUpdate['article_number'] }}</td>
                <td class="text-left">{{ $inventoryUpdate['brand'] }}</td>
                <td class="text-left">{{ $inventoryUpdate['department'] }}</td>
                @if(config('app.product_variant'))
                    <td class="text-left">{{ $inventoryUpdate['attributes'] }}</td>
                @else
                    <td class="text-left">{{ $inventoryUpdate['color'] }}</td>
                    <td class="text-left">{{ $inventoryUpdate['size'] }}</td>
                @endif
                <td class="text-right"> {{ $inventoryUpdate['opening_stock'] }} </td>
                <td class="text-right">{{ $inventoryUpdate['good_receive_note_quantity_in'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['good_receive_note_quantity_out'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['stock_transfer_quantity_in'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['stock_transfer_quantity_out'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['purchase_order_quantity_in'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['purchase_order_quantity_out'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['positive_stock_adjustment_quantity'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['negative_stock_adjustment_quantity'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['sale_quantity'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['sale_return_quantity'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['order_quantity'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['order_return_quantity'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['good_receive_note_quantity_in'] +
                    $inventoryUpdate['positive_stock_adjustment_quantity'] + $inventoryUpdate['sale_return_quantity'] + $inventoryUpdate['order_return_quantity'] +
                    $inventoryUpdate['stock_transfer_quantity_in'] +
                    $inventoryUpdate['purchase_order_quantity_in'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['good_receive_note_quantity_out'] + $inventoryUpdate['negative_stock_adjustment_quantity'] +
                    $inventoryUpdate['sale_quantity'] +  $inventoryUpdate['order_quantity'] + $inventoryUpdate['stock_transfer_quantity_out'] + $inventoryUpdate['purchase_order_quantity_out'] }}</td>
                <td class="text-right">{{ $inventoryUpdate['closing_stock'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @empty
    <table class="table table-bordered">
        <tr>
            <th colspan="16" class="text-center">
                No records found.
            </th>
        </tr>
    </table>
    @endforelse
    @endif
    @endforeach
    <table class="table table-bordered">
        <tbody>
            <tr class="page-break-inside-avoid">
                <th colspan="3"></th>
                <th class="text-center">Opening Stock</th>
                <th class="text-center">GRN (+)</th>
                <th class="text-center">GRN (-)</th>
                <th class="text-center">Transfer In (+)</th>
                <th class="text-center">Transfer Out (-)</th>
                <th class="text-center">Purchase Order In (+)</th>
                <th class="text-center">Purchase Order Out (-)</th>
                <th class="text-center">+ Stock Adj Qty</th>
                <th class="text-center">- Stock Adj Qty</th>
                <th class="text-center">Sales Qty</th>
                <th class="text-center">Sale Return Qty</th>
                <th class="text-center">Orders Qty</th>
                <th class="text-center">Order Return Qty</th>
                <th class="text-center">Qty IN</th>
                <th class="text-center">Qty OUT</th>
                <th class="text-center">Closing Stock</th>
            </tr>

            <tr class="page-break-inside-avoid">
                <th colspan="3">Grand Total</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_opening_stock'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_good_receive_note_quantity_in'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_good_receive_note_quantity_out'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_stock_transfer_quantity_in'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_stock_transfer_quantity_out'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_purchase_order_quantity_in'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_purchase_order_quantity_out'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_positive_stock_adjustment_quantity'] }}
                </th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_negative_stock_adjustment_quantity'] }}
                </th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_sale_quantity'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_sale_return_quantity'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_order_quantity'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_order_return_quantity'] }}</th>
                <th class="text-center">{{
                    $stockMovements['grand_total']['total_good_receive_note_quantity_in'] +
                    $stockMovements['grand_total']['total_positive_stock_adjustment_quantity'] +
                    $stockMovements['grand_total']['total_sale_return_quantity'] +
                    $stockMovements['grand_total']['total_sale_return_quantity'] +
                    $stockMovements['grand_total']['total_order_return_quantity'] +
                    $stockMovements['grand_total']['total_purchase_order_quantity_in'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_negative_stock_adjustment_quantity'] +
                    $stockMovements['grand_total']['total_good_receive_note_quantity_out'] +
                    $stockMovements['grand_total']['total_sale_quantity'] +
                    $stockMovements['grand_total']['total_order_quantity'] +
                    $stockMovements['grand_total']['total_stock_transfer_quantity_out'] +
                    $stockMovements['grand_total']['total_purchase_order_quantity_out'] }}</th>
                <th class="text-center">{{ $stockMovements['grand_total']['total_closing_stock'] }}</th>
            </tr>
        </tbody>
    </table>
</body>

</html>
