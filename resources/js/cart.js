/**
 * The cart lives in the browser (localStorage), so it survives reloads and needs no account.
 * It keeps a display copy of each item; the server re-prices everything at checkout.
 */
const KEY = 'mofa_cart_v1';
const MAX_QTY = 20;

/*
 * A small "it's in" moment when something is added: the bag bumps (CSS, via `bump`),
 * a short soft chime, and a tiny buzz on phones that allow it (Android).
 * Turn the sound off by setting SOUND to false.
 */
const SOUND = true;
let audio = null;
const chime = () => {
    if (!SOUND) return;
    try {
        audio ??= new (window.AudioContext || window.webkitAudioContext)();
        const t = audio.currentTime;
        // Two quick bell-like notes (E6, then A6), quiet and short.
        [[1318.5, 0], [1760, 0.07]].forEach(([freq, delay]) => {
            const osc = audio.createOscillator();
            const gain = audio.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, t + delay);
            gain.gain.exponentialRampToValueAtTime(0.07, t + delay + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + delay + 0.35);
            osc.connect(gain).connect(audio.destination);
            osc.start(t + delay);
            osc.stop(t + delay + 0.4);
        });
    } catch {
        // No audio: the visual bump is enough.
    }
};

const read = () => {
    try {
        const data = JSON.parse(window.localStorage.getItem(KEY) || '{}');
        return data && typeof data.lines === 'object' ? data.lines : {};
    } catch {
        return {};
    }
};

export const formatMoney = (piasters) => {
    const pounds = piasters / 100;
    const amount = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: piasters % 100 === 0 ? 0 : 2,
        maximumFractionDigits: 2,
    }).format(pounds);
    return document.documentElement.lang === 'ar' ? `${amount} ج.م` : `${amount} EGP`;
};

export const cartStore = {
    lines: {},
    open: false,
    /** Goes up on every add; the bag and the bottom bar watch it to bump. */
    bump: 0,
    toast: null,
    _toastTimer: null,

    init() {
        this.lines = read();
        // Another tab changed the cart.
        window.addEventListener('storage', (e) => {
            if (e.key === KEY) this.lines = read();
        });
    },

    get items() {
        return Object.values(this.lines);
    },
    get count() {
        return this.items.reduce((n, l) => n + l.qty, 0);
    },
    get subtotal() {
        return this.items.reduce((n, l) => n + l.qty * l.price, 0);
    },
    get isEmpty() {
        return this.count === 0;
    },

    qty(id) {
        return this.lines[id]?.qty ?? 0;
    },
    name(line) {
        return document.documentElement.lang === 'ar' ? line.name_ar || line.name_en : line.name_en;
    },
    money: formatMoney,

    add(product, qty = 1) {
        const current = this.lines[product.id]?.qty ?? 0;
        this.lines = { ...this.lines, [product.id]: { ...product, qty: Math.min(MAX_QTY, current + qty) } };
        this.save();
        this.flash(product);
        this.celebrate();
    },
    celebrate() {
        this.bump++;
        chime();
        navigator.vibrate?.(12);
    },
    set(id, qty) {
        qty = Math.max(0, Math.min(MAX_QTY, Number(qty) || 0));
        if (!this.lines[id]) return;
        const lines = { ...this.lines };
        if (qty === 0) delete lines[id];
        else lines[id] = { ...lines[id], qty };
        this.lines = lines;
        this.save();
    },
    inc(id) {
        const before = this.qty(id);
        this.set(id, before + 1);
        if (this.qty(id) > before) this.celebrate();
    },
    dec(id) {
        this.set(id, this.qty(id) - 1);
    },
    clear(announce = true) {
        this.lines = {};
        this.save(announce);
    },

    /** {id: qty} — what the server receives. */
    quantities() {
        return Object.fromEntries(this.items.map((l) => [l.id, l.qty]));
    },

    /** Replace the display copy with the server's fresh lines (price changes, removed items). */
    sync(fresh) {
        const lines = {};
        for (const l of fresh) lines[l.id] = l;
        this.lines = lines;
        this.save(false);
    },

    save(announce = true) {
        try {
            window.localStorage.setItem(KEY, JSON.stringify({ lines: this.lines }));
        } catch {
            /* private mode: the cart still works for this page view */
        }
        if (announce) window.dispatchEvent(new CustomEvent('cart-changed', { detail: this.quantities() }));
    },

    flash(product) {
        this.toast = this.name(product);
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => (this.toast = null), 2600);
    },
};
