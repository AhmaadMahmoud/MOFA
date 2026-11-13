<?php

namespace App\Notifications;

use App\Filament\Resources\Ingredients\IngredientResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use App\Support\Stock;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationSender;

/**
 * The bell in the dashboard. Every admin user gets these.
 * They are stored as text when sent, in the dashboard's default language (English).
 */
final class AdminAlerts
{
    /** The receipt follows on WhatsApp: Faten confirms the order once the money is in. */
    public static function newOrder(Order $order): void
    {
        self::send(
            $order,
            'New order :number',
            ':name · :total · :date. The InstaPay receipt will come on WhatsApp.',
            'heroicon-o-shopping-bag',
            'warning',
        );
    }

    /** An ingredient just ran low (or out) after an order took from it. */
    public static function lowStock(Ingredient $ingredient): void
    {
        $out = (float) $ingredient->stock_quantity <= 0;
        $params = [
            'name' => $ingredient->name_en,
            'left' => Stock::label($ingredient->stock_quantity, $ingredient->stock_unit),
        ];

        self::deliver(
            Notification::make()
                ->title(__($out ? 'Out of :name' : 'Running low on :name', $params, 'en'))
                ->body(__($out ? 'Stock is at :left. Restock it or check the count.' : 'Only :left left.', $params, 'en'))
                ->icon('heroicon-o-archive-box-x-mark')
                ->iconColor($out ? 'danger' : 'warning')
                ->actions([
                    Action::make('open')
                        ->label('Open ingredients')
                        ->url(IngredientResource::getUrl('index', panel: 'admin'))
                        ->markAsRead(),
                ]),
        );
    }

    private static function send(Order $order, string $title, string $body, string $icon, string $color): void
    {
        $params = [
            'number' => $order->number,
            'name' => $order->customer->name,
            'total' => Money::format($order->total, 'en'),
            'date' => $order->delivery_date->format('D j M'),
        ];

        $alert = Notification::make()
            ->title(__($title, $params, 'en'))
            ->body(__($body, $params, 'en'))
            ->icon($icon)
            ->iconColor($color)
            ->actions([
                Action::make('open')
                    ->label('Open order')
                    ->url(OrderResource::getUrl('view', ['record' => $order], panel: 'admin'))
                    ->markAsRead(),
            ]);

        self::deliver($alert);
    }

    private static function deliver(Notification $alert): void
    {
        $users = User::query()->get();
        if ($users->isEmpty()) {
            return;
        }

        // Saved right away, not through the queue: the bell must work even when no queue worker runs.
        NotificationSender::sendNow($users, $alert->toDatabase());
    }
}
