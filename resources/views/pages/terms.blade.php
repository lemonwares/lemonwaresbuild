@extends('layouts.app')

@section('title', __('legal.terms.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('legal.terms.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('legal.eyebrow')"
        :title="__('legal.terms.title')"
        :lede="__('legal.terms.lede')"
        cta-href="#page-content"
        :cta-label="__('legal.read_terms')"
    />

    <x-layout.page-content>
        <div class="prose-page">
            <p>{{ __('legal.last_updated', ['date' => now()->translatedFormat('F j, Y')]) }}</p>

            @foreach (__('legal.terms.sections') as $section)
                <h2>{{ $section['heading'] }}</h2>
                <p>
                    {!! str_replace(
                        ':email',
                        '<a href="mailto:' . e(config('site.email')) . '" class="link">' . e(config('site.email')) . '</a>',
                        e($section['body'])
                    ) !!}
                </p>
            @endforeach
        </div>
    </x-layout.page-content>
@endsection
