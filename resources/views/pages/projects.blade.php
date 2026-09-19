@extends('layouts.app')

@section('title', __('pages.projects.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.projects.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('pages.projects.eyebrow')"
        :title="__('pages.projects.title')"
        :lede="__('pages.projects.lede')"
        cta-href="{{ route('contact') }}"
        :cta-label="__('site.common.contact_us')"
    />

    <x-layout.page-content>
        @if ($projects->isEmpty())
            <div class="mx-auto max-w-2xl rounded-md border border-border bg-white px-6 py-12 text-center shadow-[0_16px_48px_rgba(0,0,0,0.06)] sm:px-10">
                <p class="text-lg font-semibold text-black">{{ __('pages.projects.empty_title') }}</p>
                <p class="mt-2 text-sm text-on-blush/70">{{ __('pages.projects.empty_lede') }}</p>
                <a href="{{ route('case-studies') }}" class="btn btn-primary mt-6 inline-flex">{{ __('pages.projects.cta_cases') }}</a>
            </div>
        @else
            <div class="mx-auto grid max-w-5xl gap-8 sm:grid-cols-2">
                @foreach ($projects as $project)
                    <article class="border-b border-border pb-8">
                        @if ($project->cover_path)
                            <a href="{{ route('projects.show', $project) }}" class="mb-4 block overflow-hidden rounded-md">
                                <img src="{{ $project->coverUrl() }}" alt="" class="aspect-[16/9] w-full object-cover" loading="lazy" />
                            </a>
                        @endif
                        @if ($project->client_name)
                            <p class="text-xs font-semibold uppercase tracking-widest text-on-blush/55">{{ $project->client_name }}</p>
                        @endif
                        <h2 class="mt-3 text-2xl font-bold tracking-tight text-black">
                            <a href="{{ route('projects.show', $project) }}" class="transition hover:text-rose">{{ $project->title }}</a>
                        </h2>
                        @if ($project->summary)
                            <p class="mt-3 text-sm font-light leading-relaxed text-on-blush/75">{{ $project->summary }}</p>
                        @endif
                        <a href="{{ route('projects.show', $project) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-rose">
                            {{ __('pages.projects.view') }}
                            <x-ui.icons.arrow-up-right class="size-4" />
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </x-layout.page-content>
@endsection
