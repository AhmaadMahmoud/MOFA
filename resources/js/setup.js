/**
 * Everything MOFA adds to Alpine, shared by both entry points:
 * app.js (plain pages, Alpine alone) and livewire.js (checkout and order pages,
 * where Alpine comes from Livewire's bundle). One Alpine per page either way.
 */
import { cartStore } from './cart';

/**
 * Menu category bar: highlights the category in view and keeps its chip visible.
 */
const categoryBar = () => ({
    active: null,

    init() {
        const sections = [...document.querySelectorAll('[data-category]')];
        this.active = sections[0]?.id ?? null;

        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
                if (visible.length) {
                    this.setActive(visible[0].target.id);
                }
            },
            { rootMargin: '-80px 0px -60% 0px' },
        );
        sections.forEach((s) => observer.observe(s));
    },

    setActive(id) {
        if (this.active === id) return;
        this.active = id;
        const chip = this.$refs.list.querySelector(`[data-target="${CSS.escape(id)}"]`);
        chip?.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
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
    Alpine.data('categoryBar', categoryBar);
    Alpine.data('countdown', countdown);
    Alpine.data('copy', copy);
}
