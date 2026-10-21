<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ItemBadge: string implements HasLabel
{
    case New = 'new';
    case Bestseller = 'bestseller';
    case Mini = 'mini';
    case Limited = 'limited';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::Bestseller => __('Bestseller'),
            self::Mini => __('Mini'),
            self::Limited => __('Limited'),
        };
    }
}
