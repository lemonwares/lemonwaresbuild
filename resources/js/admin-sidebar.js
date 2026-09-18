const STORAGE_KEY = 'lemonwares.admin-sidebar';

const syncToggle = (collapsed) => {
    document.querySelectorAll('[data-admin-sidebar-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        button.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        const label = button.querySelector('.admin-nav-label');
        if (label) {
            label.textContent = collapsed ? 'Expand' : 'Collapse';
        }
    });
};

const applyCollapsed = (collapsed) => {
    document.documentElement.classList.toggle('admin-sidebar-collapsed', collapsed);
    document.querySelectorAll('[data-admin-shell]').forEach((shell) => {
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

document.querySelectorAll('[data-admin-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const next = ! document.documentElement.classList.contains('admin-sidebar-collapsed');
        applyCollapsed(next);

        try {
            window.localStorage.setItem(STORAGE_KEY, next ? 'collapsed' : 'expanded');
        } catch {
            // Ignore storage access issues.
        }
    });
});
