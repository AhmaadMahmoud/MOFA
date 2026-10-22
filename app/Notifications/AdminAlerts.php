<?php

namespace App\Notifications;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

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

    private static function send(Order $order, string $title, string $body, string $icon, string $color): void
    {
        $users = User::query()->get();
        if ($users->isEmpty()) {
            return;
        }

        $params = [
            'number' => $order->number,
            'name' => $order->customer->name,
            'total' => Money::format($order->total, 'en'),
            'date' => $order->delivery_date->format('D j M'),
        ];

        Notification::make()
            ->title(__($title, $params, 'en'))
            ->body(__($body, $params, 'en'))
            ->icon($icon)
            ->iconColor($color)
            ->actions([
                Action::make('open')
                    ->label('Open order')
                    ->url(OrderResource::getUrl('view', ['record' => $order], panel: 'admin'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($users);
    }
}
