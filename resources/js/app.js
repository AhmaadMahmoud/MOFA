/*
 * Public site JavaScript.
 *
 * Livewire 4 bundles Alpine (with the focus plugin) and starts it on pages
 * that load Livewire. The public pages are plain Blade for now, so Alpine is
 * started here unless Livewire already provided it.
 */
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

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

if (!window.Alpine) {
    Alpine.plugin(focus);
    Alpine.data('categoryBar', categoryBar);
    window.Alpine = Alpine;
    Alpine.start();
} else {
    document.addEventListener('alpine:init', () => window.Alpine.data('categoryBar', categoryBar));
}
