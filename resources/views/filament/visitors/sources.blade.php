<x-filament-widgets::widget>
    <x-filament::section :heading="__('Where visitors come from')" :description="__('Worked out on its own from the app or site each visit came from.')">
        @if (empty($rows))
            <p class="text-sm text-gray-500">{{ __('No visits in this period yet.') }}</p>
        @else
            <ul class="space-y-3">
                @foreach ($rows as $row)
                    <li>
                        <div class="mb-1 flex items-baseline justify-between gap-3 text-sm">
                            <span class="font-medium">{{ $row['label'] }}</span>
                            <span class="tabular-nums text-gray-600">
                                {{ trans_choice(':count visit|:count visits', $row['visits']) }}
                                @if ($row['orders'] > 0)
                                    · <span class="text-[color:var(--mofa-terracotta)]">{{ trans_choice(':count site order|:count site orders', $row['orders']) }}</span>
                                @endif
                            </span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-[color:var(--mofa-hairline)]">
                            <div class="h-full rounded-full bg-[color:var(--mofa-olive)]" style="width: {{ max($row['pct'], 2) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
