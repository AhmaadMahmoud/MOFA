<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Pre-ordering: where and when MOFA delivers, who orders, what they ordered,
 * and how they paid. Money is stored in piasters (integers), like the menu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->unsignedInteger('fee')->default(0);
            // Pickup from the bakery: no address needed.
            $table->boolean('is_pickup')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('delivery_slots', function (Blueprint $table) {
            $table->id();
            $table->string('label_en');
            $table->string('label_ar');
            $table->time('starts_at');
            $table->time('ends_at');
            // Orders this slot can take on one day. Null = no limit.
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // The two morning windows MOFA starts with. Editable from the dashboard.
        DB::table('delivery_slots')->insert([
            ['label_en' => '8–10 am', 'label_ar' => '8–10 صباحًا', 'starts_at' => '08:00', 'ends_at' => '10:00', 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['label_en' => '10 am–12 pm', 'label_ar' => '10–12 ظهرًا', 'starts_at' => '10:00', 'ends_at' => '12:00', 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('closed_dates', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email');
            $table->string('locale', 5)->default('ar');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('status')->index();
            $table->date('delivery_date');
            $table->foreignId('delivery_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('delivery_zone_id')->nullable()->constrained()->nullOnDelete();
            // Snapshots, so renaming a slot or zone later never rewrites old orders.
            $table->string('slot_label_en');
            $table->string('slot_label_ar');
            $table->string('zone_name_en');
            $table->string('zone_name_ar');
            $table->boolean('is_pickup')->default(false);
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('delivery_fee');
            $table->unsignedInteger('total');
            $table->string('locale', 5)->default('ar');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['delivery_date', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_en');
            $table->string('name_ar');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method');
            $table->unsignedInteger('amount');
            $table->string('status')->index();
            // The transfer reference the customer typed, if any.
            $table->string('reference')->nullable()->index();
            // Receipt screenshot, on the private disk.
            $table->string('proof_path')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('closed_dates');
        Schema::dropIfExists('delivery_slots');
        Schema::dropIfExists('delivery_zones');
    }
};
