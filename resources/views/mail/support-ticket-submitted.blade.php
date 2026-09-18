@component('mail::message')
# New support ticket

A customer opened a support ticket from the website.

@component('mail::panel')
**Reference:** {{ $ticket->reference }}

**From:** {{ $ticket->full_name }} &lt;{{ $ticket->email }}&gt;

@if ($ticket->phone)
**Phone:** {{ $ticket->phone }}

@endif
**Category:** {{ $ticket->category }}

**Priority:** {{ $ticket->priority }}

**Subject:** {{ $ticket->subject }}
@endcomponent

{{ $ticket->message }}

Thanks,<br>
**{{ config('site.short_name') }}** website
@endcomponent
