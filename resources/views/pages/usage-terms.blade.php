@extends('layouts.app')

@section('title', __('legal.usage.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('legal.usage.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('legal.eyebrow')"
        :title="__('legal.usage.title')"
        :lede="__('legal.usage.lede')"
        cta-href="#page-content"
        :cta-label="__('legal.read_guidelines')"
        :art="true"
        art-src="images/heroes/legal.webp"
    />

    <x-layout.page-content>
        <div class="prose-page">
            <p>{{ __('legal.last_updated', ['date' => now()->translatedFormat('F j, Y')]) }}</p>

            @foreach (__('legal.usage.sections') as $section)
                <h2>{{ $section['heading'] }}</h2>
                @if (! empty($section['items']))
                    <ul>
                        @foreach ($section['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>
                        {!! str_replace(
                            ':email',
                            '<a href="mailto:' . e(config('site.email')) . '" class="link">' . e(config('site.email')) . '</a>',
                            e($section['body'])
                        ) !!}
                    </p>
                @endif
            @endforeach
        </div>
    </x-layout.page-content>
@endsection
