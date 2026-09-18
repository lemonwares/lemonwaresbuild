/**
 * Admin command-palette search + notifications panel.
 */
function initAdminChrome() {
    const searchOpen = document.querySelector('[data-admin-search-open]');
    const searchModal = document.querySelector('[data-admin-search-modal]');
    const searchInput = document.querySelector('[data-admin-search-input]');
    const searchResults = document.querySelector('[data-admin-search-results]');
    const searchCloseEls = document.querySelectorAll('[data-admin-search-close]');
    const searchUrl = searchModal?.getAttribute('data-search-url');

    const notifOpen = document.querySelector('[data-admin-notif-open]');
    const notifPanel = document.querySelector('[data-admin-notif-panel]');

    if (! searchOpen || ! searchModal || ! searchInput || ! searchResults || ! searchUrl) {
        return;
    }

    let debounceTimer = null;
    let activeIndex = -1;
    let currentItems = [];

    const openSearch = () => {
        searchModal.hidden = false;
        searchModal.classList.add('is-open');
        document.body.classList.add('admin-modal-open');
        searchInput.value = '';
        searchResults.innerHTML = '<p class="admin-search-hint">Type to search customers, orders, leads, tickets, and pages.</p>';
        currentItems = [];
        activeIndex = -1;
        requestAnimationFrame(() => searchInput.focus());
    };

    const closeSearch = () => {
        searchModal.hidden = true;
        searchModal.classList.remove('is-open');
        document.body.classList.remove('admin-modal-open');
    };

    const renderResults = (results) => {
        currentItems = results;
        activeIndex = results.length ? 0 : -1;

        if (! results.length) {
            searchResults.innerHTML = '<p class="admin-search-hint">No matches found.</p>';
            return;
        }

        searchResults.innerHTML = results
            .map(
                (item, index) => `
            <a href="${item.url}" class="admin-search-result${index === 0 ? ' is-active' : ''}" data-admin-search-item="${index}">
                <span class="admin-search-result-type">${item.type || 'Result'}</span>
                <span class="admin-search-result-copy">
                    <span class="admin-search-result-title">${escapeHtml(item.title || '')}</span>
                    <span class="admin-search-result-sub">${escapeHtml(item.subtitle || '')}</span>
                </span>
            </a>
        `,
            )
            .join('');
    };

    const setActive = (index) => {
        const items = searchResults.querySelectorAll('[data-admin-search-item]');
        if (! items.length) return;
        activeIndex = (index + items.length) % items.length;
        items.forEach((el, i) => el.classList.toggle('is-active', i === activeIndex));
        items[activeIndex]?.scrollIntoView({ block: 'nearest' });
    };

    const runSearch = (query) => {
        const q = query.trim();
        if (q.length < 2) {
            searchResults.innerHTML = '<p class="admin-search-hint">Keep typing — at least 2 characters.</p>';
            currentItems = [];
            return;
        }

        searchResults.innerHTML = '<p class="admin-search-hint">Searching…</p>';

        fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((res) => res.json())
            .then((data) => renderResults(Array.isArray(data.results) ? data.results : []))
            .catch(() => {
                searchResults.innerHTML = '<p class="admin-search-hint">Search failed. Try again.</p>';
            });
    };

    searchOpen.addEventListener('click', openSearch);
    searchCloseEls.forEach((el) => el.addEventListener('click', closeSearch));

    searchModal.addEventListener('click', (event) => {
        if (event.target === searchModal) closeSearch();
    });

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => runSearch(searchInput.value), 220);
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSearch();
            return;
        }
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive(activeIndex + 1);
            return;
        }
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(activeIndex - 1);
            return;
        }
        if (event.key === 'Enter' && activeIndex >= 0 && currentItems[activeIndex]) {
            event.preventDefault();
            window.location.href = currentItems[activeIndex].url;
        }
    });

    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            if (searchModal.classList.contains('is-open')) {
                closeSearch();
            } else {
                openSearch();
            }
        }
    });

    if (notifOpen && notifPanel) {
        notifOpen.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = notifPanel.hasAttribute('hidden');
            if (open) {
                notifPanel.removeAttribute('hidden');
                notifOpen.setAttribute('aria-expanded', 'true');
            } else {
                notifPanel.setAttribute('hidden', '');
                notifOpen.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('click', (event) => {
            if (! notifPanel.hasAttribute('hidden') && ! notifPanel.contains(event.target) && event.target !== notifOpen) {
                notifPanel.setAttribute('hidden', '');
                notifOpen.setAttribute('aria-expanded', 'false');
            }
        });
    }
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminChrome);
} else {
    initAdminChrome();
}
