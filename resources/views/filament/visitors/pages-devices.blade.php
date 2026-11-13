<x-filament-widgets::widget>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-filament::section :heading="__('Pages')" :description="__('Views of each part of the site.')">
            @forelse ($pages as $row)
                <div class="flex items-baseline justify-between gap-3 border-b border-[color:var(--mofa-hairline)] py-2 text-sm last:border-0">
                    <span class="font-medium">{{ $row['label'] }}</span>
                    <span class="tabular-nums text-gray-600">{{ number_format($row['count']) }} <span class="text-xs text-gray-500">· {{ trans_choice(':count person|:count people', $row['visitors']) }}</span></span>
                </div>
            @empty
                <p class="text-sm text-gray-500">{{ __('No visits in this period yet.') }}</p>
            @endforelse
        </x-filament::section>

        @foreach ([[__('Devices'), $devices, 'var(--mofa-mauve)'], [__('Browsers'), $browsers, 'var(--mofa-olive)']] as [$heading, $rows, $color])
            <x-filament::section :heading="$heading" :description="__('Share of visits.')">
                @forelse ($rows as $row)
                    <div class="mb-3 last:mb-0">
                        <div class="mb-1 flex items-baseline justify-between gap-3 text-sm">
                            <span class="font-medium">{{ $row['label'] }}</span>
                            <span class="tabular-nums text-gray-600">{{ $row['pct'] }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-[color:var(--mofa-hairline)]">
                            <div class="h-full rounded-full" style="width: {{ max($row['pct'], 2) }}%; background-color: {{ $color }}"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No visits in this period yet.') }}</p>
                @endforelse
            </x-filament::section>
        @endforeach
    </div>
</x-filament-widgets::widget>
