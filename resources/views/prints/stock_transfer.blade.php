<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/stock-transfer-print.css') }}">
    <title>Stock Transfer</title>

    <style>
        table {
            font-size: 12px;
        }

        td { display: table-cell; margin: 10px 15px; padding: 10px;}

        .no-border {
            border: 0px !important;
        }
    </style>
</head>

<body class="arial-font arial-font-custom-report">
    <div class="row m-0">
        <div class="col-6 border">
            <div class="row">
                <div class="col-4">
                    <img alt="logo" class="img-fluid rounded" src="{{$stockTransfer->company->getDiskBasedFirstMediaUrl('dark_logo')}}">
                </div>

                <div class="col-8">
                    <p> <b>Registed Company Name:</b> {{ $stockTransfer->company->name }} </p>

                    <p> <b>SSN:</b> {{ $stockTransfer->company->social_security_number }} </p>

                    <p> <b>Address:</b> {{ $stockTransfer->company->address }} </p>
                </div>
            </div>
        </div>

        <div class="col-6 border">
            @if ($stockTransfer->status >= $staticTransferTypeShipped)
                <h1 class="text-right">TRANSFER {{ $transferTypeStatus }}</h1>
            @else
                <h1 class="text-right">{{ Str::upper($transferType) }}</h1>
            @endIf
            <h1 class="mr-2">{{ $currentStatus }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-3">
            <div class="pr-1">
                <h2>From:</h2>
                <p>
                    <b>{{ $stockTransfer->sourceLocation->name }}</b><br>
                    {{ $stockTransfer->sourceLocation->address_line_1 }},
                    {{ $stockTransfer->sourceLocation->address_line_2 }}<br>
                    {{ $stockTransfer->sourceLocation->city ? $stockTransfer->sourceLocation->city->name : '' }}<br>
                    <b>Tel:</b> {{ $stockTransfer->sourceLocation->phone }}<br>
                    <b>FAX:</b> {{ $stockTransfer->sourceLocation->fax }}
                </p>
            </div>
        </div>

        <div class="col-3">
            <h2>To:</h2>
            <p>
                <b>{{ $stockTransfer->destinationLocation->name }}</b><br>
                {{ $stockTransfer->destinationLocation->address_line_1 }},
                {{ $stockTransfer->destinationLocation->address_line_2 }} <br>
                {{ $stockTransfer->destinationLocation->city ? $stockTransfer->destinationLocation->city->name : '' }} <br>
                <b>Tel:</b> {{ $stockTransfer->destinationLocation->phone }} <br>
                <b>FAX:</b> {{ $stockTransfer->destinationLocation->fax }} <br>
            </p>
        </div>

        <div class="col-3 mt-2-5">
            <p>
                <b>Transfer Date:</b> {{$stockTransfer->transfer_date ? date('d/m/Y', strtotime($stockTransfer->transfer_date)) : 'N/A' }}<br>
                <b>Entry Date:</b> {{ $stockTransfer->created_at->format('d/m/Y') ?? 'N/A' }}<br>
                <b>Reference No:</b> {{ $stockTransfer->reference_number }}<br>
                <b>Remarks:</b> {{ $stockTransfer->remarks }}<br>
                <b>Attention:</b> {{ $stockTransfer->attention }}<br>
                <b>Reason:</b> {{ $stockTransfer->stockTransferReason?->name }}<br>
                <b>Entry By:</b> {{ $stockTransfer->requestedBy->username }}<br>
                <b>Shipped At: </b> {{ $stockTransfer->shipped_at ? date('d/m/Y H:i:s', strtotime($stockTransfer->shipped_at)) : 'N/A' }}<br>
                <b>Received Date: </b> {{ $stockTransfer->received_date ? date('d/m/Y', strtotime($stockTransfer->received_date)) : 'N/A' }}
            </p>
        </div>

        <div class="col-3 mt-2-5">
            <p>
                @if ($stockTransfer->transfer_order_number)
                    <b>Transfer Order:</b> {{ $stockTransfer->transfer_order_number }}<br>
                    <p>
                        <img src="data:image/png;base64,{{ $stockTransferBarcode['transfer_order_barcode'] }}" width="140" height="30">
                    </p>
                @elseif ($stockTransfer->request_order_number)
                    <b>Request Order:</b> {{ $stockTransfer->request_order_number }}<br>
                    <p>
                        <img src="data:image/png;base64,{{ $stockTransferBarcode['request_order_barcode'] }}" width="140" height="30">
                    </p>
                @endif

                @if ($stockTransfer->transfer_in_number)
                    <b>Transfer In:</b> {{ $stockTransfer->transfer_in_number }}<br>
                    <p>
                        <img src="data:image/png;base64,{{ $stockTransferBarcode['transfer_in_barcode'] }}" width="140" height="30">
                    </p>
                @endif
                @if ($stockTransfer->transfer_out_number)
                    <b>Transfer Out:</b> {{ $stockTransfer->transfer_out_number }}
                    <p>
                        <img src="data:image/png;base64,{{ $stockTransferBarcode['transfer_out_barcode'] }}" width="140" height="30">
                    </p>
                @endif
            </p>
        </div>
    </div>

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">Article Number</th>
                <th class="text-center">Description</th>
                <th class="text-center">Quantity</th>
                <th class="text-center">Received Quantity</th>
                <th class="text-center">Package Type (Qty)</th>
            </tr>
        </thead>

        <tbody>
            @foreach($stockTransferItems as $stockTransferItem)
                <tr class="{{ $loop->index !== 0 ? 'page-break-inside-avoid' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $stockTransferItem['article_number'] }}</td>
                    <td>
                        {{ $stockTransferItem['name'] }}
                        <table class="table mt-2">
                            <thead>
                                <tr>
                                    <th>UPC</th>
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
                                    <th>Received Quantity</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>

                            @foreach ($stockTransferItem['products'] as $product)
                                <tbody>
                                    <tr>
                                        <td rowspan="2" width="100" class="no-border">{{ $product['upc'] }}</td>
                                         @if(! $productVariant)
                                            <td rowspan="2" width="100" class="no-border">{{ $product['color'] }}</td>
                                        @endif
                                        @if(! $productVariant)
                                            <td rowspan="2" width="80" class="no-border">{{ $product['size'] }}</td>
                                        @endif
                                        @if($productVariant)
                                            <td rowspan="2" width="80" class="no-border">
                                                @foreach($product['attributes'] as $key => $attribute)
                                                    {{ $key }} : {{ $attribute }}<br>
                                                @endforeach
                                            </td>
                                        @endif
                                        <td rowspan="2" width="20" class="no-border">
                                            {{ $product['quantity'] }}
                                            {{ $product['derivative'] }}
                                        </td>
                                        <td rowspan="2" width="20" class="no-border">
                                            {{ $product['received_quantity'] }}
                                            {{ $product['derivative'] }}
                                        </td>
                                        <td rowspan="2" width="20" class="no-border">{{ $product['remarks'] }}</td>
                                    </tr>
                                </tbody>
                            @endforeach
                        </table>
                    </td>
                    <td>{{ $stockTransferItem['quantity'] }}</td>
                    <td>{{ $stockTransferItem['received_quantity'] }}</td>
                    <td>
                        {{ $stockTransferItem['package_type'] }}<br>
                        ({{ $stockTransferItem['package_quantity'] }})
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break-inside-avoid">
        <div class="row">
            <div class="col-5">
                <p><b>Created By:</b> {{ $stockTransfer->requestedBy->employee->getFullName() }}</p>
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
                <p><b>Requested Qty:</b>
                    {{ $stockTransfer->items->sum('quantity') }}
                </p>
                @if (
                    $stockTransfer->status === $staticTransferTypeReceived ||
                    $stockTransfer->status === $staticTransferTypeDiscrepancy ||
                    $stockTransfer->status === $staticTransferTypeClosed
                )
                    <p><b>Transferred Qty:</b>
                        {{ $stockTransfer->items->sum('received_quantity') }}
                    </p>
                @endif
                <p><b>Package Type Qty:</b> {{ $stockTransfer->items->sum('package_quantity') ?? 0 }}</p>
                @foreach($stockTransfer->items->whereNotNull('package_type_id')->groupBy('package_type_id') as $stockTransferItem)
                    <p><b>{{ $stockTransferItem->first()->packageType->name }}:</b>
                        {{ $stockTransferItem->sum('package_quantity') }}
                    <b>{{ $stockTransferItem->first()->packageType->name }} (s)</b></p>
                @endforeach
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-4">
                <div class="row mt-3">
                    <div class="col-3 mb-2">
                        Checker:
                    </div>

                    <div class="col-8">
                        <hr class="mt-3">
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="row mt-3">
                    <div class="col-3 mb-2">
                        Supervisor:
                    </div>

                    <div class="col-8">
                        <hr class="mt-3">
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="row mt-3">
                    <div class="col-3 mb-2">
                        Manager:
                    </div>

                    <div class="col-8">
                        <hr class="mt-3">
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-4 mt-4">
                <div class="row">
                    <div class="col-12">
                        <strong>Received by:</strong> {{ $receivedBy }}
                    </div>
                    <div class="col-12">
                        <strong>Approved by:</strong> {{ $statusManagedBy['approved_by'] }}
                    </div>
                    <div class="col-12">
                        <strong>Shipped by:</strong> {{ $statusManagedBy['shipped_by'] }}
                    </div>
                    <div class="col-12">
                        <strong>Closed by:</strong> {{ $statusManagedBy['closed_by'] }}
                    </div>
                    <div class="col-12">
                        <strong>Discrepancy by:</strong> {{ $statusManagedBy['discrepancy_by'] }}
                    </div>
                    <div class="col-12">
                        <strong>Cancelled by:</strong> {{ $statusManagedBy['cancelled_by'] }}
                    </div>
                </div>
            </div>

            <div class="col-4 mt-4">
                <div
                    class="transfer-feedback-box"
                >
                    Transfer Feedback
                </div>
            </div>

            <div class="col-4 mt-4">
                <div class="row">
                    <div class="col-4">
                        Lorry Driver:<br><br>
                        Lorry No:<br><br>
                        Driver's I/C:<br><br>
                        Driver's Phone:
                    </div>

                    <div class="col-7">
                        <hr class="mt-2"><br>
                        <hr class="mt-2"><br>
                        <hr class="mt-2"><br>
                        <hr class="mt-3">
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
