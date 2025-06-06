<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.4.3/echarts.min.js"></script>
    <script type="text/javascript" src="{{ asset('build/js/chartHelper.js') }}"></script>
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
        @if ($locations)
            {{ $locations->getNamesWithCodes }}
        @else
            All Stores
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
            @forelse($saleThroughDataByColors as $saleThroughDataByColor)
                <tr class="page-break-inside-avoid">
                    <td>
                        {{ $saleThroughDataByColor['name'] }}
                    </td>

                    <td class="text-right">
                        {{ $saleThroughDataByColor['received'] }}
                    </td>

                    <td class="text-right">
                        {{ $saleThroughDataByColor['sold'] }}
                    </td>

                    <td class="text-right">
                        {{ $saleThroughDataByColor['returned'] }}
                    </td>

                    <td class="text-right">
                        {{ $saleThroughDataByColor['received'] - ((float) $saleThroughDataByColor['sold'] - (float) $saleThroughDataByColor['returned']) }}
                    </td>

                    <td class="text-right">
                        @if ((float) $saleThroughDataByColor['received'] === (float) 0)
                            0.00
                        @else
                            @truncateDecimal((($saleThroughDataByColor['sold'] - (float) $saleThroughDataByColor['returned']) * 100) / (float) $saleThroughDataByColor['received'])
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
                </tr>
            @endforelse

            <tr class="page-break-inside-avoid text-bold">
                <td>
                    <b>
                        Grand Total
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleThroughTotalDataByColors['received'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleThroughTotalDataByColors['sold'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleThroughTotalDataByColors['returned'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleThroughTotalDataByColors['remaining'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleThroughTotalDataByColors['sale_through'] }}
                    </b>
                </td>
            </tr>
        </tbody>
    </table>

    @if (count($chartRecords) > 0)
        <div class="page-break-inside-avoid">
            <h2> Pie Chart </h2>
            <div id="pie-chart" style="width: 100%; height:400px;"></div>
        </div>

        <div class="page-break-inside-avoid">
            <h2> Bar Chart </h2>
            <div id="bar-chart" style="width: 100%; height:400px;"></div>
        </div>
    @endif

    <script type="text/javascript">
        var pieChartElement = echarts.init(document.getElementById('pie-chart'));
        var barChartElement = echarts.init(document.getElementById('bar-chart'));

        var chartLabel = {!! isset($chartRecords['labels']) ? json_encode($chartRecords['labels']) : '[]' !!};
        var chartData = {!! isset($chartRecords['sale_through']) ? json_encode($chartRecords['sale_through']) : '[]' !!};

        chartLabel = chartLabel.length > 0 ? chartLabel : ['No Records'];
        chartData = chartData.length > 0 ? chartData : [10];

        const preparedRecords = [];
        for (const key in chartLabel) {
            preparedRecords.push({
                name: chartLabel[key],
                value: chartData[key]
            });
        }

        pieChartElement.setOption({
            tooltip: {
                valueFormatter: function(value) {
                    return formatLabelForChartWithPercentage(value.value)
                }
            },
            legend: {
                data: chartLabel,
                top: -6,
                type: 'scroll',
            },
            grid: {},
            series: [{
                scale: true,
                scaleSize: 20,
                type: 'pie',
                data: preparedRecords,
                label: {
                    fontSize: 13,
                    fontWeight: 'bold',
                    formatter: function(value) {
                        return formatLabelAndValueForChartWithPercentage(value.name, value.value);
                    }
                },
                itemStyle: {
                    shadowBlur: 5,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                },
                color: getPastelColors()
            }, ],
        });

        barChartElement.setOption({
            legend: {
                data: 'Sell Through (%)'
            },
            xAxis: {
                data: chartLabel,
                axisLabel: {
                    formatter: function(value) {
                        return labelsInTruncateForm(value)
                    },
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
                    fontWeight: 'bolder',
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
