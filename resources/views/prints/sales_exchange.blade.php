<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Sales Exchange</title>

    <style>
        tr, td, th {
            padding: 4px;
            font-size: 11px;
        }

        table {
            padding: 0px 10px;
        }
        .table-auto {
            border: 1px solid black;
        }

        .date-display {
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Exchange Report" reportType="" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>


    @foreach ($locationSales as $locationSale)
        <p> Location : <strong> {{ $locationSale['location_name'] }} </strong> </p>
        <div>
            <table width=100%>
                <thead>
                    <tr>
                        <th class="bottom-border text-center" style="border: 1px solid black;" width="33%"> Receipt Before Exchange </th>
                        <th class="bottom-border text-center" style="border: 1px solid black;" width="34%"> Exchange </th>
                        <th class="bottom-border text-center" style="border: 1px solid black;" width="33%"> Receipt After Exchange </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locationSale['sale'] as $saleKey => $sale)
                        <tr class="@if($saleKey > 0) page-break-inside-avoid @endif">
                            <td style="border: 1px solid black; vertical-align: top;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="70"> Date </th>
                                            <th class="text-center"> Receipt No. </th>
                                            <th class="text-center"> Product No. Description </th>
                                            <th class="text-center"> Quantity </th>
                                            <th class="text-center"> Total </th>
                                            <th class="text-center"> Promoters </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($sale['sale_products'] as $key => $product)
                                            <tr class="@if($saleKey > 0) page-break-inside-avoid @endif">
                                                @if ($key === 0)
                                                    <td rowspan="{{ count($sale['sale_products']) }}"> {{ $sale['sale_happened_at'] }} </td>
                                                    <td rowspan="{{ count($sale['sale_products']) }}" style="word-break: break-all;"> {{ $sale['sale_offline_id'] }} </td>
                                                @endif
                                                <td>
                                                    <p>
                                                        {{ $product['upc'] }}
                                                    </p>

                                                    <p>
                                                        {{ $product['name'] }}
                                                    </p>
                                                </td>
                                                <td class="text-center"> {{ $product['quantity'] }} </td>
                                                <td  class="text-right">  {{ $currencySymbol }}{{ $product['price'] }} </td>
                                                <td  class="text-right"> {{ $sale['promoters'] }} </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                            <td style="border: 1px solid black; vertical-align: top;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="100"> Date </th>
                                            <th class="text-center"> Receipt No. </th>
                                            <th class="text-center"> Product No. Description </th>
                                            <th class="text-center"> Quantity </th>
                                            <th class="text-center" width="60"> Total </th>
                                            <th class="text-center"> Promoters </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($sale['return_sale_products'] as $product)
                                            <tr class="@if($saleKey > 0) page-break-inside-avoid @endif">
                                                <td>
                                                    {{ $sale['return_sale_happened_at'] }}
                                                </td>
                                                <td style="word-break: break-all;"> {{ $sale['return_sale_offline_id'] }} </td>
                                                <td>
                                                    <p>
                                                        {{ $product['upc'] }}
                                                    </p>

                                                    <p>
                                                        {{ $product['name'] }}
                                                    </p>
                                                    <p>
                                                        <b>Reason:</b> {{ $product['reason'] }}
                                                    </p>
                                                </td>
                                                <td class="text-center"> {{ $product['quantity'] }} </td>
                                                <td  class="text-right"> - {{ $currencySymbol }}{{ $product['price'] }} </td>
                                                <td  class="text-right"> {{ $sale['promoters'] }} </td>
                                            </tr>
                                            @endforeach
                                    </tbody>
                                </table>
                            </td>
                            <td style="border: 1px solid black; vertical-align: top;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="70"> Date </th>
                                            <th class="text-center"> Receipt No. </th>
                                            <th class="text-center"> Product No. Description </th>
                                            <th class="text-center"> Quantity </th>
                                            <th class="text-center" width="60"> Total </th>
                                            <th class="text-center"> Promoters </th>
                                        </tr>
                                    </thead>

                                    <tbody class="@if($saleKey > 0) page-break-inside-avoid @endif">
                                        @foreach ($sale['return_sale_products'] as $key => $product)
                                            <tr class="@if($saleKey > 0) page-break-inside-avoid @endif">
                                                @if ($key === 0)
                                                    <td rowspan="{{count($sale['return_sale_products'])}}" style="vertical-align: top;">
                                                        {{ $sale['return_sale_happened_at'] }}
                                                    </td>
                                                    <td rowspan="{{count($sale['return_sale_products'])}}"
                                                        style="word-break: break-all; vertical-align: top;"
                                                    >
                                                        {{ $sale['return_sale_offline_id'] }}
                                                    </td>
                                                @endif
                                                <td>
                                                    <p>
                                                        {{ $product['upc'] }}
                                                    </p>
                                                    <p>
                                                        {{ $product['name'] }}
                                                    </p>
                                                </td>
                                                <td class="text-center"> {{ $product['quantity'] }} </td>
                                                <td class="text-right"> - {{ $currencySymbol }}{{ $product['price'] }} </td>
                                                <td  class="text-right"> {{ $sale['promoters'] }} </td>
                                            </tr>
                                        @endforeach
                                        @if($sale['new_sale_offline_id'])
                                            @foreach ($sale['new_sale_products'] as $key => $product)
                                                <tr class="@if($saleKey > 0) page-break-inside-avoid @endif">
                                                    @if ($key === 0)
                                                        <td rowspan="{{count($sale['new_sale_products'])}}" style="vertical-align: top;">
                                                            {{ $sale['new_sale_happened_at'] }}
                                                        </td>
                                                        <td rowspan="{{count($sale['new_sale_products'])}}"
                                                            style="word-break: break-all; vertical-align: top;"
                                                        >
                                                            {{ $sale['new_sale_offline_id'] }}
                                                        </td>
                                                    @endif
                                                    <td>
                                                        <p>
                                                            {{ $product['upc'] }}
                                                        </p>
                                                        <p>
                                                            {{ $product['name'] }}
                                                        </p>
                                                    </td>
                                                    <td class="text-center"> {{ $product['quantity'] }} </td>
                                                    <td class="text-right">  {{ $currencySymbol }}{{ $product['price'] }} </td>
                                                    <td  class="text-right"> {{ $sale['promoters'] }} </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center"> No Record Found. </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
