/**
 * Everything MOFA adds to Alpine, shared by both entry points:
 * app.js (plain pages, Alpine alone) and livewire.js (checkout and order pages,
 * where Alpine comes from Livewire's bundle). One Alpine per page either way.
 */
import { cartStore } from './cart';

/**
 * Menu category bar: highlights the category in view and keeps its chip visible.
 */
/**
 * The menu filter: "all" or one category. Follows the #slug in the address,
 * so "Back to the menu" from an item opens its category.
 */
const menuFilter = (slugs) => ({
    active: 'all',

    init() {
        this.fromHash();
        window.addEventListener('hashchange', () => this.fromHash());
    },

    fromHash() {
        const slug = decodeURIComponent(window.location.hash.slice(1));
        this.active = slugs.includes(slug) ? slug : 'all';
    },

    pick(slug) {
        this.active = slug;
        history.replaceState(null, '', slug === 'all' ? window.location.pathname : `#${slug}`);
        // Back to the top of the list when the filter bar is already stuck.
        const top = this.$refs.sections.getBoundingClientRect().top + window.scrollY - 80;
        if (window.scrollY > top) window.scrollTo({ top, behavior: 'smooth' });
    },

    shows(slug) {
        return this.active === 'all' || this.active === slug;
    },
});

/**
 * Countdown to a deadline (ISO string). Shows mm:ss under an hour, h:mm:ss above.
 */
const countdown = (deadline) => ({
    left: '',
    done: false,
    timer: null,
    init() {
        const end = new Date(deadline).getTime();
        const tick = () => {
            const s = Math.max(0, Math.floor((end - Date.now()) / 1000));
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const sec = String(s % 60).padStart(2, '0');
            this.left = h > 0 ? `${h}:${String(m).padStart(2, '0')}:${sec}` : `${m}:${sec}`;
            if (s === 0 && !this.done) {
                this.done = true;
                this.$dispatch('countdown-done');
            }
        };
        tick();
        this.timer = setInterval(tick, 1000);
    },
    destroy() {
        clearInterval(this.timer);
    },
});

/** Copy a value and say so for a moment. */
const copy = (value) => ({
    copied: false,
    async copy() {
        try {
            await navigator.clipboard.writeText(value);
        } catch {
            const t = Object.assign(document.createElement('textarea'), { value });
            document.body.append(t);
            t.select();
            document.execCommand('copy');
            t.remove();
        }
        this.copied = true;
        setTimeout(() => (this.copied = false), 1800);
    },
});

export function registerMofa(Alpine) {
    Alpine.store('cart', cartStore);
    Alpine.data('menuFilter', menuFilter);
    Alpine.data('countdown', countdown);
    Alpine.data('copy', copy);
}
