<?php

namespace Database\Factories;

use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Visit> */
class VisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'visitor_id' => (string) Str::uuid(),
            'started_at' => now(),
            'last_seen_at' => now(),
            'landing_path' => '/ar',
            'source' => 'direct',
            'device' => 'mobile',
            'browser' => 'Safari',
            'os' => 'iOS',
            'locale' => 'ar',
            'is_returning' => false,
            'page_views' => 1,
        ];
    }
}
