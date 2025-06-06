<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.4.3/echarts.min.js"></script>
    <script type="text/javascript" src="{{ asset('build/js/chartHelper.js') }}"></script>
    <title>Sell Through Report</title>
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
            @forelse($sellThroughDataByLocations as $sellThroughDataByLocation)
                <tr class="page-break-inside-avoid">
                    <td>
                        {{ $sellThroughDataByLocation['name'] }}
                    </td>

                    <td>
                        {{ $sellThroughDataByLocation['code'] }}
                    </td>

                    <td class="text-right">
                        {{ (float) $sellThroughDataByLocation['received'] }}
                    </td>

                    <td class="text-right">
                        {{ (float) $sellThroughDataByLocation['sold'] }}
                    </td>

                    <td class="text-right">
                        {{ (float) $sellThroughDataByLocation['balance'] }}
                    </td>

                    <td class="text-right">
                        {{ (float) $sellThroughDataByLocation['sell_through'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
                </tr>
            @endforelse

            <tr class="page-break-inside-avoid text-bold">
                <td colspan="2">
                    <b>
                        Grand Total
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $sellThroughTotalDataByLocations['received'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $sellThroughTotalDataByLocations['sold'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $sellThroughTotalDataByLocations['remaining'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $sellThroughTotalDataByLocations['sell_through'] }}
                    </b>
                </td>
            </tr>
        </tbody>
    </table>

    @if (count($chartRecords) > 0)
        <div class="page-break-inside-avoid">
            <h2> Bar Chart </h2>
            <div id="bar-chart" style="width: 100%; height:400px;"></div>
        </div>
    @endif

    <script type="text/javascript">
        var barChartElement = echarts.init(document.getElementById('bar-chart'));

        var chartLabel = {!! isset($chartRecords['labels']) ? json_encode($chartRecords['labels']) : '[]' !!};
        var chartData = {!! isset($chartRecords['sell_through'])
            ? json_encode($chartRecords['sell_through'])
            : '[]' !!};

        chartLabel = chartLabel.length > 0 ? chartLabel : ['No Records'];
        chartData = chartData.length > 0 ? chartData : [10];

        const preparedRecords = [];
        for (const key in chartLabel) {
            preparedRecords.push({
                name: chartLabel[key],
                value: chartData[key]
            });
        }

        barChartElement.setOption({
            legend: {
                data: 'Sell Through (%)'
            },
            xAxis: {
                data: chartLabel,
                axisTick: {
                    alignWithLabel: true
                },
            },
            yAxis: {
                type: 'value',
                min: 0
            },
            rotate: {
                min: -90,
                max: 90
            },
            series: [{
                type: 'bar',
                data: chartData,
                label: {
                    position: 'insideBottom',
                    distance: 15,
                    align: 'left',
                    verticalAlign: 'middle',
                    show: true,
                    fontSize: 16,
                    fontWeight: 'bold',
                    rotate: 90,
                    formatter: function(value) {
                        return formatLabelForChartWithPercentage(value.value)
                    }
                },
                itemStyle: {
                    borderRadius: [4, 4, 0, 0],
                },
                color: getPastelColors()
            }, ]
        });
    </script>
</body>

</html>
