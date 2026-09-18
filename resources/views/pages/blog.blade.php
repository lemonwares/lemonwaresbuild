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
        @if ($posts->isEmpty())
            <div class="mx-auto max-w-2xl rounded-md border border-border bg-white px-6 py-12 text-center shadow-[0_16px_48px_rgba(0,0,0,0.06)] sm:px-10">
                <p class="text-lg font-semibold text-black">{{ __('pages.blog.empty_title') }}</p>
                <p class="mt-2 text-sm text-on-blush/70">{{ __('pages.blog.empty_lede') }}</p>
                <a href="{{ route('contact') }}" class="btn btn-primary mt-6 inline-flex">{{ __('site.common.contact_us') }}</a>
            </div>
        @else
            <div class="mx-auto grid max-w-5xl gap-8 sm:grid-cols-2">
                @foreach ($posts as $post)
                    <article class="border-b border-border pb-8">
                        @if ($post->cover_path)
                            <a href="{{ route('blog.show', $post) }}" class="mb-4 block overflow-hidden rounded-md">
                                <img src="{{ asset('storage/' . $post->cover_path) }}" alt="" class="aspect-[16/9] w-full object-cover" loading="lazy" />
                            </a>
                        @endif
                        <p class="text-xs font-semibold uppercase tracking-widest text-on-blush/55">
                            {{ $post->published_at?->timezone(config('app.timezone'))->format('d M Y') }}
                            @if ($post->author)
                                · {{ $post->author->name }}
                            @endif
                        </p>
                        <h2 class="mt-3 text-2xl font-bold tracking-tight text-black">
                            <a href="{{ route('blog.show', $post) }}" class="transition hover:text-rose">{{ $post->title }}</a>
                        </h2>
                        @if ($post->excerpt)
                            <p class="mt-3 text-sm font-light leading-relaxed text-on-blush/75">{{ $post->excerpt }}</p>
                        @endif
                        <a href="{{ route('blog.show', $post) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-rose">
                            {{ __('pages.blog.read_more') }}
                            <x-ui.icons.arrow-up-right class="size-4" />
                        </a>
                    </article>
                @endforeach
            </div>

            @if ($posts->hasPages())
                <div class="mx-auto mt-12 max-w-5xl">{{ $posts->links() }}</div>
            @endif
        @endif
    </x-layout.page-content>
@endsection
