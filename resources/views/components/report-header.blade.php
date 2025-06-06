<div>
    <h3>
        {{ $company->name ?? '' }} ( {{ $company->code ?? '' }} )
    </h3>

    <p>
        Report Name:
        <strong>
            {{ $reportName ?? '' }}
        </strong>
    </p>

    @if ($reportType)
        <p>
            Report Type:
            <strong>
                {{ $reportType }}
            </strong>
        </p>
    @endif

    @if ($filterBy)
        <p>
            Filter Type:
            <strong>
                {{ $filterBy ?? '' }}
            </strong>
        </p>
    @endif

    @if ($dateRange)
        <p>
            Records From
            <strong>
                {{ $dateRange[0] }}
            </strong>
            To
            <strong>
                {{ $dateRange[1] }}
            </strong>
            @if(count($dateRange) > 3)
                &nbsp; and Records From
                <strong>
                    {{ $dateRange[2] }}
                </strong>
                To
                <strong>
                    {{ $dateRange[3] }}
                </strong>
            @endif
        </p>
    @endif

    <p>
        Date:
        <strong>
            {{ $date }}
        </strong>
    </p>
</div>
