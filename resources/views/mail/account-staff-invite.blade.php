@component('mail::message')
# {{ __('account.staff_invite_mail_title') }}

{{ __('account.staff_invite_mail_lede', ['name' => $ownerName, 'company' => config('site.short_name')]) }}

@component('mail::button', ['url' => $acceptUrl])
{{ __('account.staff_invite_mail_action') }}
@endcomponent

{{ __('account.staff_invite_mail_expire') }}

{{ config('site.short_name') }}
@endcomponent
