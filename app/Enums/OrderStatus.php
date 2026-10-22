<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case PendingPayment = 'pending_payment';
    case PaymentReview = 'payment_review';
    case Confirmed = 'confirmed';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingPayment => __('Waiting for payment'),
            self::PaymentReview => __('Payment under review'),
            self::Confirmed => __('Confirmed'),
            self::OutForDelivery => __('Out for delivery'),
            self::Delivered => __('Delivered'),
            self::Expired => __('Expired'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingPayment => 'gray',
            self::PaymentReview => 'warning',
            self::Confirmed, self::OutForDelivery => 'info',
            self::Delivered => 'success',
            self::Expired, self::Cancelled => 'danger',
        };
    }

    /** Orders that will be baked: what the prep list counts. */
    public static function toBake(): array
    {
        return [self::Confirmed, self::OutForDelivery, self::Delivered];
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::PendingPayment, self::PaymentReview, self::Confirmed, self::OutForDelivery], true);
    }
}
