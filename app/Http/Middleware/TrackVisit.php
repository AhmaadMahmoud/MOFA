<?php

namespace App\Http\Middleware;

use App\Analytics\Tracker;
use App\Models\Item;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Counts a page view on the public site (see App\Analytics\Tracker). Never breaks the page. */
class TrackVisit
{
    private const PAGES = [
        'home' => 'home',
        'menu' => 'menu',
        'menu.item' => 'item',
        'checkout' => 'checkout',
        'orders.show' => 'order',
    ];

    public function __construct(private Tracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $page = self::PAGES[$request->route()?->getName()] ?? null;
            if ($page !== null && $this->tracker->shouldTrack($request, $response)) {
                $item = $request->route('item');
                $this->tracker->pageView($request, $page, $item instanceof Item ? $item : null);
            }
        } catch (Throwable $e) {
            report($e); // analytics must never cost a page
        }

        return $response;
    }
}
