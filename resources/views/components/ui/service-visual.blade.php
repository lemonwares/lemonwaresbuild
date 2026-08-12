@props([
    'icon',
    'tone' => 'blush',
    'label',
])

@php
    $tones = [
        'slate' => 'bg-slate text-white',
        'rose' => 'bg-rose text-white',
        'blush' => 'bg-blush text-rose',
    ];
@endphp

<div {{ $attributes->class('service-visual ' . ($tones[$tone] ?? $tones['blush'])) }}>
    <div class="service-visual-glow" aria-hidden="true"></div>
    <x-dynamic-component :component="'ui.icons.'.$icon" class="service-visual-icon" />
    <span class="service-visual-label">{{ $label }}</span>
</div>
