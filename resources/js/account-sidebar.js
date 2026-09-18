const STORAGE_KEY = 'lemonwares.account-sidebar';

const syncToggle = (collapsed) => {
    document.querySelectorAll('[data-account-sidebar-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        button.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        const label = button.querySelector('.account-nav-label');
        if (label) {
            label.textContent = collapsed ? 'Expand' : 'Collapse';
        }
    });
};

const applyCollapsed = (collapsed) => {
    document.documentElement.classList.toggle('account-sidebar-collapsed', collapsed);
    document.querySelectorAll('[data-account-shell]').forEach((shell) => {
        shell.classList.toggle('is-collapsed', collapsed);
    });
    syncToggle(collapsed);
};

const readCollapsed = () => {
    try {
        return window.localStorage.getItem(STORAGE_KEY) === 'collapsed';
    } catch {
        return false;
    }
};

applyCollapsed(readCollapsed());

document.querySelectorAll('[data-account-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const next = ! document.documentElement.classList.contains('account-sidebar-collapsed');
        applyCollapsed(next);

        try {
            window.localStorage.setItem(STORAGE_KEY, next ? 'collapsed' : 'expanded');
        } catch {
            // Ignore storage access issues.
        }
    });
});

const drawer = document.querySelector('[data-account-drawer]');
const overlay = document.querySelector('[data-account-drawer-overlay]');
const openButtons = document.querySelectorAll('[data-account-nav-open]');
const closeButtons = document.querySelectorAll('[data-account-nav-close]');

const setDrawerOpen = (open) => {
    if (! drawer) {
        return;
    }

    drawer.classList.toggle('is-open', open);
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    overlay?.classList.toggle('is-open', open);
    document.body.classList.toggle('account-drawer-open', open);

    openButtons.forEach((button) => {
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
};

openButtons.forEach((button) => {
    button.addEventListener('click', () => setDrawerOpen(true));
});

closeButtons.forEach((button) => {
    button.addEventListener('click', () => setDrawerOpen(false));
});

overlay?.addEventListener('click', () => setDrawerOpen(false));

drawer?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setDrawerOpen(false));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setDrawerOpen(false);
    }
});
