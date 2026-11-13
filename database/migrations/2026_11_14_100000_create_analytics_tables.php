<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Site analytics, kept on our own server (no outside service): who visits, from where,
 * what they look at, and which visits end in an order. See App\Analytics\Tracker.
 *
 * Visitors are anonymous: a random id in a cookie, no IP address, no name. A visit is
 * linked to a customer only when that visit places an order.
 */
return new class extends Migration
{
    public function up(): void
    {
        // One visit = one sitting; 30 minutes without a page starts a new visit.
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id')->index();
            $table->timestamp('started_at')->index();
            $table->timestamp('last_seen_at');
            $table->string('landing_path', 255);
            $table->string('referrer_host', 120)->nullable();
            $table->string('source', 20)->index();          // instagram, facebook, whatsapp, tiktok, google, search, direct, other
            $table->string('utm_campaign', 80)->nullable();
            $table->string('device', 10);                    // mobile, tablet, desktop
            $table->string('browser', 20)->nullable();
            $table->string('os', 20)->nullable();
            $table->string('locale', 5)->nullable();
            $table->boolean('is_returning')->default(false);
            $table->unsignedSmallInteger('page_views')->default(0);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('page', 20);                      // home, menu, item, checkout, order
            $table->string('path', 255);
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('viewed_at')->index();
        });

        // Things done on a page: added to the bag, placed an order.
        Schema::create('visit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30);                      // add_to_cart, order_placed
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_events');
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('visits');
    }
};
