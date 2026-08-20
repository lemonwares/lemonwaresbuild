const ACTIVE_TAB = [
    'bg-rose',
    'text-white',
    'shadow-[0_8px_20px_rgba(224,69,69,0.25)]',
];

const IDLE_TAB = [
    'text-black',
    'hover:bg-blush-soft',
];

const ACTIVE_BADGE_SAVE = ['text-white/80'];
const IDLE_BADGE_SAVE = ['text-rose'];
const ACTIVE_BADGE_STANDARD = ['text-white/70'];
const IDLE_BADGE_STANDARD = ['text-on-blush/55'];

function parsePricing(raw) {
    try {
        return JSON.parse(raw || '{}');
    } catch {
        return {};
    }
}

function setTabStyles(tab, active) {
    const badge = tab.querySelector('[data-email-cycle-badge]');
    const isStandard = Number(tab.getAttribute('data-discount') || '0') === 0;

    ACTIVE_TAB.forEach((cls) => tab.classList.toggle(cls, active));
    IDLE_TAB.forEach((cls) => tab.classList.toggle(cls, ! active));
    tab.setAttribute('aria-selected', active ? 'true' : 'false');

    if (! badge) {
        return;
    }

    if (active) {
        IDLE_BADGE_SAVE.forEach((cls) => badge.classList.remove(cls));
        IDLE_BADGE_STANDARD.forEach((cls) => badge.classList.remove(cls));
        (isStandard ? ACTIVE_BADGE_STANDARD : ACTIVE_BADGE_SAVE).forEach((cls) => badge.classList.add(cls));
    } else {
        ACTIVE_BADGE_SAVE.forEach((cls) => badge.classList.remove(cls));
        ACTIVE_BADGE_STANDARD.forEach((cls) => badge.classList.remove(cls));
        (isStandard ? IDLE_BADGE_STANDARD : IDLE_BADGE_SAVE).forEach((cls) => badge.classList.add(cls));
    }
}

function updateCards(root, cycle) {
    const checkoutBase = root.getAttribute('data-checkout-base') || '/email/checkout';

    document.querySelectorAll('[data-email-plan-card]').forEach((card) => {
        const pricing = parsePricing(card.getAttribute('data-pricing'));
        const entry = pricing[cycle];
        if (! entry) {
            return;
        }

        const price = card.querySelector('[data-email-period-price]');
        const meta = card.querySelector('[data-email-cycle-meta]');
        const perMailbox = card.querySelector('[data-email-per-mailbox]');
        const cta = card.querySelector('[data-email-plan-cta]');

        if (price) {
            price.textContent = entry.period_display;
        }
        if (meta) {
            meta.textContent = entry.cycle_meta;
        }
        if (perMailbox) {
            perMailbox.textContent = entry.per_mailbox_line;
        }
        if (cta) {
            const url = new URL(checkoutBase, window.location.origin);
            url.searchParams.set('plan', card.getAttribute('data-plan-key') || '');
            url.searchParams.set('billing_cycle', cycle);
            cta.setAttribute('href', `${url.pathname}${url.search}`);
        }
    });
}

function syncUrl(root, cycle) {
    const plansUrl = root.getAttribute('data-plans-url') || '/email';
    const url = new URL(plansUrl, window.location.origin);
    url.searchParams.set('billing_cycle', cycle);
    url.hash = 'email-plans';
    window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
}

function selectCycle(root, cycle, { updateHistory = true } = {}) {
    if (! cycle) {
        return;
    }

    root.setAttribute('data-selected-cycle', cycle);

    root.querySelectorAll('[data-email-cycle-tab]').forEach((tab) => {
        setTabStyles(tab, tab.getAttribute('data-cycle') === cycle);
    });

    updateCards(root, cycle);

    if (updateHistory) {
        syncUrl(root, cycle);
    }
}

document.querySelectorAll('[data-email-plans]').forEach((root) => {
    root.querySelectorAll('[data-email-cycle-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            selectCycle(root, tab.getAttribute('data-cycle') || '');
        });
    });
});
