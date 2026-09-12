/**
 * Advancells HRMS — Premium Frontend Application JS
 */

/* ============================================================
   PAGE LOADER
   ============================================================ */
(function () {
    const loader = document.getElementById('page-loader');
    if (!loader) return;

    // Hide loader once the page is fully ready
    function hideLoader() {
        loader.classList.add('hide');
        setTimeout(() => { loader.style.display = 'none'; }, 500);
    }

    if (document.readyState === 'complete') {
        setTimeout(hideLoader, 200);
    } else {
        window.addEventListener('load', () => setTimeout(hideLoader, 200));
    }

    // Show loader on every navigation away from page
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript') || href.startsWith('mailto') || link.getAttribute('target') === '_blank') return;
        // Only show for same-origin non-anchor links
        if (href.startsWith('http') && !href.startsWith(window.location.origin)) return;
        loader.style.display = 'flex';
        loader.classList.remove('hide');
    });

    document.addEventListener('submit', function (e) {
        if (e.target.tagName === 'FORM' && e.target.method !== 'dialog') {
            loader.style.display = 'flex';
            loader.classList.remove('hide');
        }
    });
})();


/* ============================================================
   PREMIUM CONFIRM DIALOG (replaces browser confirm())
   ============================================================ */
let _agConfirmCallback = null;

/**
 * Show a premium confirm dialog.
 * @param {string} message   - Body message
 * @param {object} opts      - { title, okLabel, cancelLabel, type: 'danger'|'warning'|'logout'|'info' }
 * @returns {Promise<boolean>}
 */
function agConfirm(message, opts = {}) {
    return new Promise((resolve) => {
        _agConfirmCallback = resolve;

        const backdrop   = document.getElementById('agy-confirm-backdrop');
        const iconWrap   = document.getElementById('agy-confirm-icon');
        const iconI      = document.getElementById('agy-confirm-icon-i');
        const titleEl    = document.getElementById('agy-confirm-title');
        const msgEl      = document.getElementById('agy-confirm-message');
        const okBtn      = document.getElementById('agy-confirm-ok');
        const cancelBtn  = document.getElementById('agy-confirm-cancel');

        const type = opts.type || 'danger';
        const title = opts.title || (type === 'logout' ? 'Sign Out?' : 'Are you sure?');
        const okLabel = opts.okLabel || (type === 'logout' ? 'Sign Out' : 'Confirm');
        const cancelLabel = opts.cancelLabel || 'Cancel';

        const iconMap = {
            danger:  'fa-triangle-exclamation',
            warning: 'fa-triangle-exclamation',
            logout:  'fa-arrow-right-from-bracket',
            info:    'fa-circle-info',
        };

        // Reset and apply type
        iconWrap.className = 'icon-circle ' + type;
        iconI.className = 'fa-solid ' + (iconMap[type] || 'fa-triangle-exclamation');
        okBtn.className = type;

        titleEl.textContent   = title;
        msgEl.textContent     = message || 'This action cannot be undone.';
        okBtn.textContent     = okLabel;
        cancelBtn.textContent = cancelLabel;

        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';

        // Animate dialog in
        const dialog = document.getElementById('agy-confirm-dialog');
        dialog.style.animation = 'none';
        void dialog.offsetWidth; // reflow
        dialog.style.animation = '';
    });
}

function agConfirmResolve(value) {
    const backdrop = document.getElementById('agy-confirm-backdrop');
    if (!backdrop) return;
    backdrop.classList.remove('open');
    document.body.style.overflow = '';
    if (typeof _agConfirmCallback === 'function') {
        _agConfirmCallback(value);
        _agConfirmCallback = null;
    }
}

// Close on backdrop click (outside dialog)
document.addEventListener('click', function (e) {
    if (e.target && e.target.id === 'agy-confirm-backdrop') {
        agConfirmResolve(false);
    }
});

// Close on Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const backdrop = document.getElementById('agy-confirm-backdrop');
        if (backdrop && backdrop.classList.contains('open')) {
            agConfirmResolve(false);
        }
    }
});

/**
 * Legacy-compatible confirmAction() — now async with premium UI.
 * Usage in HTML: onclick="return confirmAction('msg')" still works
 * via a submit-event trick, but for non-form uses a Promise is returned.
 */
function confirmAction(message, opts) {
    // We need a synchronous return for inline onclick="return confirmAction(...)"
    // So we intercept by finding the nearest form/button and handling submit.
    // For full async support, prefer agConfirm() directly.
    agConfirm(message, opts || { type: 'warning', title: 'Confirm Action' }).then(function (ok) {
        if (ok && window._pendingConfirmElement) {
            const el = window._pendingConfirmElement;
            window._pendingConfirmElement = null;
            if (el.tagName === 'FORM') {
                el.removeEventListener('submit', _confirmSubmitHandler);
                el.submit();
            } else if (el.tagName === 'BUTTON' || el.tagName === 'INPUT') {
                el.closest('form') && el.closest('form').submit();
            } else if (el.tagName === 'A') {
                window.location.href = el.href;
            }
        }
    });
    return false; // always prevent default; dialog will handle it
}


/* ============================================================
   ALERT DISMISS
   ============================================================ */
function agDismissAlert(btn) {
    const alert = btn.closest('.alert');
    if (!alert) return;
    alert.classList.add('dismissing');
    setTimeout(() => alert.remove(), 320);
}


/* ============================================================
   LOGOUT
   ============================================================ */
function agLogout(e) {
    if (e) e.preventDefault();
    agConfirm(
        'You will be signed out of your current session. Any unsaved changes will be lost.',
        { type: 'logout', title: 'Sign Out?', okLabel: 'Sign Out', cancelLabel: 'Stay' }
    ).then(function (ok) {
        if (ok) {
            const loader = document.getElementById('page-loader');
            if (loader) { loader.style.display = 'flex'; loader.classList.remove('hide'); }
            window.location.href = window._logoutUrl || '/logout';
        }
    });
}


/* ============================================================
   DOM READY
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {

    // 1. Live Digital Clock
    function updateClocks() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        document.querySelectorAll('.clock-live').forEach(el => { el.textContent = timeStr; });
        const dateStr = now.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
        document.querySelectorAll('.date-live').forEach(el => { el.textContent = dateStr; });
    }
    updateClocks();
    setInterval(updateClocks, 1000);

    // 2. Tab Navigation
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const target = btn.dataset.tab;
            if (!target) return;
            const parentContainer = btn.closest('.card') || document;
            parentContainer.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            parentContainer.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            const targetContent = parentContainer.querySelector(`#${target}`);
            if (targetContent) targetContent.classList.add('active');
        });
    });

    // 3. Mobile Sidebar Toggle
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.app-sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => { sidebar.classList.toggle('open'); });
    }

    // 4. Auto-dismiss alerts after 6 seconds (animated)
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            if (!el.classList.contains('dismissing')) {
                agDismissAlert(el.querySelector('.alert-dismiss-btn') || el);
            }
        });
    }, 6000);

    // 5. Intercept all onclick="return confirmAction(...)" forms
    document.querySelectorAll('[onclick*="confirmAction"]').forEach(el => {
        el.addEventListener('click', function (e) {
            // Only intercept if not already handled
            if (el._agConfirmBound) return;
            el._agConfirmBound = true;
            e.preventDefault();
            e.stopPropagation();

            // Extract message from onclick attr
            const onclickStr = el.getAttribute('onclick') || '';
            const match = onclickStr.match(/confirmAction\(['"](.+?)['"]/);
            const msg = match ? match[1] : 'Are you sure?';

            window._pendingConfirmElement = el;
            agConfirm(msg, { type: 'warning', title: 'Confirm Action' }).then(ok => {
                if (ok) {
                    window._pendingConfirmElement = null;
                    // Re-fire click without interception
                    el.removeAttribute('onclick');
                    el._agConfirmBound = false;
                    if (el.tagName === 'BUTTON' || el.tagName === 'INPUT') {
                        const form = el.closest('form');
                        if (form) {
                            const loader = document.getElementById('page-loader');
                            if (loader) { loader.style.display = 'flex'; loader.classList.remove('hide'); }
                            form.submit();
                        } else {
                            el.click();
                        }
                    } else if (el.tagName === 'A') {
                        const loader = document.getElementById('page-loader');
                        if (loader) { loader.style.display = 'flex'; loader.classList.remove('hide'); }
                        window.location.href = el.href;
                    }
                }
            });
        }, true); // capture phase
    });
});


/* ============================================================
   MODAL HELPERS (existing — unchanged)
   ============================================================ */
function openModal(id) {
    const el = document.getElementById(id);
    if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.style.display = 'none'; document.body.style.overflow = ''; }
}

window.addEventListener('click', function (e) {
    if (e.target && (e.target.classList.contains('modal-backdrop') || e.target.classList.contains('modal'))) {
        e.target.style.display = 'none';
        document.body.style.overflow = '';
    }
});


/* ============================================================
   GLOBAL SEARCH & COMMAND PALETTE (Ctrl + K)
   ============================================================ */
(function initGlobalSearch() {
    let searchDebounceTimer = null;
    let activeResultIndex = -1;
    let currentResultLinks = [];
    let isFetching = false;
    let lastQuery = null;

    function getElements() {
        return {
            wrapper: document.getElementById('headerSearchWrapper'),
            input: document.getElementById('globalSearchInput'),
            results: document.getElementById('globalSearchResults'),
            clearBtn: document.getElementById('globalSearchClear'),
            kbd: document.getElementById('globalSearchKbd')
        };
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Safe URL resolver
    function getApiUrl(query) {
        const base = window._searchApiUrl || '/advancells/api/search';
        return base + (base.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(query);
    }

    // Show dropdown and fetch results
    function openSearchDropdown() {
        const { results, input } = getElements();
        if (!results || !input) return;

        results.style.display = 'block';
        const q = input.value.trim();
        if (lastQuery !== q || results.innerHTML.trim() === '') {
            performSearch(q);
        }
    }

    // Hide dropdown
    function closeSearchDropdown() {
        const { results, wrapper } = getElements();
        if (results) {
            results.style.display = 'none';
        }
        if (wrapper) {
            wrapper.classList.remove('mobile-open');
        }
        activeResultIndex = -1;
        currentResultLinks = [];
    }

    // Execute API search
    function performSearch(query) {
        const { results } = getElements();
        if (!results) return;

        lastQuery = query;
        isFetching = true;

        // Show subtle loading state if not already populated
        if (results.innerHTML.trim() === '') {
            results.innerHTML = `
                <div style="padding: 24px 16px; text-align: center; color: #94a3b8; font-size: 12.5px;">
                    <i class="fa-solid fa-circle-notch fa-spin" style="margin-right: 8px; color: var(--primary);"></i>
                    Searching Advancells HRMS...
                </div>
            `;
        }

        fetch(getApiUrl(query), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('Search request failed: ' + res.status);
            return res.json();
        })
        .then(data => {
            isFetching = false;
            renderSearchResults(data, query);
        })
        .catch(err => {
            isFetching = false;
            console.error('Global search error:', err);
            results.innerHTML = `
                <div style="padding: 20px 16px; text-align: center; color: #ef4444; font-size: 12px;">
                    <i class="fa-solid fa-triangle-exclamation" style="margin-right: 6px;"></i>
                    Unable to fetch search results. Please try again.
                </div>
            `;
        });
    }

    // Render formatted results inside dropdown
    function renderSearchResults(data, query) {
        const { results } = getElements();
        if (!results) return;

        currentResultLinks = [];
        activeResultIndex = -1;

        if (!data || !data.results || Object.keys(data.results).length === 0 || data.total === 0) {
            results.innerHTML = `
                <div class="search-empty-state">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <div class="empty-title">No results found for "${escapeHtml(query)}"</div>
                    <div class="empty-sub">Try searching by employee name, employee code, leave type, payslip number, or asset name.</div>
                </div>
                <div class="search-footer-hint">
                    <div class="search-footer-left">
                        <span><kbd>ESC</kbd> to dismiss</span>
                    </div>
                    <span>Advancells HRMS Global Search</span>
                </div>
            `;
            return;
        }

        let html = '';

        // Iterate over category groups
        for (const [catKey, group] of Object.entries(data.results)) {
            if (!group.items || group.items.length === 0) continue;

            html += `<div class="search-category-wrap">`;
            html += `<div class="search-category-header">
                        <span>${escapeHtml(group.title)}</span>
                     </div>`;

            group.items.forEach(item => {
                const itemUrl = escapeHtml(item.url);
                const title = escapeHtml(item.title);
                const subtitle = escapeHtml(item.subtitle);
                const badge = item.badge ? `<span class="badge ${item.badge_class || 'badge-secondary'} search-item-badge">${escapeHtml(item.badge)}</span>` : '';

                let iconHtml = '';
                if (item.avatar) {
                    iconHtml = `<img src="${escapeHtml(item.avatar)}" alt="${title}" class="search-item-avatar">`;
                } else if (item.initials) {
                    iconHtml = `<div class="search-item-initials">${escapeHtml(item.initials)}</div>`;
                } else {
                    const iconColor = item.color || '#2563eb';
                    const iconBg = iconColor + '18';
                    iconHtml = `
                        <div class="search-item-icon-box" style="background: ${iconBg}; color: ${iconColor};">
                            <i class="fa-solid ${escapeHtml(item.icon || 'fa-arrow-right')}"></i>
                        </div>
                    `;
                }

                html += `
                    <a href="${itemUrl}" class="search-result-item" role="option">
                        ${iconHtml}
                        <div class="search-item-info">
                            <div class="search-item-title">${title}</div>
                            ${subtitle ? `<div class="search-item-subtitle">${subtitle}</div>` : ''}
                        </div>
                        <div class="search-item-action">
                            ${badge}
                            <span class="search-key-hint"><i class="fa-solid fa-arrow-turn-down-left"></i></span>
                        </div>
                    </a>
                `;
            });

            html += `</div>`;
        }

        // Footer shortcut helper
        html += `
            <div class="search-footer-hint">
                <div class="search-footer-left">
                    <span><kbd>↑</kbd> <kbd>↓</kbd> to navigate</span>
                    <span><kbd>↵</kbd> to select</span>
                    <span><kbd>ESC</kbd> to close</span>
                </div>
                <span>${data.total} ${data.total === 1 ? 'match' : 'matches'}</span>
            </div>
        `;

        results.innerHTML = html;

        // Cache all matching clickable result links
        currentResultLinks = Array.from(results.querySelectorAll('.search-result-item'));

        // Highlight the first result by default for effortless Enter navigation
        if (currentResultLinks.length > 0) {
            setActiveItem(0);
        }
    }

    // Set active item visually and update active index
    function setActiveItem(index) {
        if (!currentResultLinks || currentResultLinks.length === 0) return;

        currentResultLinks.forEach(el => el.classList.remove('active'));

        if (index >= 0 && index < currentResultLinks.length) {
            activeResultIndex = index;
            const targetEl = currentResultLinks[activeResultIndex];
            targetEl.classList.add('active');
            targetEl.scrollIntoView({ block: 'nearest' });
        } else {
            activeResultIndex = -1;
        }
    }

    // Update clear button and Ctrl+K badge based on input text
    function updateClearButtonState() {
        const { input, clearBtn, kbd } = getElements();
        if (!input || !clearBtn || !kbd) return;

        const hasText = input.value.trim().length > 0;
        clearBtn.style.display = hasText ? 'inline-flex' : 'none';
        kbd.style.display = hasText ? 'none' : 'inline-block';
    }

    // Attach listeners on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        const { input, clearBtn, wrapper } = getElements();
        if (!input) return;

        // 1. Global Keyboard Shortcut: Ctrl + K / Cmd + K to focus search
        window.addEventListener('keydown', (e) => {
            // Check for Ctrl+K or Cmd+K
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                // If on mobile screen, toggle mobile view
                if (wrapper && window.innerWidth <= 992) {
                    wrapper.classList.add('mobile-open');
                }
                input.focus();
                input.select();
                openSearchDropdown();
            }

            // Escape key closes search
            if (e.key === 'Escape') {
                const { results } = getElements();
                if (results && results.style.display !== 'none') {
                    e.preventDefault();
                    closeSearchDropdown();
                    input.blur();
                }
            }
        });

        // 2. Input Focus: open dropdown
        input.addEventListener('focus', () => {
            updateClearButtonState();
            openSearchDropdown();
        });

        // 3. Input Typing: debounced search (180ms)
        input.addEventListener('input', () => {
            updateClearButtonState();
            openSearchDropdown();

            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                performSearch(input.value.trim());
            }, 180);
        });

        // 4. Keyboard Arrow & Enter Navigation inside Search Input
        input.addEventListener('keydown', (e) => {
            const { results } = getElements();
            const isOpen = results && results.style.display !== 'none';

            if (!isOpen || currentResultLinks.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = (activeResultIndex + 1) % currentResultLinks.length;
                setActiveItem(nextIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevIndex = (activeResultIndex - 1 + currentResultLinks.length) % currentResultLinks.length;
                setActiveItem(prevIndex);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeResultIndex >= 0 && currentResultLinks[activeResultIndex]) {
                    const targetLink = currentResultLinks[activeResultIndex];
                    const loader = document.getElementById('page-loader');
                    if (loader) { loader.style.display = 'flex'; loader.classList.remove('hide'); }
                    window.location.href = targetLink.href;
                } else if (currentResultLinks.length > 0) {
                    const targetLink = currentResultLinks[0];
                    const loader = document.getElementById('page-loader');
                    if (loader) { loader.style.display = 'flex'; loader.classList.remove('hide'); }
                    window.location.href = targetLink.href;
                }
            }
        });

        // 5. Clear Button Click
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                input.value = '';
                updateClearButtonState();
                input.focus();
                performSearch('');
            });
        }

        // 6. Dismiss dropdown when clicking outside
        document.addEventListener('click', (e) => {
            const { wrapper } = getElements();
            if (wrapper && !wrapper.contains(e.target)) {
                closeSearchDropdown();
            }
        });
    });
})();
