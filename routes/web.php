<?php

use App\Http\Controllers\MenuController;
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
    });
