<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/stock-transfer-print.css') }}">
    <title>Goods Received Note</title>

    <style>
        table {
            font-size: 12px;
        }

        td {
            display: table-cell;
            margin: 10px 15px;
            padding: 10px;
        }

        .no-border {
            border: 0px !important;
        }
    </style>
</head>

<body class="arial-font arial-font-custom-report">
    <div class="row m-0">
        <div class="col-2">
            <img alt="logo" class="img-fluid rounded" src="{{ $goodsReceivedNote->company->getDiskBasedFirstMediaUrl('dark_logo') }}">
        </div>

        <div class="col-10">
            <h1 class="text-center">Goods Received Note</h1>
        </div>
    </div>

    <h3 class="text-center">{{ $goodsReceivedNote->company->name }}</h3>
    <div class="row m-0">
        <div class="col-6">
            <p class="text-left">
                <b>GRN Date:</b> {{ date('d/m/Y', strtotime($goodsReceivedNote->created_at)) }}<br>
                <b>GRN Reference No:</b> {{ $goodsReceivedNote->grn_reference }}<br>
                <b>Notes:</b> {{ $goodsReceivedNote->notes }}<br>
                <b>Location:</b> {{ $goodsReceivedNote->location->name . ' (' . $locationType . ')' }}<br>
            </p>
        </div>
        <div class="col-6">
            <p class="text-left">
                <b>Purchase Order Reference:</b> {{ $goodsReceivedNote->purchase_order_reference }}<br>
                <b>Delivery Order Reference:</b> {{ $goodsReceivedNote->delivery_order_reference }}<br>
                <b>Vendor:</b> {{ $goodsReceivedNote->vendor?->name }}<br>
            </p>
        </div>
    </div>

    <table class="table table-bordered mt-3 w-full">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">Article Number</th>
                <th class="text-center">Description</th>
                <th class="text-center">Quantity</th>
            </tr>
        </thead>

        <tbody>
            @foreach($goodsReceivedNoteProducts as $goodsReceivedNoteProduct)
            <tr class="page-break-inside-avoid">
                <td>{{ $loop->iteration }}</td>
                <td>{{ $goodsReceivedNoteProduct['article_number'] }}</td>
                <td>
                    <table class="table mt-2">
                        <thead>
                            <tr>
                                <th>UPC</th>
                                <th>Name</th>
                                @if(! $productVariant)
                                <th>Color</th>
                                @endif
                                @if(! $productVariant)
                                <th>Size</th>
                                @endif
                                @if($productVariant)
                                <th>Attributes</th>
                                @endif
                                <th>Quantity</th>
                                <th>Batch</th>
                                <th>Batch Expiry Date</th>
                            </tr>
                        </thead>

                        @foreach ($goodsReceivedNoteProduct['items'] as $product)
                        <tbody>
                            <tr>
                                <td width="100" class="no-border">{{ $product['upc'] }}</td>
                                <td width="100" class="no-border">{{ $product['name'] }}</td>
                                @if(! $productVariant)
                                    <td width="100" class="no-border">{{ $product['color'] }}</td>
                                @endif
                                @if(! $productVariant)
                                    <td width="80" class="no-border">{{ $product['size'] }}</td>
                                @endif
                                @if($productVariant)
                                    <td width="80" class="no-border">
                                        @foreach ($product['attributes'] as $key => $attribute)
                                            <p>{{ $key }} : {{ $attribute }}</p>
                                        @endforeach
                                    </td>
                                @endif
                                <td width="20" class="no-border text-center">{{ $product['quantity'] }}</td>
                                <td width="20" class="no-border">{{ $product['batch_number'] }}</td>
                                <td width="20" class="no-border">{{ $product['batch_expiry_date'] }}</td>
                            </tr>
                        </tbody>
                        @endforeach
                    </table>
                </td>
                <td class="text-center">{{ $goodsReceivedNoteProduct['quantity'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break-inside-avoid">
        <div class="row">
            <div class="col-5">
                <p><b>Created By:</b> {{ $goodsReceivedCreatedUser ?? 'N/A' }}</p>
            </div>

            <div class="col-4">
                <div class="row">
                    <div class="col-3 mb-2">
                        Remark:
                    </div>

                    <div class="col-8">
                        <hr class="mt-3">
                        <hr class="mt-5">
                    </div>
                </div>
            </div>

            <div class="col-3">
                @php $total=0 @endphp
                <p><b>Quantities:</b>
                    @foreach($goodsReceivedNoteProducts as $goodsReceivedNoteProduct)
                    @php $total += $goodsReceivedNoteProduct['quantity'] @endphp
                    @endforeach
                    {{ $total }}
                </p>
            </div>

        </div>
    </div>
</body>

</html>