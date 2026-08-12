@extends('layouts.app')

@section('title', __('pages.team.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.team.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('site.pages.team_eyebrow')"
        :title="__('site.pages.team_title')"
        :lede="__('site.pages.team_lede')"
        cta-href="#page-content"
        :cta-label="__('site.pages.team_cta')"
        :art="true"
        art-src="images/heroes/team.webp"
    />

    <x-layout.page-content wide>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($members as $member)
                <article class="card-tech p-6 text-center">
                    @if ($member->photo_path)
                        <img
                            src="{{ asset('storage/' . $member->photo_path) }}"
                            alt="{{ $member->name }}"
                            class="mx-auto mb-4 size-16 rounded-full object-cover"
                            loading="lazy"
                        />
                    @else
                        <span class="mx-auto mb-4 inline-flex size-16 items-center justify-center rounded-full bg-blush text-xl font-bold text-rose">
                            {{ \Illuminate\Support\Str::of($member->name)->explode(' ')->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->take(2)->join('') }}
                        </span>
                    @endif

                    <h2 class="mb-1 text-lg font-semibold text-black">{{ $member->name }}</h2>
                    <p class="body-text">{{ $member->role }}</p>
                    @if ($member->quote)
                        <p class="mt-3 italic body-text">"{{ $member->quote }}"</p>
                    @endif
                    @if ($member->bio)
                        <p class="mt-3 body-text">{{ $member->bio }}</p>
                    @endif

                    @if ($member->x_url || $member->linkedin_url || $member->instagram_url || $member->facebook_url)
                        <div class="mt-5 flex items-center justify-center gap-3">
                            @if ($member->x_url)
                                <a href="{{ $member->x_url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $member->name }} on X" class="inline-flex size-9 items-center justify-center rounded-full border border-border text-black transition hover:border-rose hover:text-rose">
                                    <x-ui.icons.x class="size-4" />
                                </a>
                            @endif
                            @if ($member->linkedin_url)
                                <a href="{{ $member->linkedin_url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $member->name }} on LinkedIn" class="inline-flex size-9 items-center justify-center rounded-full border border-border text-black transition hover:border-rose hover:text-rose">
                                    <x-ui.icons.linkedin class="size-4" />
                                </a>
                            @endif
                            @if ($member->instagram_url)
                                <a href="{{ $member->instagram_url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $member->name }} on Instagram" class="inline-flex size-9 items-center justify-center rounded-full border border-border text-black transition hover:border-rose hover:text-rose">
                                    <x-ui.icons.instagram class="size-4" />
                                </a>
                            @endif
                            @if ($member->facebook_url)
                                <a href="{{ $member->facebook_url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $member->name }} on Facebook" class="inline-flex size-9 items-center justify-center rounded-full border border-border text-black transition hover:border-rose hover:text-rose">
                                    <x-ui.icons.facebook class="size-4" />
                                </a>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <article class="card-tech p-6 text-center sm:col-span-2 lg:col-span-3">
                    <p class="body-text">{{ __('pages.team.empty') }}</p>
                </article>
            @endforelse
        </div>

        <p class="mt-10 text-center body-text">
            {{ __('pages.team.need_help') }}
            <a href="{{ route('contact') }}" class="link">{{ __('pages.team.get_in_touch') }}</a>
            {{ __('pages.team.respond_quickly') }}
        </p>
    </x-layout.page-content>
@endsection
