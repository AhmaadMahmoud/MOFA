<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Costing: what each ingredient costs to buy (e.g. 300 g of beef for 150 EGP),
 * and a packaging cost per item (box, bag, napkins). See App\Support\Costing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->decimal('purchase_quantity', 10, 2)->nullable()->after('name_ar');
            $table->string('purchase_unit', 10)->nullable()->after('purchase_quantity');
            $table->unsignedInteger('purchase_price')->nullable()->after('purchase_unit'); // piasters
        });

        Schema::table('items', function (Blueprint $table) {
            $table->unsignedInteger('packaging_cost')->nullable()->after('price'); // piasters
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn(['purchase_quantity', 'purchase_unit', 'purchase_price']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('packaging_cost');
        });
    }
};
