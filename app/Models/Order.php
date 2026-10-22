<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\URL;

#[Fillable([
    'number', 'customer_id', 'status', 'delivery_date', 'delivery_slot_id', 'delivery_zone_id',
    'slot_label_en', 'slot_label_ar', 'zone_name_en', 'zone_name_ar', 'is_pickup', 'address', 'notes',
    'subtotal', 'delivery_fee', 'total', 'locale', 'expires_at', 'confirmed_at', 'cancelled_at', 'cancel_reason',
])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'delivery_date' => 'immutable_date',
            'is_pickup' => 'boolean',
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'total' => 'integer',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlot::class, 'delivery_slot_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /** The current payment attempt. */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * Orders that take a place on their delivery day. Unpaid ones count too: nothing cancels
     * them automatically, so they hold their place until Faten confirms or cancels them.
     */
    public function scopeHoldingCapacity(Builder $query): void
    {
        $query->whereIn('status', [OrderStatus::PendingPayment, OrderStatus::PaymentReview, OrderStatus::Confirmed, OrderStatus::OutForDelivery, OrderStatus::Delivered]);
    }

    /** Placed, waiting for Faten to see the receipt on WhatsApp and confirm (or cancel). */
    public function scopeAwaitingConfirmation(Builder $query): void
    {
        $query->whereIn('status', [OrderStatus::PendingPayment, OrderStatus::PaymentReview]);
    }

    public function scopeForDate(Builder $query, $date): void
    {
        $query->whereDate('delivery_date', $date);
    }

    public function slotLabel(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar' ? $this->slot_label_ar : $this->slot_label_en;
    }

    public function zoneName(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar' ? $this->zone_name_ar : $this->zone_name_en;
    }

    public function awaitsPayment(): bool
    {
        return in_array($this->status, [OrderStatus::PendingPayment, OrderStatus::PaymentReview], true);
    }

    /** WhatsApp chat with the shop, the receipt message already typed (the customer attaches the screenshot). */
    public function whatsappReceiptUrl(): ?string
    {
        return \App\Support\Settings::whatsappLink(__('Hi MOFA, this is the InstaPay receipt for order :number (:amount).', [
            'number' => $this->number,
            'amount' => \App\Support\Money::format($this->total, $this->locale),
        ], $this->locale));
    }

    /** The customer's private link to this order. Signed: the number alone isn't enough. */
    public function publicUrl(?string $locale = null): string
    {
        return URL::signedRoute('orders.show', ['locale' => $locale ?? $this->locale, 'order' => $this->number]);
    }

    /** Short, readable, hard to guess in sequence: MF-7K3Q9. */
    public static function newNumber(): string
    {
        do {
            $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
            $number = 'MF-';
            for ($i = 0; $i < 5; $i++) {
                $number .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::query()->where('number', $number)->exists());

        return $number;
    }
}
