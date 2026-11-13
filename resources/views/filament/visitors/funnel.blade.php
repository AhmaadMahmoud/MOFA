<x-filament-widgets::widget>
    <x-filament::section :heading="__('From visit to order')" :description="__('How far visits get. Each step counts visits.')">
        <ol class="space-y-3">
            @foreach ($steps as $i => $step)
                <li>
                    <div class="mb-1 flex items-baseline justify-between gap-3 text-sm">
                        <span class="font-medium">{{ $step['label'] }}</span>
                        <span class="tabular-nums text-gray-600">
                            {{ number_format($step['count']) }}
                            @if ($i > 0)<span class="text-xs text-gray-500">({{ $step['pct'] }}%)</span>@endif
                        </span>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-[color:var(--mofa-hairline)]">
                        <div class="h-full rounded-full bg-[color:var(--mofa-terracotta)]" style="width: {{ max($step['pct'], $step['count'] > 0 ? 2 : 0) }}%"></div>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-filament::section>
</x-filament-widgets::widget>
