const STORAGE_KEY = 'lemonwares.theme';

const readStoredTheme = () => {
    try {
        const saved = window.localStorage.getItem(STORAGE_KEY);
        if (saved === 'dark' || saved === 'light') {
            return saved;
        }
    } catch {
        // Ignore storage access issues.
    }

    return 'light';
};

const syncLogos = (theme) => {
    document.querySelectorAll('[data-logo-light][data-logo-dark]').forEach((img) => {
        const src = theme === 'dark'
            ? img.getAttribute('data-logo-dark')
            : img.getAttribute('data-logo-light');
        if (src) {
            img.setAttribute('src', src);
        }
    });
};

const syncToggles = (theme) => {
    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        const label = theme === 'dark'
            ? toggle.getAttribute('data-label-light')
            : toggle.getAttribute('data-label-dark');
        if (label) {
            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);
        }
        toggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    });
};

export const applyTheme = (theme) => {
    const next = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', next);
    syncLogos(next);
    syncToggles(next);
    return next;
};

export const getTheme = () => (
    document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'
);

applyTheme(readStoredTheme());

document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const next = applyTheme(getTheme() === 'dark' ? 'light' : 'dark');

        try {
            window.localStorage.setItem(STORAGE_KEY, next);
        } catch {
            // Ignore storage access issues.
        }
    });
});
