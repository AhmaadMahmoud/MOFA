<?php

use App\Http\Controllers\MenuController;
use App\Models\Order;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// "/" sends visitors to their language: saved choice, else browser language, else Arabic.
Route::get('/', fn (Request $request) => redirect()->route('home', ['locale' => Locales::preferred($request)]))
    ->name('root');

Route::prefix('{locale}')
    ->where(['locale' => implode('|', Locales::supported())])
    ->middleware('locale')
    ->group(function () {
        Route::get('/', fn () => view('pages.home', [
            'featured' => \App\Models\Item::query()->active()->where('is_featured', true)->ordered()->limit(6)->get(),
        ]))->name('home');

        Route::get('/menu', [MenuController::class, 'index'])->name('menu');
        Route::get('/menu/{item}', [MenuController::class, 'show'])->name('menu.item');

        Route::view('/checkout', 'pages.checkout')->name('checkout');

        // The customer's private order page: only reachable through the signed link we give them.
        Route::get('/orders/{order}', fn (Order $order) => view('pages.order', ['order' => $order]))
            ->middleware('signed')
            ->name('orders.show');
    });

// Local only: see each customer email in the browser, for the latest order.
//   /_mail/confirmed?locale=ar
if (app()->isLocal()) {
    Route::get('/_mail/{type}', function (Request $request, string $type) {
        $class = [
            'confirmed' => \App\Notifications\Customer\OrderConfirmed::class,
        ][$type] ?? abort(404);

        $order = Order::query()->latest('id')->firstOrFail();
        $locale = in_array($request->query('locale'), Locales::supported(), true) ? $request->query('locale') : $order->locale;

        app()->setLocale($locale);
        $order->locale = $locale;

        return (new $class($order))->toMail($order->customer)->render();
    })->name('mail.preview');
}
