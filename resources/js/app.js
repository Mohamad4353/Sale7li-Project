import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

// Mark that JavaScript is running. CSS keys off `html:not(.js)` to give a plain
// no-JS fallback where an Alpine-only affordance would otherwise be dead (e.g.
// the mobile filter drawer's open button).
document.documentElement.classList.add('js');

Alpine.plugin(focus);
window.Alpine = Alpine;

/**
 * Branded confirmation dialog for `data-confirm` actions (P14-11).
 *
 * The <x-confirm-dialog> Alpine component registers `window.__sale7liConfirm`
 * on mount; it returns a Promise<boolean>. If the component is not mounted for
 * any reason we fall back to the native `window.confirm()` so a destructive
 * action is never left unguarded. With JavaScript disabled there is no submit
 * listener at all, so the form simply submits — the action stays usable.
 */
function askConfirmation(message, trigger) {
    if (typeof window.__sale7liConfirm === 'function') {
        return Promise.resolve(window.__sale7liConfirm(message, trigger));
    }
    return Promise.resolve(window.confirm(message));
}

/**
 * Progressive form-submit feedback + confirmation gate. On a `data-confirm`
 * form the first submit is held, the dialog is shown, and only a positive
 * answer re-submits it — exactly once, and never after Cancel. `data-confirm`
 * remains the single source of the message; form action / method / CSRF are
 * untouched. Forms opting out of the busy state set `data-no-busy`.
 * Plain JS — no eval, CSP-safe.
 */
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-busy')) return;

    const confirmMessage = form.getAttribute('data-confirm');
    if (confirmMessage && form.dataset.confirmed !== '1') {
        event.preventDefault();
        const trigger = (event.submitter instanceof HTMLElement && event.submitter)
            || (document.activeElement instanceof HTMLElement && document.activeElement)
            || form;
        askConfirmation(confirmMessage, trigger).then((ok) => {
            if (!ok) return;                         // Cancel — never submits
            form.dataset.confirmed = '1';            // let the next submit through
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        });
        return;
    }
    // Post-confirmation pass (or a form with no data-confirm): clear the flag so
    // a later re-submit is confirmed afresh, then fall through to the busy lock.
    delete form.dataset.confirmed;

    if (form.dataset.busy === '1') {
        event.preventDefault();
        return;
    }
    form.dataset.busy = '1';
    form.querySelectorAll('button[type="submit"], button:not([type])').forEach((btn) => {
        btn.setAttribute('aria-busy', 'true');
        btn.disabled = true;
    });
}, true);

/**
 * Alpine factory for <x-confirm-dialog>. One instance, mounted from the layout.
 * `defaults` carries the localized title / confirm label from Blade.
 */
window.confirmDialog = (defaults = {}) => ({
    open: false,
    title: defaults.title || 'Are you sure?',
    confirmLabel: defaults.confirmLabel || 'Confirm',
    message: '',
    _resolve: null,
    _trigger: null,
    init() {
        window.__sale7liConfirm = (message, trigger) => this.ask(message, trigger);
    },
    destroy() {
        if (typeof window.__sale7liConfirm === 'function') window.__sale7liConfirm = null;
    },
    ask(message, trigger) {
        this.message = message || this.title;
        this._trigger = trigger && typeof trigger.focus === 'function' ? trigger : null;
        this.open = true;
        return new Promise((resolve) => { this._resolve = resolve; });
    },
    confirm() { this._settle(true); },
    cancel() { this._settle(false); },
    _settle(result) {
        if (!this.open) return;
        this.open = false;
        const trigger = this._trigger;
        const resolve = this._resolve;
        this._resolve = null;
        this._trigger = null;
        // Return focus to whatever triggered the confirmation.
        if (trigger) requestAnimationFrame(() => trigger.focus());
        if (resolve) resolve(result);
    },
});

/*
 * (Phase 17) The Alpine `attachmentField()` factory was removed — the chat
 * attachment field is now the React <AttachmentInput> island
 * (resources/js/react/components/AttachmentInput.jsx), mounted via <x-react>
 * on messages/show. favoriteToggle / confirmDialog / chatPoll stay in Alpine.
 */

/**
 * Auto-submit the parent form when a `[data-autosubmit]` control changes.
 * Replaces inline `onchange="this.form.submit()"` so the strict CSP (no
 * script-src 'unsafe-inline') does not disable it.
 */
document.addEventListener('change', (event) => {
    const el = event.target;
    if (el instanceof HTMLElement && el.hasAttribute('data-autosubmit') && el.form) {
        el.form.requestSubmit ? el.form.requestSubmit() : el.form.submit();
    }
});

/**
 * Lightweight conversation poller. No WebSockets — a plain interval fetch for
 * messages newer than `lastId`, appended as server-rendered bubbles. If JS is
 * off, the normal paginated thread and full-page send still work. Polling never
 * creates messages and never re-appends ones already on the page.
 */
window.chatPoll = (url, initialLastId) => ({
    lastId: Number(initialLastId) || 0,
    timer: null,
    onVisible: null,
    init() {
        this.scrollToEnd();
        this.start();
        // Pause the interval while the tab is hidden, resume on return. Same
        // 12s cadence and same `?after=` delta endpoint — nothing else changes.
        this.onVisible = () => (document.hidden ? this.stop() : this.start());
        document.addEventListener('visibilitychange', this.onVisible);
    },
    destroy() {
        this.stop();
        if (this.onVisible) document.removeEventListener('visibilitychange', this.onVisible);
    },
    start() {
        if (!this.timer) this.timer = setInterval(() => this.fetchNew(), 12000);
    },
    stop() {
        clearInterval(this.timer);
        this.timer = null;
    },
    scrollToEnd() {
        const box = this.$refs.thread;
        if (box) box.scrollTop = box.scrollHeight;
    },
    async fetchNew() {
        if (document.hidden) return;
        try {
            const res = await fetch(`${url}?after=${this.lastId}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.html && data.html.trim() !== '') {
                const box = this.$refs.thread;
                box.insertAdjacentHTML('beforeend', data.html);
                this.scrollToEnd();
            }
            if (data.last_id) this.lastId = Number(data.last_id);
        } catch (e) {
            /* transient network error — retry on the next tick */
        }
    },
});


/**
 * Lightweight top page-progress bar. Full-page navigations (link clicks,
 * data-autosubmit filters, form posts) give no feedback on their own; this
 * nudges a 2px bar so a slow response doesn't feel like a dead click. Plain
 * DOM, no eval, CSP-safe. The bar carries no status text — it is aria-hidden.
 */
(() => {
    const bar = document.getElementById('page-progress');
    if (!bar) return;

    let creep = null;

    const start = () => {
        clearTimeout(creep);
        bar.classList.add('is-active');
        bar.style.transform = 'scaleX(0.3)';
        creep = setTimeout(() => { bar.style.transform = 'scaleX(0.66)'; }, 400);
    };

    const reset = () => {
        clearTimeout(creep);
        bar.classList.remove('is-active');
        bar.style.transform = 'scaleX(0)';
    };

    // A data-autosubmit control just triggered a navigation (see the change
    // listener above).
    document.addEventListener('change', (event) => {
        const el = event.target;
        if (el instanceof HTMLElement && el.hasAttribute('data-autosubmit') && el.form) start();
    });

    // Internal navigation via links — but not the many kinds that do not load a
    // new page.
    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target instanceof Element ? event.target.closest('a') : null;
        if (!link) return;

        const href = link.getAttribute('href') || '';
        if (href === '' || /^(mailto:|tel:|javascript:|#)/i.test(href)) return;
        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;
        if (link.origin !== window.location.origin) return;                 // external (incl. wa.me / WhatsApp)
        if (link.pathname === window.location.pathname && link.hash) return; // same-page anchor

        start();
    });

    // A real navigation is committing.
    window.addEventListener('beforeunload', start);

    // Restored from the back/forward cache — clear a stale bar.
    window.addEventListener('pageshow', (event) => { if (event.persisted) reset(); });
})();

/**
 * Favorite (save professional) toggle. Optimistic: flip immediately, POST in the
 * background, revert on failure. With JS off the form posts normally and the
 * controller redirects back — so the feature still works.
 */
window.favoriteToggle = (initial) => ({
    on: initial === true || initial === 'true',
    busy: false,
    async toggle(form) {
        if (this.busy) return;
        this.busy = true;
        const previous = this.on;
        this.on = !this.on;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token || '', Accept: 'application/json' },
            });
            if (!res.ok) throw new Error('request failed');
            const data = await res.json();
            this.on = data.favorited === true;
        } catch (e) {
            this.on = previous;
        } finally {
            this.busy = false;
        }
    },
});

/**
 * Advisory AI Service Assistant on the booking page (resources/views/booking/create.blade.php).
 * Talks ONLY to `url` (customer.booking.aiSuggestion) — no provider URL or
 * credential ever reaches this file. The endpoint already owns auth,
 * verification, throttling and validation; this component duplicates none of
 * that, it only renders what the endpoint returns and, on "Use suggestion",
 * writes plain text into the EXISTING category radio / description textarea.
 * The model's category is only ever applied if it matches an existing
 * `[data-category-option]` in the DOM — never used to create one.
 */
window.aiSuggestion = (url) => ({
    description: '',
    state: 'idle', // idle | loading | success | unavailable | error
    suggestion: null,
    async request() {
        if (this.state === 'loading') return; // no duplicate in-flight requests
        const text = this.description.trim();
        if (text === '') return;

        this.state = 'loading';
        this.suggestion = null;

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token || '',
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ description: text }),
            });

            if (!res.ok) { // covers 429, validation 422, and any server error alike
                this.state = 'error';
                return;
            }

            const data = await res.json();
            if (!data || data.available !== true) {
                this.state = 'unavailable';
                return;
            }

            const slug = data.category && typeof data.category.slug === 'string' ? data.category.slug : null;
            const label = data.category && typeof data.category.label === 'string' ? data.category.label : null;
            const options = Array.from(document.querySelectorAll('[data-category-option]'));
            const matched = slug !== null && options.some((el) => el.dataset.slug === slug);

            this.suggestion = {
                slug: matched ? slug : null,
                label: matched ? label : null,
                description: typeof data.structured_description === 'string' ? data.structured_description : '',
            };
            this.state = 'success';
        } catch (e) {
            this.state = 'error';
        }
    },
    apply() {
        if (!this.suggestion) return;

        if (this.suggestion.slug) {
            const input = Array.from(document.querySelectorAll('[data-category-option]'))
                .find((el) => el.dataset.slug === this.suggestion.slug);
            if (input) {
                input.checked = true;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        const textarea = document.getElementById('f-problem_description');
        if (textarea && this.suggestion.description) {
            textarea.value = this.suggestion.description; // plain text assignment, never rendered as markup
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // Collapse the suggestion, then move focus into the field the customer
        // will keep editing — the "Use suggestion" button that had focus is
        // about to be removed from the DOM.
        this.suggestion = null;
        this.state = 'idle';
        this.$nextTick(() => document.getElementById('f-problem_description')?.focus());
    },
    dismiss() {
        this.suggestion = null;
        this.state = 'idle';
        // Return focus to the control that opened this suggestion.
        this.$nextTick(() => document.getElementById('ai-assistant-analyze')?.focus());
    },
});

// Start Alpine LAST — after every window.* factory above is defined — so the
// synchronous initial DOM walk can resolve x-data="confirmDialog(...)" /
// chatPoll(...) / favoriteToggle(...) / aiSuggestion(...).
Alpine.start();
