@component('mail::message')
# {{ __('email.credentials_mail_title') }}

{{ __('email.credentials_mail_lede', ['domain' => $domain]) }}

@if (filled($note))
{{ $note }}

@endif
@foreach ($mailboxes as $mailbox)
**{{ $mailbox['address'] }}**  
{{ __('email.credentials_mail_password_label') }}: `{{ $mailbox['password'] }}`

@endforeach
@component('mail::button', ['url' => $webmailUrl])
{{ __('email.open_webmail') }}
@endcomponent

{{ __('email.credentials_mail_change_password') }}

{{ __('email.credentials_mail_order_link') }}: [{{ __('email.order_title') }}]({{ $orderUrl }})

{{ config('site.short_name') }}
@endcomponent
