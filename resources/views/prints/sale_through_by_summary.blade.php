<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Sales Through Report</title>
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <h4>
        <strong>Sales Through Report {{ $reportType }}</strong>
    </h4>

    <p>
        Sales Through from {{ $dateRange[0] }} to {{ $dateRange[1] }}
    </p>

    <p>
        Date: {{ $date }}
    </p>

    <x-filter-label-header :getFilterLabels="$getFilterLabels" />

    <h3>
        @if ($selectedLocations)
            @foreach ($selectedLocations as $location)
                {{ $location->getNameWithCode() }},
            @endforeach
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
            @forelse ($saleThroughRecords as $key => $saleThroughProduct)
                <tr class="page-break-inside-avoid">
                    <td>
                        {{ isset($saleThroughProduct['name']) ? $saleThroughProduct['name'] : 'N/A' }}
                    </td>

                    @if (is_countable($saleThroughProduct))
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
                                    @foreach ($saleThroughProduct['locations'] as $warehouseRecords)
                                        <tr>
                                            <td>
                                                {{ count($warehouseRecords) > 1 && array_key_exists('color_name', $warehouseRecords[0]) ? $warehouseRecords[0]['color_name'] : '' }}
                                            </td>

                                            @foreach ($warehouseRecords as $key => $location)
                                                <td class="p-2 text-right {{ $location['units_sold'] > 0 ? 'text-green-with-light-background' : '' }}">
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
                        {{ isset($saleThroughProduct['received']) ? $saleThroughProduct['received'] : 'N/A' }}
                    </td>

                    <td class="text-right">
                        {{ isset($saleThroughProduct['sold']) ? $saleThroughProduct['sold'] : 'N/A' }}
                    </td>

                    <td class="text-right">
                        {{ isset($saleThroughProduct['returned']) ? $saleThroughProduct['returned'] : 'N/A' }}
                    </td>

                    <td class="text-right">
                        {{ isset($saleThroughProduct['balance']) ? $saleThroughProduct['balance'] : 'N/A' }}
                    </td>

                    <td class="text-right">
                        {{ isset($saleThroughProduct['sale_through']) ? $saleThroughProduct['sale_through'] : 'N/A' }}
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
