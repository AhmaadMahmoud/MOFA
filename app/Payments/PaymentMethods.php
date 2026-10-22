<?php

namespace App\Payments;

use InvalidArgumentException;

final class PaymentMethods
{
    /** @var array<string, class-string<PaymentMethod>> */
    private const METHODS = [
        'instapay' => InstapayManual::class,
    ];

    public const DEFAULT = 'instapay';

    public static function get(?string $key = null): PaymentMethod
    {
        $class = self::METHODS[$key ?? self::DEFAULT] ?? throw new InvalidArgumentException("Unknown payment method [$key].");

        return app($class);
    }
}
