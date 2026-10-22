<?php

namespace App\Livewire;

use App\Delivery\DeliveryDay;
use App\Delivery\DeliveryScheduler;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Ordering\Cart;
use App\Ordering\CartLine;
use App\Ordering\OrderPlacer;
use App\Ordering\SlotUnavailable;
use App\Payments\PaymentMethods;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Three steps on one page: your details → delivery → review and place.
 * The cart comes from the browser as {item id: quantity}; everything else is priced here.
 */
class Checkout extends Component
{
    public const STEPS = 3;

    /** @var array<int, int> */
    public array $quantities = [];

    /** False until the browser has sent its cart. */
    public bool $loaded = false;

    public int $step = 1;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public ?string $date = null;

    public ?int $slotId = null;

    public ?int $zoneId = null;

    public string $address = '';

    public string $notes = '';

    /** Called by the page once Alpine has read the cart from localStorage. */
    public function loadCart(array $quantities): void
    {
        $this->quantities = (new Cart($quantities))->quantities();
        $this->loaded = true;
        unset($this->cart);

        $this->dispatch('cart-synced', lines: $this->cart->lines->map(fn (CartLine $l) => [
            ...$l->item->cartPayload(),
            'qty' => $l->quantity,
        ])->values()->all());
    }

    #[Computed]
    public function cart(): Cart
    {
        return new Cart($this->quantities);
    }

    /** @return Collection<int, DeliveryDay> */
    #[Computed]
    public function days(): Collection
    {
        return (new DeliveryScheduler)->days();
    }

    #[Computed]
    public function zones(): Collection
    {
        return DeliveryZone::query()->active()->ordered()->get();
    }

    #[Computed]
    public function zone(): ?DeliveryZone
    {
        return $this->zoneId ? $this->zones->firstWhere('id', $this->zoneId) : null;
    }

    #[Computed]
    public function selectedDay(): ?DeliveryDay
    {
        return $this->date ? $this->days->first(fn (DeliveryDay $d) => $d->key() === $this->date) : null;
    }

    /** Ordering is open when there is somewhere to deliver, a day to deliver on, and a way to pay. */
    #[Computed]
    public function isOpen(): bool
    {
        return $this->zones->isNotEmpty()
            && $this->days->contains(fn (DeliveryDay $d) => $d->isAvailable())
            && PaymentMethods::get()->isReady();
    }

    public function deliveryFee(): int
    {
        return $this->zone?->fee ?? 0;
    }

    public function total(): int
    {
        return $this->cart->subtotal() + $this->deliveryFee();
    }

    public function mount(): void
    {
        // Pre-select the first open day and slot, and the only zone if there is one.
        $first = $this->days->first(fn (DeliveryDay $d) => $d->isAvailable());
        if ($first) {
            $this->date = $first->key();
            $this->slotId = collect($first->slots)->firstWhere('available', true)?->slot->id;
        }
        if ($this->zones->count() === 1) {
            $this->zoneId = $this->zones->first()->id;
        }
    }

    public function updatedDate(): void
    {
        // Keep the slot if it is still open on the new day, otherwise take the first open one.
        $day = $this->selectedDay;
        $options = collect($day?->slots ?? [])->where('available', true);
        if (! $options->contains(fn ($o) => $o->slot->id === $this->slotId)) {
            $this->slotId = $options->first()?->slot->id;
        }
    }

    public function next(): void
    {
        $this->validate($this->rulesFor($this->step), $this->messages());
        $this->step = min(self::STEPS, $this->step + 1);
        $this->dispatch('checkout-step');
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
        $this->dispatch('checkout-step');
    }

    public function goTo(int $step): void
    {
        if ($step < $this->step) {
            $this->step = max(1, $step);
            $this->dispatch('checkout-step');
        }
    }

    public function place(OrderPlacer $placer)
    {
        foreach ([1, 2] as $step) {
            $validator = validator($this->all(), $this->rulesFor($step), $this->messages());
            if ($validator->fails()) {
                $this->step = $step;
                $this->setErrorBag($validator->errors());
                if ($step === 2) {
                    // The chosen time may have filled up meanwhile: offer the next open one.
                    $this->updatedDate();
                }

                return null;
            }
        }

        if ($this->cart->isEmpty()) {
            $this->addError('cart', __('Your order is empty.'));

            return null;
        }

        try {
            $order = $placer->place(
                $this->cart,
                ['name' => $this->name, 'phone' => $this->phone, 'email' => $this->email],
                $this->date,
                $this->slotId,
                $this->zoneId,
                $this->address,
                $this->notes,
                app()->getLocale(),
            );
        } catch (SlotUnavailable) {
            unset($this->days, $this->selectedDay);
            $this->step = 2;
            $this->addError('slotId', __('Sorry, that time just filled up. Please pick another.'));
            $this->updatedDate();

            return null;
        }

        $this->dispatch('order-placed');

        return $this->redirect($order->publicUrl(app()->getLocale()));
    }

    protected function rulesFor(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => ['required', 'string', 'min:2', 'max:80'],
                'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                    if (! Customer::isValidPhone((string) $value)) {
                        $fail(__('Enter an Egyptian mobile number, like 01012345678.'));
                    }
                }],
                'email' => ['required', 'email', 'max:120'],
            ],
            2 => [
                'date' => ['required', Rule::in($this->days->filter(fn (DeliveryDay $d) => $d->isAvailable())->map->key()->all())],
                'slotId' => ['required', 'integer', function ($attribute, $value, $fail) {
                    $option = collect($this->selectedDay?->slots ?? [])->first(fn ($o) => $o->slot->id === (int) $value);
                    if (! $option?->available) {
                        $fail(__('Please pick an available time.'));
                    }
                }],
                'zoneId' => ['required', 'integer', Rule::in($this->zones->pluck('id')->all())],
                'address' => [$this->zone && ! $this->zone->is_pickup ? 'required' : 'nullable', 'string', 'max:500'],
                'notes' => ['nullable', 'string', 'max:500'],
            ],
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'name.required' => __('Please enter your name.'),
            'name.min' => __('Please enter your name.'),
            'phone.required' => __('Please enter your phone number.'),
            'email.required' => __('Please enter your email.'),
            'email.email' => __('This email doesn\'t look right.'),
            'date.required' => __('Please pick a day.'),
            'date.in' => __('Please pick an available day.'),
            'slotId.required' => __('Please pick a time.'),
            'zoneId.required' => __('Please pick your area.'),
            'zoneId.in' => __('Please pick your area.'),
            'address.required' => __('Please enter the address.'),
            '*.max' => __('This is too long.'),
        ];
    }

    public function render()
    {
        return view('shop.checkout');
    }
}
