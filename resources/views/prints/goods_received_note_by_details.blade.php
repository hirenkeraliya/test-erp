<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Goods Received Note(ByDetails)</title>
</head>
<body class="arial-font-custom-report">
     <x-report-header :company="$company" reportName="Goods Received Notes Report" reportType="By Details" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    @foreach ($goodsReceivedNotes as $goodsReceivedNote)
        <p> Location : <strong> {{ $goodsReceivedNote['location_name'] }} </strong> </p>

        <table class="table table-bordered">
            <thead >
                <tr>
                    <th class="text-center">Date</th>
                    <th class="text-center">Grn Ref</th>
                    <th class="text-center">Created By</th>
                    <th class="text-center item">Do Ref</th>
                    <th class="text-center">Po Ref</th>
                    <th class="text-center">Notes</th>
                    <th class="text-center">Quantity</th>
                </tr>
            </thead>

            <tbody>
                @if (count($goodsReceivedNote['goods_received_notes']) > 0)
                    @forelse($goodsReceivedNote['goods_received_notes'] as $goodsReceivedNoteItem)
                        <tr class="page-break-inside-avoid">
                            <td class="{{ $goodsReceivedNoteItem['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteItem['date'] }}</td>
                            <td class="{{ $goodsReceivedNoteItem['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteItem['grn_ref'] }}</td>
                            <td class="{{ $goodsReceivedNoteItem['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteItem['created_by'] }}</td>
                            <td class="{{ $goodsReceivedNoteItem['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteItem['do_ref'] }}</td>
                            <td class="mt-2 {{ $goodsReceivedNoteItem['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteItem['po_ref'] }}</td>
                            <td class="mt-2 {{ $goodsReceivedNoteItem['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteItem['notes'] }}</td>
                            <td class="text-center mt-2 {{ $goodsReceivedNoteItem['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteItem['total_quantity'] }}</td>
                        </tr>

                        @if(array_key_exists('products',$goodsReceivedNoteItem))
                            <tr class="page-break-inside-avoid">
                                <td></td>
                                <td colspan="4">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th class="border-top-none">Name</th>
                                                <th class="border-top-none">Article number</th>
                                                <th class="text-right border-top-none">Quantity</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach($goodsReceivedNoteItem['products'] as $product)
                                                <tr>
                                                    <td class="border-top-none">{{ $product['name'] }}</td>
                                                    <td class="border-top-none">{{ $product['article_number'] }}</td>
                                                    <td class="text-right border-top-none">{{ $product['total_quantity'] }}</td>
                                                </tr>
                                                <tr>
                                                @if(array_key_exists('color_wise_products',$product))
                                                <tr>
                                                    <td class="border-top-none"></td>
                                                    <td colspan="2" class="border-top-none">
                                                        <table class="table">
                                                            <thead >
                                                                <tr>
                                                                    <th class="border-top-none">UPC</th>
                                                                    @if(config('app.product_variant'))
                                                                      <th class="border-top-none">Attributes</th>
                                                                    @else
                                                                        <th class="border-top-none">Color</th>
                                                                        <th class="border-top-none">Size</th>
                                                                    @endif
                                                                    <th class="text-right border-top-none">Quantity</th>
                                                                </tr>
                                                            </thead>

                                                            <tbody>
                                                                @foreach($product['color_wise_products'] as $colorWiseProduct)
                                                                    <tr>
                                                                        <td class="border-top-none">{{ $colorWiseProduct['upc'] }}</td>
                                                                        @if(config('app.product_variant'))
                                                                            <td class="border-top-none">{{ $colorWiseProduct['attributes'] }}</td>
                                                                        @else
                                                                            <td class="border-top-none">{{ $colorWiseProduct['color'] }}</td>
                                                                            <td class="border-top-none">{{ $colorWiseProduct['size'] }}</td>
                                                                        @endif
                                                                        <td class="text-right border-top-none">{{ $colorWiseProduct['quantity'] }}</td>
                                                                    </tr>

                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                        @endif
                    @endforeach
                    <tr class="page-break-inside-avoid">
                        <td class="{{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['date'] }}
                        </td>
                        <td class="{{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['grn_ref'] }}
                        </td>
                        <td class="{{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['created_by'] }}
                        </td>
                        <td class="{{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['do_ref'] }}
                        </td>
                        <td class="mt-2 {{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['po_ref'] }}
                        </td>
                        <td class="mt-2 {{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['notes'] }}
                        </td>
                        <td class="text-center mt-2 {{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['total_quantity'] }}
                        </td>
                    </tr>
                @else
                    <tr>
                        <td colspan="4" class="text-center">No Records</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach
</body>
</html>
