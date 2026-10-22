<x-layouts.app :title="__('Order').' '.$order->number" :mobile-bar="false" noindex livewire
               :alternates="collect(\App\Support\Locales::supported())->mapWithKeys(fn ($l) => [$l => $order->publicUrl($l)])->all()">
    <livewire:order-page :order="$order" />
</x-layouts.app>
