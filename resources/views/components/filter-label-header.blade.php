@if (count($getFilterLabels) > 0)
    <b>Filters: </b>
    <div style="display: flex; gap: 20px; width: 100%">
        @foreach ($getFilterLabels as $key => $value)
            <p>
                <b>{{ $key }}</b>: {{ $value }}
            </p>
        @endforeach
    </div>
@endIf