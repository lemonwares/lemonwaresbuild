@props([])

<div {{ $attributes->class(['domain-scene domain-scene-search']) }} aria-hidden="true">
    <div class="domain-scene-glow"></div>
    <div class="domain-scene-orb is-a"></div>
    <div class="domain-scene-orb is-b"></div>

    <div class="domain-scene-browser domain-scene-float">
        <div class="domain-scene-browser-bar">
            <span></span><span></span><span></span>
            <div class="domain-scene-url">lemonwares.com/domain</div>
        </div>
        <div class="domain-scene-browser-body">
            <div class="domain-scene-search-bar">
                <span class="domain-scene-search-icon"></span>
                <span class="domain-scene-search-text">yourbrand<span class="domain-scene-caret"></span></span>
                <span class="domain-scene-search-btn">Go</span>
            </div>
            <div class="domain-scene-result-row">
                <span class="domain-scene-dot is-live"></span>
                <span>yourbrand.com</span>
                <strong>Available</strong>
            </div>
        </div>
    </div>

    <div class="domain-scene-price domain-scene-float-delay">
        <span>From</span>
        <strong>₦24,996</strong>
    </div>
</div>
