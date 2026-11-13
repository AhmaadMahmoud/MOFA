<?php

namespace App\Analytics;

use App\Models\Item;
use App\Models\Order;
use App\Models\PageView;
use App\Models\Visit;
use App\Models\VisitEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records visits to the public site, on our own server.
 *
 * - A visitor is a random id in a first-party cookie (a year). No IP address, no name.
 * - A visit is one sitting: 30 minutes with no page starts a new one.
 * - Page views are recorded on the server (middleware TrackVisit), so they count even
 *   with JavaScript blocked; "added to the bag" comes from the browser (cart.js → /t).
 * - When a visit places an order, the visit is linked to it: Faten sees the order's path.
 *
 * Not counted: bots and link previews (WhatsApp, Facebook…), browser prefetching,
 * anyone logged into the dashboard, and non-HTML or failed responses.
 */
final class Tracker
{
    public const COOKIE = 'mofa_vid';

    private const SESSION_KEY = 'analytics.visit';

    private const VISIT_GAP_MINUTES = 30;

    /** Keep the details this long; older rows are cleared now and then. */
    private const KEEP_DAYS = 400;

    public function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }
        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }
        if ($request->user() !== null) {
            return false; // Faten (or anyone in the dashboard) browsing her own site
        }
        $purpose = strtolower($request->header('Sec-Purpose', $request->header('Purpose', '')));
        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return false;
        }

        return ! UserAgent::isBot((string) $request->userAgent());
    }

    /** Called by the middleware for every public page. */
    public function pageView(Request $request, string $page, ?Item $item = null): void
    {
        $visit = $this->visitFor($request, startIfNeeded: true);

        PageView::query()->create([
            'visit_id' => $visit->id,
            'page' => $page,
            'path' => Str::limit('/'.ltrim($request->path(), '/'), 250, ''),
            'item_id' => $item?->id,
            'viewed_at' => now(),
        ]);
        $visit->forceFill(['last_seen_at' => now(), 'page_views' => $visit->page_views + 1])->save();

        if (random_int(1, 500) === 1) {
            $this->prune();
        }
    }

    /** Something done on a page (from the browser). Ignored when there is no visit yet. */
    public function event(Request $request, string $name, ?int $itemId = null, int $quantity = 1): void
    {
        $visit = $this->visitFor($request, startIfNeeded: false);
        if ($visit === null) {
            return;
        }

        VisitEvent::query()->create([
            'visit_id' => $visit->id,
            'name' => $name,
            'item_id' => $itemId !== null && Item::query()->whereKey($itemId)->exists() ? $itemId : null,
            'quantity' => max(1, min(99, $quantity)),
            'created_at' => now(),
        ]);
        $visit->forceFill(['last_seen_at' => now()])->save();
    }

    /** The current visit placed this order. */
    public function orderPlaced(Request $request, Order $order): void
    {
        $visit = $this->visitFor($request, startIfNeeded: false);
        if ($visit === null) {
            return;
        }

        $visit->forceFill(['order_id' => $order->id, 'last_seen_at' => now()])->save();
        VisitEvent::query()->create([
            'visit_id' => $visit->id,
            'name' => VisitEvent::ORDER_PLACED,
            'quantity' => 1,
            'created_at' => now(),
        ]);
    }

    private function visitFor(Request $request, bool $startIfNeeded): ?Visit
    {
        $known = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
        $current = is_int($known) ? Visit::query()->find($known) : null;
        if ($current && $current->last_seen_at->lte(now()->subMinutes(self::VISIT_GAP_MINUTES))) {
            $current = null; // a break: the next page starts a new visit
        }

        $visitorId = $request->cookie(self::COOKIE);
        if (! is_string($visitorId) || ! Str::isUuid($visitorId)) {
            // No visitor cookie (lost, or not sent with this request): keep the one this session already has.
            $visitorId = $current?->visitor_id;
        }
        if ($current && $current->visitor_id !== $visitorId) {
            $current = null; // the cookie says someone else
        }

        if ($visitorId === null && ! $startIfNeeded) {
            return null;
        }
        $visitorId ??= (string) Str::uuid();
        // Refresh it on every page so the year counts from the last visit.
        Cookie::queue(Cookie::make(self::COOKIE, $visitorId, 60 * 24 * 365, sameSite: 'lax'));

        if ($current) {
            return $current;
        }
        if (! $startIfNeeded) {
            return null;
        }

        $agent = (string) $request->userAgent();
        $browser = UserAgent::browser($agent);
        $source = Source::detect($request->headers->get('referer'), $request->query('utm_source'), $request->getHost());
        // Instagram's and Facebook's in-app browsers hide the referrer, but say who they are.
        if ($source['source'] === 'direct' && in_array($browser, ['Instagram', 'Facebook', 'TikTok'], true)) {
            $source['source'] = strtolower($browser);
        }

        $visit = Visit::query()->create([
            'visitor_id' => $visitorId,
            'started_at' => now(),
            'last_seen_at' => now(),
            'landing_path' => Str::limit('/'.ltrim($request->path(), '/'), 250, ''),
            'referrer_host' => $source['host'],
            'source' => $source['source'],
            'utm_campaign' => Str::limit((string) $request->query('utm_campaign'), 80, '') ?: null,
            'device' => UserAgent::device($agent),
            'browser' => $browser,
            'os' => UserAgent::os($agent),
            'locale' => app()->getLocale(),
            'is_returning' => Visit::query()->where('visitor_id', $visitorId)->exists(),
            'page_views' => 0,
        ]);

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $visit->id);
        }

        return $visit;
    }

    private function prune(): void
    {
        Visit::query()->where('started_at', '<', now()->subDays(self::KEEP_DAYS))->delete();
    }
}
