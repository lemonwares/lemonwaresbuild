<x-mail::message>
# Reply on {{ $ticket->reference }}

Hi {{ $ticket->full_name }},

Our team replied to your support request **{{ $ticket->subject }}**:

{{ $replyBody }}

You can reply to this email or open a new ticket from our [support page]({{ route('support') }}).

Thanks,<br>
{{ config('app.name') }} Support
</x-mail::message>
