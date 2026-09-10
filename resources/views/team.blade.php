@extends('layouts.app')

@section('title', __('pages.team.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.team.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('site.pages.team_eyebrow')"
        :title="__('site.pages.team_title')"
        :lede="__('site.pages.team_lede')"
        cta-href="#team-grid"
        :cta-label="__('site.pages.team_cta')"
    />

    <section id="team-grid" class="border-t bg-white" style="border-color:var(--color-border);">
        <div class="container-page py-16 sm:py-20">

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($members as $member)
                    <article class="group relative flex flex-col overflow-hidden rounded-2xl border transition hover:-translate-y-0.5 hover:shadow-lg"
                             style="border-color:var(--color-border); background:var(--color-surface);">

                        {{-- Avatar / photo --}}
                        <div class="flex items-center gap-4 border-b p-5"
                             style="border-color:var(--color-border); background:var(--color-surface-2);">
                            @if ($member->photo_path)
                                <img src="{{ asset('storage/' . $member->photo_path) }}"
                                     alt="{{ $member->name }}"
                                     class="size-14 rounded-full object-cover ring-2"
                                     style="ring-color:var(--color-border);"
                                     loading="lazy">
                            @else
                                <span class="inline-flex size-14 shrink-0 items-center justify-center rounded-full text-lg font-bold text-white"
                                      style="background:var(--color-red);">
                                    {{ \Illuminate\Support\Str::of($member->name)->explode(' ')->map(fn($p) => \Illuminate\Support\Str::substr($p,0,1))->take(2)->join('') }}
                                </span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold" style="color:var(--color-ink);">{{ $member->name }}</p>
                                <p class="mt-0.5 truncate text-xs font-medium" style="color:var(--color-red);">{{ $member->role }}</p>
                            </div>
                        </div>

                        {{-- Body --}}
                        <div class="flex flex-1 flex-col gap-3 p-5">
                            @if ($member->quote)
                                <p class="text-sm font-light italic leading-relaxed" style="color:var(--color-ink-2);">
                                    "{{ $member->quote }}"
                                </p>
                            @endif
                            @if ($member->bio)
                                <p class="text-sm font-light leading-relaxed" style="color:var(--color-ink-3);">
                                    {{ $member->bio }}
                                </p>
                            @endif
                        </div>

                        {{-- Social links --}}
                        @if ($member->x_url || $member->linkedin_url || $member->instagram_url || $member->facebook_url)
                            <div class="flex items-center gap-2 border-t px-5 py-4"
                                 style="border-color:var(--color-border);">
                                @foreach ([
                                    ['url' => $member->x_url,         'icon' => 'x',         'label' => $member->name . ' on X'],
                                    ['url' => $member->linkedin_url,  'icon' => 'linkedin',  'label' => $member->name . ' on LinkedIn'],
                                    ['url' => $member->instagram_url, 'icon' => 'instagram', 'label' => $member->name . ' on Instagram'],
                                    ['url' => $member->facebook_url,  'icon' => 'facebook',  'label' => $member->name . ' on Facebook'],
                                ] as $social)
                                    @if ($social['url'])
                                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                                           aria-label="{{ $social['label'] }}"
                                           class="inline-flex size-8 items-center justify-center rounded-full border transition hover:border-red hover:text-red"
                                           style="border-color:var(--color-border); color:var(--color-ink-3);">
                                            <x-dynamic-component :component="'ui.icons.' . $social['icon']" class="size-3.5" />
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-2xl border p-8 text-center sm:col-span-2 lg:col-span-3"
                         style="border-color:var(--color-border); background:var(--color-surface-2);">
                        <p class="body-text">{{ __('pages.team.empty') }}</p>
                    </div>
                @endforelse
            </div>

            {{-- Help CTA --}}
            <p class="mt-12 text-center text-sm font-light" style="color:var(--color-ink-3);">
                {{ __('pages.team.need_help') }}
                <a href="{{ route('contact') }}"
                   class="font-semibold transition hover:underline"
                   style="color:var(--color-red);">{{ __('pages.team.get_in_touch') }}</a>
                {{ __('pages.team.respond_quickly') }}
            </p>

        </div>
    </section>
@endsection
