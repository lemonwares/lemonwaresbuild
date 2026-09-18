/**
 * Dynamic WHMCS product mapping rows (add / remove / seed from catalog).
 */
function initWhmcsMappings() {
    const root = document.querySelector('[data-whmcs-mappings]');
    if (! root) return;

    const tbody = root.querySelector('[data-whmcs-mapping-rows]');
    const template = root.querySelector('[data-whmcs-mapping-template]');
    const addBtn = root.querySelector('[data-whmcs-mapping-add]');

    if (! tbody || ! template || ! addBtn) return;

    const reindex = () => {
        tbody.querySelectorAll('[data-whmcs-mapping-row]').forEach((row, index) => {
            row.querySelectorAll('input').forEach((input) => {
                const name = input.getAttribute('name');
                if (! name) return;
                input.setAttribute(
                    'name',
                    name.replace(/mappings\[(?:\d+|__INDEX__)\]/, `mappings[${index}]`),
                );
            });
        });
    };

    const appendRow = (values = {}) => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(tbody.children.length));
        const wrap = document.createElement('tbody');
        wrap.innerHTML = html.trim();
        const row = wrap.firstElementChild;
        if (! row) return;

        if (values.plan_slug) row.querySelector('[data-whmcs-plan]').value = values.plan_slug;
        if (values.spec_key) row.querySelector('[data-whmcs-spec]').value = values.spec_key;
        if (values.whmcs_pid) row.querySelector('[data-whmcs-pid]').value = values.whmcs_pid;
        const active = row.querySelector('[data-whmcs-active]');
        if (active) active.checked = values.is_active !== false;

        tbody.appendChild(row);
        reindex();
    };

    addBtn.addEventListener('click', () => appendRow());

    tbody.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-whmcs-mapping-remove]');
        if (! btn) return;
        const row = btn.closest('[data-whmcs-mapping-row]');
        if (! row) return;

        if (tbody.querySelectorAll('[data-whmcs-mapping-row]').length <= 1) {
            row.querySelectorAll('input[type="text"], input[type="number"]').forEach((input) => {
                input.value = '';
            });
            const active = row.querySelector('[data-whmcs-active]');
            if (active) active.checked = true;
            return;
        }

        row.remove();
        reindex();
    });

    root.querySelectorAll('[data-whmcs-mapping-seed]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const planSlug = btn.getAttribute('data-plan-slug') || '';
            let specs = [];
            try {
                specs = JSON.parse(btn.getAttribute('data-specs') || '[]');
            } catch (e) {
                specs = [];
            }

            if (! Array.isArray(specs) || ! specs.length) {
                appendRow({ plan_slug: planSlug, spec_key: '', is_active: true });
                return;
            }

            const existing = new Set(
                Array.from(tbody.querySelectorAll('[data-whmcs-mapping-row]')).map((row) => {
                    const plan = (row.querySelector('[data-whmcs-plan]')?.value || '').toLowerCase().trim();
                    const spec = (row.querySelector('[data-whmcs-spec]')?.value || '').toLowerCase().trim();
                    return `${plan}:${spec}`;
                }),
            );

            // Clear a single blank starter row if present.
            const onlyBlank =
                tbody.querySelectorAll('[data-whmcs-mapping-row]').length === 1 &&
                !(tbody.querySelector('[data-whmcs-plan]')?.value || '').trim() &&
                !(tbody.querySelector('[data-whmcs-spec]')?.value || '').trim() &&
                !(tbody.querySelector('[data-whmcs-pid]')?.value || '').trim();
            if (onlyBlank) {
                tbody.innerHTML = '';
            }

            specs.forEach((spec) => {
                const key = `${planSlug}:${(spec.key || '').toLowerCase()}`;
                if (existing.has(key)) return;
                appendRow({
                    plan_slug: planSlug,
                    spec_key: spec.key || '',
                    is_active: true,
                });
                existing.add(key);
            });
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWhmcsMappings);
} else {
    initWhmcsMappings();
}
