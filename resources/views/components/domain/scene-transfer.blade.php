@props([])

<div {{ $attributes->class(['domain-scene domain-scene-transfer']) }} aria-hidden="true">
    <div class="domain-scene-glow"></div>
    <div class="domain-scene-orb is-a"></div>
    <div class="domain-scene-orb is-b"></div>

    <div class="domain-scene-panel is-from domain-scene-float">
        <p class="domain-scene-panel-label">Current registrar</p>
        <p class="domain-scene-panel-domain">yourbrand.net</p>
        <p class="domain-scene-panel-meta">Unlocked · EPP ready</p>
    </div>

    <div class="domain-scene-arrow domain-scene-float-delay" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <div class="domain-scene-panel is-to domain-scene-float">
        <p class="domain-scene-panel-label">LemonWares</p>
        <p class="domain-scene-panel-domain">yourbrand.net</p>
        <p class="domain-scene-panel-meta">DNS · billing · support</p>
    </div>
</div>
