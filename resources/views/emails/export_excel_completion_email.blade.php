@component('mail::message')
    Hi,

    Your request for exporting records via Excel at {{ $exportRecord->created_at->format('d/m/Y H:i:s') }} is completed
    successfully.

    Please find the excel file attached to this email.

    Thanks,
    {{ config('app.name') }}
@endcomponent
