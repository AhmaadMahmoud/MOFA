@php
    use App\Enums\OrderStatus;
    use App\Support\Money;
    use App\Support\Settings;

    $locale = app()->getLocale();
    $status = $order->status;
    // Waiting for the money: the customer pays, sends the receipt on WhatsApp, Faten confirms.
    $awaiting = in_array($status, [OrderStatus::PendingPayment, OrderStatus::PaymentReview], true) && ! $expired;
    $pay = $method->viewData($order);
    $help = Settings::whatsappLink(__('Hi MOFA, a question about order :number', ['number' => $order->number]));

    [$headline, $sub] = match (true) {
        $expired => [__('This order was cancelled'), __('The payment didn\'t reach us, so the order was cancelled.')],
        $status === OrderStatus::Cancelled => [__('This order was cancelled'), $order->cancel_reason],
        $awaiting => [__('Almost done: pay to confirm'), __('Your place is saved. Pay and send us the receipt, and we confirm your order.')],
        $status === OrderStatus::Confirmed => [__('Your order is confirmed!'), __('Faten will bake it fresh for you.')],
        $status === OrderStatus::OutForDelivery => [$order->is_pickup ? __('Your order is ready for pickup') : __('Your order is on its way'), __('Enjoy your morning!')],
        $status === OrderStatus::Delivered => [__('Delivered. Enjoy!'), __('Thank you for ordering from MOFA.')],
    };

    $progress = [__('Order placed'), __('Paid and confirmed'), $order->is_pickup ? __('Ready') : __('On its way'), __('Delivered')];
    $reached = match ($status) {
        OrderStatus::PendingPayment, OrderStatus::PaymentReview => 0,
        OrderStatus::Confirmed => 1,
        OrderStatus::OutForDelivery => 2,
        OrderStatus::Delivered => 3,
        default => -1,
    };
@endphp
<div @if ($awaiting) wire:poll.30s @endif class="mx-auto max-w-[70rem] px-4 pt-2 pb-16 lg:px-10 lg:pt-6">

    {{-- ---------- Where the order stands ---------- --}}
    <header class="mb-6 flex flex-col gap-2 lg:mb-8">
        <p class="m-0 text-sm text-ink-muted">{{ __('Order') }} <span dir="ltr" class="font-semibold text-brown">{{ $order->number }}</span></p>
        <h1 class="m-0 font-serif text-[2.25rem] leading-[1.1] font-bold text-balance lg:text-5xl">{{ $headline }}</h1>
        @if ($sub)
            <p class="m-0 max-w-xl text-lg text-ink-muted">{{ $sub }}</p>
        @endif
    </header>

    <div class="grid grid-cols-[minmax(0,1fr)] items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-10">
        <div class="flex flex-col gap-6">
            @if ($awaiting)
                {{-- ---------- How to pay: three steps ---------- --}}
                <section class="rounded-lg bg-paper" aria-labelledby="pay-title">
                    <div class="border-b border-brown/12 px-5 py-4 sm:px-7">
                        <h2 id="pay-title" class="m-0 font-serif text-[1.5rem] font-bold">{{ __('How to pay') }}</h2>
                    </div>

                    <ol class="m-0 list-none p-0">
                        {{-- 1. Transfer --}}
                        <li class="flex gap-4 px-5 py-6 sm:px-7">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brown text-sm font-semibold text-paper">1</span>
                            <div class="flex min-w-0 flex-1 flex-col gap-4">
                                <div>
                                    <h3 class="m-0 text-lg font-semibold">{{ __('Transfer the amount with InstaPay') }}</h3>
                                    @if ($pay['name'])
                                        <p class="m-0 mt-0.5 text-sm text-ink-muted">{{ __('The account name shows as :name.', ['name' => $pay['name']]) }}</p>
                                    @endif
                                </div>

                                {{-- minmax(0,1fr): a long address must wrap inside its card, never widen the page. --}}
                                <div class="grid grid-cols-[minmax(0,1fr)] gap-2.5 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                                    <div x-data="copy(@js(number_format($order->total / 100, $order->total % 100 ? 2 : 0, '.', '')))" class="flex items-center justify-between gap-3 rounded-md bg-brown p-4 text-paper sm:col-span-2">
                                        <div class="flex flex-col">
                                            <span class="text-sm text-paper/70">{{ __('Amount to pay') }}</span>
                                            <span class="font-serif text-[2.25rem] leading-none font-bold">{{ Money::format($order->total) }}</span>
                                        </div>
                                        <button type="button" @click="copy()" class="flex min-h-11 shrink-0 items-center gap-1.5 rounded-pill bg-paper/12 px-4 text-sm font-medium hover:bg-paper/20">
                                            <x-mofa.icon name="copy" :size="18" x-show="!copied" />
                                            <x-mofa.icon name="check" :size="18" x-show="copied" x-cloak />
                                            <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))"></span>
                                        </button>
                                    </div>

                                    @foreach ([['label' => __('InstaPay address'), 'value' => $pay['address']], ['label' => __('Or the mobile number'), 'value' => $pay['phone']]] as $row)
                                        @continue(blank($row['value']))
                                        <div x-data="copy(@js($row['value']))" class="flex items-center justify-between gap-3 rounded-md border border-brown/15 bg-[#fffaf4] p-4">
                                            <div class="flex min-w-0 flex-col">
                                                <span class="text-sm text-ink-muted">{{ $row['label'] }}</span>
                                                <span dir="ltr" class="text-start text-lg leading-snug font-semibold break-words [overflow-wrap:anywhere] rtl:text-end">{{ $row['value'] }}</span>
                                            </div>
                                            <button type="button" @click="copy()" class="flex size-11 shrink-0 items-center justify-center rounded-pill border border-brown/20 hover:bg-cream/50"
                                                    :aria-label="copied ? @js(__('Copied')) : @js(__('Copy')) + ' ' + @js($row['label'])">
                                                <x-mofa.icon name="copy" :size="18" x-show="!copied" />
                                                <x-mofa.icon name="check" :size="18" x-show="copied" x-cloak />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="flex flex-col gap-2.5 sm:flex-row">
                                    @if ($pay['qr'])
                                        <div x-data="{ qr: false }" class="sm:flex-1">
                                            <button type="button" @click="qr = !qr" class="btn btn-ghost h-12 w-full" :aria-expanded="qr.toString()">{{ __('Show the QR code') }}</button>
                                            <div x-show="qr" x-collapse class="flex justify-center pt-3">
                                                <img src="{{ $pay['qr'] }}" alt="{{ __('InstaPay QR code') }}" class="w-52 max-w-full rounded-md bg-white p-2">
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </li>

                        {{-- 2. Receipt on WhatsApp --}}
                        <li class="flex gap-4 border-t border-brown/12 px-5 py-6 sm:px-7">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brown text-sm font-semibold text-paper">2</span>
                            <div class="flex min-w-0 flex-1 flex-col gap-4">
                                <div>
                                    <h3 class="m-0 text-lg font-semibold">{{ __('Send us the receipt on WhatsApp') }}</h3>
                                    <p class="m-0 mt-0.5 text-ink-muted">{{ __('Take a screenshot of the successful transfer and send it in the chat. Your order number is already written in the message.') }}</p>
                                </div>
                                @if ($pay['whatsapp'])
                                    <x-button :href="$pay['whatsapp']" target="_blank" rel="noopener" class="h-13 w-full normal-case! tracking-normal! text-base!">
                                        <x-mofa.icon name="chat-send" :size="22" /> {{ __('Send on WhatsApp') }}
                                    </x-button>
                                @endif
                            </div>
                        </li>

                        {{-- 3. Faten confirms --}}
                        <li class="flex gap-4 border-t border-brown/12 px-5 py-6 sm:px-7">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full border-[1.5px] border-brown/30 text-sm font-semibold text-ink-muted">3</span>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <h3 class="m-0 text-lg font-semibold">{{ __('We confirm your order') }}</h3>
                                <p class="m-0 mt-0.5 text-ink-muted">{{ __('Once we see the payment, we confirm the order and email you. This page updates by itself.') }}</p>
                            </div>
                        </li>
                    </ol>
                </section>
            @elseif ($reached >= 0)
                {{-- ---------- Progress ---------- --}}
                <section class="rounded-lg bg-paper px-5 py-6 sm:px-7" aria-label="{{ __('Order progress') }}">
                    <ol class="m-0 grid list-none grid-cols-4 gap-2 p-0">
                        @foreach ($progress as $i => $label)
                            <li class="flex flex-col gap-2" @if ($i === $reached) aria-current="step" @endif>
                                <span @class(['h-1.5 rounded-pill', 'bg-brown' => $i <= $reached, 'bg-brown/15' => $i > $reached])></span>
                                <span @class(['text-sm leading-tight', 'font-semibold' => $i === $reached, 'text-ink-muted' => $i !== $reached])>{{ $label }}</span>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @else
                {{-- ---------- Expired or cancelled ---------- --}}
                <section class="flex flex-col items-start gap-4 rounded-lg bg-paper px-5 py-6 sm:px-7">
                    <p class="m-0 text-ink-muted">{{ __('If you already paid, send us the receipt on WhatsApp and we will sort it out.') }}</p>
                    <div class="flex flex-wrap gap-2.5">
                        <x-button :href="route('menu')">{{ __('Order again') }}</x-button>
                        @if ($help)
                            <x-button :href="$help" variant="ghost" target="_blank" rel="noopener">{{ __('Message us on WhatsApp') }}</x-button>
                        @endif
                    </div>
                </section>
            @endif

            @if ($help && ! $expired && $status !== OrderStatus::Cancelled)
                <p class="m-0 text-sm text-ink-muted">
                    {{ __('Questions about your order?') }}
                    <a href="{{ $help }}" target="_blank" rel="noopener" class="font-semibold text-brown">{{ __('Message us on WhatsApp') }}</a>
                </p>
            @endif
        </div>

        {{-- ---------- The receipt ---------- --}}
        <aside class="mofa-receipt text-paper lg:sticky lg:top-6" aria-labelledby="summary-title">
            <div class="px-5 pt-5 pb-4">
                <h2 id="summary-title" class="m-0 font-serif text-[1.5rem] leading-tight font-bold">{{ __('Your order') }}</h2>
                <p class="m-0 text-sm text-paper/70 rtl:text-end" dir="ltr">{{ $order->number }}</p>
            </div>

            <dl class="m-0 mx-5 flex flex-col gap-3 border-t border-dashed border-paper/25 pt-4 text-sm">
                <div class="flex flex-col">
                    <dt class="text-paper/65">{{ $order->is_pickup ? __('Pickup') : __('Delivery') }}</dt>
                    <dd class="m-0 font-semibold">{{ $order->delivery_date->locale($locale)->translatedFormat('l j F') }} · {{ $order->slotLabel() }}</dd>
                </div>
                <div class="flex flex-col">
                    <dt class="text-paper/65">{{ __('Area') }}</dt>
                    <dd class="m-0">{{ $order->zoneName() }}@if (! $order->is_pickup && $order->address) · <span dir="auto">{{ $order->address }}</span>@endif</dd>
                </div>
            </dl>

            <ul class="m-0 mx-5 mt-4 flex list-none flex-col gap-2.5 border-t border-dashed border-paper/25 p-0 pt-4">
                @foreach ($order->items as $line)
                    <li class="flex items-baseline justify-between gap-3">
                        <span class="min-w-0">{{ $line->localized('name') }} <span class="text-paper/65">× {{ $line->quantity }}</span></span>
                        <span class="shrink-0">{{ Money::format($line->line_total) }}</span>
                    </li>
                @endforeach
                <li class="flex items-baseline justify-between gap-3 text-paper/80">
                    <span>{{ __('Delivery fee') }}</span>
                    <span class="shrink-0">{{ $order->delivery_fee > 0 ? Money::format($order->delivery_fee) : __('Free') }}</span>
                </li>
            </ul>

            <div class="mx-5 mt-4 flex items-baseline justify-between border-t border-dashed border-paper/25 pt-4 pb-6">
                <span class="font-semibold">{{ __('Total') }}</span>
                <span class="font-serif text-[2rem] leading-none font-bold">{{ Money::format($order->total) }}</span>
            </div>
        </aside>
    </div>

    <p class="m-0 mt-8 text-center text-xs text-ink-muted">{{ __('Save this page to come back to it. Once your order is confirmed, the email we send has the link too.') }}</p>
</div>
