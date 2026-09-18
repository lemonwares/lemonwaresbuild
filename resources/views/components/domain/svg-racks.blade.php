@props([
    'side' => 'left',
])

<svg
    {{ $attributes->class(['domain-svg-racks', 'is-right' => $side === 'right']) }}
    viewBox="0 0 180 280"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
>
    <g class="domain-svg-rack" transform="translate(20 20)">
        <path d="M40 20 L120 0 L120 60 L40 80 Z" stroke="currentColor" stroke-width="1.5" fill="color-mix(in srgb, currentColor 8%, transparent)" />
        <path d="M40 80 L120 60 L140 72 L60 92 Z" stroke="currentColor" stroke-width="1.5" fill="color-mix(in srgb, currentColor 12%, transparent)" />
        <path d="M40 20 L60 32 L60 92 L40 80 Z" stroke="currentColor" stroke-width="1.5" fill="color-mix(in srgb, currentColor 6%, transparent)" />
        <circle class="domain-svg-dot" cx="55" cy="45" r="3" fill="var(--color-rose)" />
        <circle class="domain-svg-dot domain-svg-dot-delay" cx="55" cy="58" r="3" fill="var(--color-rose)" />
        <circle class="domain-svg-dot" cx="55" cy="71" r="3" fill="var(--color-rose)" />
    </g>
    <g class="domain-svg-rack domain-svg-rack-mid" transform="translate(10 100)">
        <path d="M30 20 L110 0 L110 55 L30 75 Z" stroke="currentColor" stroke-width="1.5" fill="color-mix(in srgb, currentColor 8%, transparent)" />
        <path d="M30 75 L110 55 L130 66 L50 86 Z" stroke="currentColor" stroke-width="1.5" fill="color-mix(in srgb, currentColor 12%, transparent)" />
        <path d="M30 20 L50 31 L50 86 L30 75 Z" stroke="currentColor" stroke-width="1.5" fill="color-mix(in srgb, currentColor 6%, transparent)" />
        <circle class="domain-svg-dot domain-svg-dot-delay" cx="45" cy="42" r="3" fill="var(--color-rose)" />
        <circle class="domain-svg-dot" cx="45" cy="55" r="3" fill="var(--color-rose)" />
    </g>
    <g class="domain-svg-rack domain-svg-rack-low" transform="translate(30 175)">
        <path d="M20 20 L90 5 L90 50 L20 65 Z" stroke="currentColor" stroke-width="1.5" fill="color-mix(in srgb, currentColor 8%, transparent)" />
        <path d="M20 65 L90 50 L108 60 L38 75 Z" stroke="currentColor" stroke-width="1.5" fill="color-mix(in srgb, currentColor 12%, transparent)" />
        <path d="M20 20 L38 30 L38 75 L20 65 Z" stroke="currentColor" stroke-width="1.5" fill="color-mix(in srgb, currentColor 6%, transparent)" />
        <circle class="domain-svg-dot" cx="33" cy="40" r="3" fill="var(--color-rose)" />
        <circle class="domain-svg-dot domain-svg-dot-delay" cx="33" cy="52" r="3" fill="var(--color-rose)" />
    </g>
</svg>
