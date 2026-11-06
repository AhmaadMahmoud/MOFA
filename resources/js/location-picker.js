/*
 * Checkout map: the pin stays in the middle and the customer moves the map under it
 * (like ride apps), or taps "Use my location". Leaflet only loads on this page.
 *
 * x-data="locationPicker($wire.entangle('lat'), $wire.entangle('lng'))"
 */
const CAIRO = [30.0444, 31.2357];

export const locationPicker = (lat, lng) => {
    // Kept outside Alpine's reactive data: Leaflet objects must not be proxied.
    let map = null;

    return {
        lat,
        lng,
        ready: false,
        moving: false,
        touched: false,
        locating: false,
        error: '',

        get pinned() {
            return this.lat !== null && this.lng !== null;
        },

        async init() {
            const [{ default: L }] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]);

            map = L.map(this.$refs.map, {
                center: this.pinned ? [this.lat, this.lng] : CAIRO,
                zoom: this.pinned ? 17 : 11,
                zoomControl: false,
            });
            map.attributionControl.setPrefix(false);

            // Tile source comes from the page (CARTO with a key, or OpenStreetMap without one).
            const { tiles, attribution } = this.$root.dataset;
            L.tileLayer(tiles, { maxZoom: 19, subdomains: 'abcd', attribution }).addTo(map);
            L.control.zoom({ position: 'topright' }).addTo(map);

            // Only a move made by the customer counts as pinning (not the first render).
            const touch = () => {
                this.touched = true;
                this.error = '';
            };
            map.on('dragstart', touch);
            map.on('zoomstart', () => { if (this.ready) touch(); });
            map.on('movestart', () => { this.moving = true; });
            map.on('moveend', () => {
                this.moving = false;
                if (this.touched) this.save();
            });

            this.ready = true;
            // Drawn while the step was animating in: size it once it is on screen.
            requestAnimationFrame(() => map.invalidateSize());
        },

        save() {
            const c = map.getCenter();
            this.lat = Math.round(c.lat * 1e7) / 1e7;
            this.lng = Math.round(c.lng * 1e7) / 1e7;
        },

        locate() {
            if (!('geolocation' in navigator) || !window.isSecureContext) {
                this.error = this.$root.dataset.noGps;
                return;
            }
            this.locating = true;
            this.error = '';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.locating = false;
                    this.touched = true;
                    map.flyTo([pos.coords.latitude, pos.coords.longitude], 17, { duration: 0.8 });
                },
                () => {
                    this.locating = false;
                    this.error = this.$root.dataset.noGps;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
            );
        },

        clear() {
            this.lat = null;
            this.lng = null;
            this.touched = false;
        },

        destroy() {
            map?.remove();
            map = null;
        },
    };
};
