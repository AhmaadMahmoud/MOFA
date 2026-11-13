<?php

namespace App\Http\Controllers;

use App\Analytics\Tracker;
use App\Analytics\UserAgent;
use App\Models\VisitEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/** POST /t from the browser (navigator.sendBeacon): "added to the bag". Always answers 204. */
class AnalyticsEventController extends Controller
{
    public function __invoke(Request $request, Tracker $tracker): Response
    {
        try {
            $name = $request->input('name');
            if ($name === VisitEvent::ADD_TO_CART && $request->user() === null && ! UserAgent::isBot((string) $request->userAgent())) {
                $item = $request->input('item');
                $tracker->event($request, $name, is_numeric($item) ? (int) $item : null, (int) $request->input('qty', 1));
            }
        } catch (Throwable $e) {
            report($e);
        }

        return response()->noContent();
    }
}
