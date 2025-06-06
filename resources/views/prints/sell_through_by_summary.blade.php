<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Sell Through</title>
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <h4>
        <strong>Sell Through Report {{ $reportType }}</strong>
    </h4>

    <p>
        @if (is_array($filterDate))
        Sell Through from {{ $filterDate[0] }} to {{ $filterDate[1] }}
        @else
        Sell Through Date : {{ $filterDate }}
        @endif
    </p>

    <p>
        Date: {{ $date }}
    </p>

    <x-filter-label-header :getFilterLabels="$getFilterLabels" />

    <h3>
        @if ($locations)
        {{ $locations->getNamesWithCodes }}
        @else
        All Locations
        @endif
    </h3>

    <table class="table table-bordered bordered">
        <thead>
            <tr>
                @foreach ($columns as $column)
                <th class="text-center">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($sellThroughRecords as $key => $sellThroughProduct)
            <tr class="page-break-inside-avoid">
                <td>
                    {{ isset($sellThroughProduct['name']) ? $sellThroughProduct['name'] : 'N/A' }}
                </td>

                @if (is_countable($sellThroughProduct))
                <td class="p-2">
                    <table class="table table-bordered bordered">
                        <thead>
                            <tr>
                                @foreach ($locationColumns as $locationColumn)
                                <th class="text-center p-2">{{ $locationColumn }}</th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($sellThroughProduct['locations'] as $warehouseRecords)
                            <tr>
                                <td>
                                    {{ count($warehouseRecords) > 1 && array_key_exists('color_name',
                                    $warehouseRecords[0]) ? $warehouseRecords[0]['color_name'] : '' }}
                                </td>

                                @foreach ($warehouseRecords as $key => $location)
                                <td class="p-2 text-right">
                                    {{ $location['units_sold'] }}
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                @endif

                <td class="text-right">
                    {{ isset($sellThroughProduct['received']) ? $sellThroughProduct['received'] : 'N/A' }}
                </td>

                <td class="text-right">
                    {{ isset($sellThroughProduct['sold']) ? $sellThroughProduct['sold'] : 'N/A' }}
                </td>

                <td class="text-right">
                    {{ isset($sellThroughProduct['balance']) ? $sellThroughProduct['balance'] : 'N/A' }}
                </td>

                <td class="text-right">
                    {{ isset($sellThroughProduct['sell_through']) ? $sellThroughProduct['sell_through'] : 'N/A' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
