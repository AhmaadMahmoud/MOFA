<?php

namespace App\Notifications\Customer;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * An email to the customer about their order, in the language they ordered in.
 * Built on Laravel notifications, so WhatsApp can be added later as one more channel in via().
 */
abstract class OrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->locale($order->locale);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    abstract protected function subject(): string;

    abstract protected function headline(): string;

    /** @return list<string> */
    abstract protected function lines(): array;

    protected function buttonLabel(): ?string
    {
        return __('View your order');
    }

    protected function showSummary(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');

        return (new MailMessage)
            ->subject($this->subject().' · '.$order->number)
            ->view('emails.order', [
                'order' => $order,
                'name' => $order->customer->name,
                'headline' => $this->headline(),
                'lines' => $this->lines(),
                'buttonLabel' => $this->buttonLabel(),
                'buttonUrl' => $order->publicUrl(),
                'showSummary' => $this->showSummary(),
            ]);
    }
}
