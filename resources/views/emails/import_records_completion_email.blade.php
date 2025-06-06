@component('mail::message', ['emailLogos' => $emailLogos])

Hi,

Your request for importing records at {{ $importRecord->created_at->format('d/m/Y H:i:s') }} is completed successfully. We have imported {{ $importRecord->records_imported }} records.

@if ($importRecord->records_failed)
{{ $importRecord->records_failed }} of the records could not be imported. A file containing those records is attached with this email. Please check the "Failed Reasons" column which contains reason(s) for failure.
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
