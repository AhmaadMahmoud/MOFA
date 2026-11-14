<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/** Visitor tracking was taken out of the site; its tables go with it. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('visit_events');
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('visits');
    }

    public function down(): void
    {
        // Not brought back: the tracking code that filled these tables is gone.
    }
};
