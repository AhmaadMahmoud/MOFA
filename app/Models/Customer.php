<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/** A guest who ordered. Phone is the identity: the same phone is the same customer. */
#[Fillable(['name', 'phone', 'email', 'locale'])]
class Customer extends Model
{
    use HasFactory, Notifiable;

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function preferredLocale(): string
    {
        return $this->locale ?: 'ar';
    }

    /**
     * Egyptian mobile numbers in one shape: 01xxxxxxxxx.
     * Accepts +20 / 0020 / spaces / dashes / Arabic-Indic digits.
     */
    public static function normalizePhone(string $phone): string
    {
        $phone = strtr($phone, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '0020')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '20') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public static function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^01[0125]\d{8}$/', static::normalizePhone($phone));
    }
}
