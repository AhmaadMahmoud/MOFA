<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock: how much of each ingredient is on hand, and when to warn that it's running low.
 * A confirmed order takes its ingredients out; cancelling it puts them back. See App\Support\Stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            // Null = this ingredient's stock isn't tracked.
            $table->decimal('stock_quantity', 12, 3)->nullable()->after('purchase_price');
            $table->string('stock_unit', 10)->nullable()->after('stock_quantity');
            // Warn when the stock falls to this (in stock_unit). Null = only warn when it runs out.
            $table->decimal('low_stock_at', 12, 3)->nullable()->after('stock_unit');
        });

        Schema::table('orders', function (Blueprint $table) {
            // What this order took out of stock, so a cancellation puts back exactly that:
            // [{"ingredient_id": 3, "quantity": 0.12, "unit": "kg"}, ...]. Null = nothing taken.
            $table->json('stock_taken')->nullable()->after('cancel_reason');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'stock_unit', 'low_stock_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_taken');
        });
    }
};
