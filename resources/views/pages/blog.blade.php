@extends('layouts.app')

@section('title', __('site.nav.blog') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.blog.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('pages.blog.eyebrow')"
        :title="__('pages.blog.title')"
        :lede="__('pages.blog.lede')"
        cta-href="{{ route('contact') }}"
        :cta-label="__('site.common.contact_us')"
    />

    <x-layout.page-content>
        <div class="mx-auto max-w-2xl rounded-md border border-border bg-white px-6 py-12 text-center shadow-[0_16px_48px_rgba(0,0,0,0.06)] sm:px-10">
            <p class="text-lg font-semibold text-black">{{ __('pages.blog.empty_title') }}</p>
            <p class="mt-2 text-sm text-on-blush/70">{{ __('pages.blog.empty_lede') }}</p>
            <a href="{{ route('contact') }}" class="btn btn-primary mt-6 inline-flex">{{ __('site.common.contact_us') }}</a>
        </div>
    </x-layout.page-content>
@endsection
