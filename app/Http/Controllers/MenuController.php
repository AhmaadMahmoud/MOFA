<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Contracts\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->with(['activeItems' => fn ($q) => $q->ordered()])
            ->get()
            ->filter(fn (Category $category) => $category->activeItems->isNotEmpty())
            ->values();

        return view('pages.menu.index', ['categories' => $categories]);
    }

    public function show(Item $item): View
    {
        abort_unless($item->is_active && $item->category?->is_active, 404);

        $item->load('ingredientLines.ingredient');

        $more = Item::query()
            ->active()
            ->where('category_id', $item->category_id)
            ->whereKeyNot($item->getKey())
            ->ordered()
            ->limit(3)
            ->get();

        return view('pages.menu.show', ['item' => $item, 'more' => $more]);
    }
}
