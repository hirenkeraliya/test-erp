@component('mail::message', ['emailLogos' => $emailLogos])

Dear {{ $employee->getFullName() }},

# Failed Automatic Day close

Automatic Day close failed on **{{ now()->format('d/m/Y g:ia') }}** for the {{ $location->name }} because some of the counters are not closed. You can close this manually from the [Day Close]({{ config('app.url') }}/store-manager/day-close).

Thanks,<br>
{{ config('app.name') }}
@endcomponent
