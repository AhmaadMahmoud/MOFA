/**
 * Tells our own server about things done on a page (page views are counted on the server).
 * Fire-and-forget: never blocks or breaks the page. See app/Analytics/Tracker.php.
 */
export const track = (name, data = {}) => {
    try {
        const body = JSON.stringify({ name, ...data });
        const blob = new Blob([body], { type: 'application/json' });
        if (!navigator.sendBeacon?.('/t', blob)) {
            fetch('/t', { method: 'POST', body, headers: { 'Content-Type': 'application/json' }, keepalive: true }).catch(() => {});
        }
    } catch {
        /* analytics is optional */
    }
};
