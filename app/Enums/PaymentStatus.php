<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('Not paid yet'),
            self::Submitted => __('Receipt sent'),
            self::Confirmed => __('Payment confirmed'),
            self::Rejected => __('Receipt rejected'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Submitted => 'warning',
            self::Confirmed => 'success',
            self::Rejected => 'danger',
        };
    }
}
