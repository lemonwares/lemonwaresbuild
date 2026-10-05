@component('mail::message')
# {{ __('account.verify_mail_title') }}

{{ __('account.verify_mail_lede') }}

@component('mail::button', ['url' => $url])
{{ __('account.verify_mail_action') }}
@endcomponent

{{ __('account.verify_mail_expire', ['minutes' => $expireMinutes]) }}

{{ config('site.short_name') }}
@endcomponent
