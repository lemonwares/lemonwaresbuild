@php
    $reviews = config('reviews.items');
    $rating = config('reviews.rating');
    $total = config('reviews.total');
@endphp

<div {{ $attributes->class('reviews-carousel') }} data-reviews-carousel>
    <div class="reviews-carousel-track" data-reviews-track>
        @foreach ($reviews as $index => $review)
            <div
                class="reviews-carousel-slide @if ($index === 0) is-active @endif"
                data-reviews-slide
                aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
            >
                <x-ui.review-card
                    :initials="$review['initials']"
                    :name="$review['name']"
                    :date="$review['date']"
                    :rating="$review['rating']"
                    :text="$review['text']"
                />
            </div>
        @endforeach
    </div>

    <div class="mt-4 flex items-center justify-between gap-4">
        <p class="text-sm font-light text-black/60">
            Rated {{ $rating }} of 5 · {{ $total }} reviews on Google
        </p>

        <div class="flex items-center gap-2">
            <button type="button" class="reviews-carousel-btn" data-reviews-prev aria-label="Previous review">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" />
                </svg>
            </button>

            <div class="flex items-center gap-1.5" data-reviews-dots role="tablist" aria-label="Review slides">
                @foreach ($reviews as $index => $review)
                    <button
                        type="button"
                        @class(['reviews-carousel-dot', 'is-active' => $index === 0])
                        data-reviews-dot="{{ $index }}"
                        role="tab"
                        aria-label="Review {{ $index + 1 }}"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                    ></button>
                @endforeach
            </div>

            <button type="button" class="reviews-carousel-btn" data-reviews-next aria-label="Next review">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>
        </div>
    </div>
</div>
