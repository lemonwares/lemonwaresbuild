// Keeps the admin/account sidebar at the same scroll position across page loads.
const containers = ['.admin-sidebar-scroll', '.account-sidebar-scroll'];

const restore = () => {
    containers.forEach((selector) => {
        const el = document.querySelector(selector);
        if (!el) return;

        const key = `lemonwares.scroll${selector}`;
        const saved = Number(sessionStorage.getItem(key));
        if (saved) el.scrollTop = saved;

        const save = () => sessionStorage.setItem(key, String(el.scrollTop));
        el.addEventListener('scroll', save, { passive: true });
        el.addEventListener('click', save);
        window.addEventListener('pagehide', save);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', restore);
} else {
    restore();
}
