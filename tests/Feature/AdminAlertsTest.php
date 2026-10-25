<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminAlerts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_order_reaches_the_bell_even_without_a_queue_worker(): void
    {
        config(['queue.default' => 'database']);
        $faten = User::factory()->create();
        $order = Order::factory()->create();

        AdminAlerts::newOrder($order);

        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(1, $faten->unreadNotifications()->count());
        $this->assertStringContainsString($order->number, $faten->notifications()->first()->data['title']);
    }
}
