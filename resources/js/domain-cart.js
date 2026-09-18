const root = document.querySelector('[data-site-cart], [data-domain-cart]');

if (root) {
    const csrf = root.dataset.csrf || '';
    const template = root.dataset.updateUrlTemplate || '';

    root.querySelectorAll('[data-cart-item]').forEach((itemEl) => {
        const select = itemEl.querySelector('[data-cart-period]');
        if (! select) {
            return;
        }

        select.addEventListener('change', async () => {
            const itemId = itemEl.dataset.itemId;
            const url = template.replace('__ID__', encodeURIComponent(itemId));
            select.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ reg_period: Number(select.value) }),
                });
                const payload = await response.json();
                if (! response.ok || ! payload?.ok) {
                    return;
                }

                const price = itemEl.querySelector('[data-item-price]');
                const periodLabel = itemEl.querySelector('[data-item-period-label]');
                if (price && payload.item?.display) {
                    price.textContent = payload.item.display;
                }
                if (periodLabel && payload.item?.period_label) {
                    periodLabel.textContent = payload.item.period_label;
                }

                const total = root.querySelector('[data-cart-total]');
                if (total && payload.totals?.display) {
                    total.textContent = payload.totals.display;
                }

                window.dispatchEvent(new CustomEvent('cart:updated', {
                    detail: { count: payload.count ?? payload.totals?.count },
                }));
                window.dispatchEvent(new CustomEvent('domain-cart:updated', {
                    detail: { count: payload.count ?? payload.totals?.count },
                }));
            } finally {
                select.disabled = false;
            }
        });
    });
}

const syncCartBadge = (count) => {
    const link = document.querySelector('[data-domain-cart-link], [data-site-cart-link]');
    const badge = document.querySelector('[data-domain-cart-count], [data-site-cart-count]');
    const value = Number(count || 0);
    const visible = value > 0;

    if (link) {
        link.classList.toggle('hidden', ! visible);
    }

    if (badge) {
        badge.textContent = visible ? String(value) : '';
        badge.classList.toggle('hidden', ! visible);
    }
};

window.addEventListener('cart:updated', (event) => {
    syncCartBadge(event.detail?.count);
});

window.addEventListener('domain-cart:updated', (event) => {
    syncCartBadge(event.detail?.count);
});
