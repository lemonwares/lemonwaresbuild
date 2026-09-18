@component('mail::message')
# {{ __('pages.support_page.ack_title') }}

{{ __('pages.support_page.ack_body', ['name' => $ticket->full_name, 'reference' => $ticket->reference]) }}

@component('mail::panel')
**{{ __('pages.support_page.ticket_reference') }}:** {{ $ticket->reference }}

**{{ __('pages.support_page.subject') }}:** {{ $ticket->subject }}

**{{ __('pages.support_page.category') }}:** {{ __('pages.support_page.categories.'.$ticket->category) }}

**Priority:** {{ __('pages.support_page.priorities.'.$ticket->priority) }}
@endcomponent

@component('mail::button', ['url' => rtrim((string) config('app.url'), '/').'/support'])
{{ __('pages.support_page.cta') }}
@endcomponent

{{ __('pages.support_page.ack_footer') }}

Thanks,<br>
**{{ config('site.short_name') }}**
@endcomponent
