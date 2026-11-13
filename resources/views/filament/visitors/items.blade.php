<x-filament-widgets::widget>
    <x-filament::section :heading="__('Most viewed items')" :description="__('Views of each item page, how many added it to the bag, and pieces ordered in this period.')">
        @if (empty($rows))
            <p class="text-sm text-gray-500">{{ __('No item has been viewed in this period yet.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[color:var(--mofa-hairline)] text-gray-500">
                            <th class="py-2 text-start font-medium">{{ __('Item') }}</th>
                            <th class="py-2 text-start font-medium">{{ __('Views') }}</th>
                            <th class="py-2 text-end font-medium">{{ __('People') }}</th>
                            <th class="py-2 text-end font-medium">{{ __('Added to bag') }}</th>
                            <th class="py-2 text-end font-medium">{{ __('Pieces ordered') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-b border-[color:var(--mofa-hairline)] last:border-0">
                                <td class="py-2.5 pe-3 font-medium">{{ $row['item']->localized('name') }}</td>
                                <td class="w-2/5 py-2.5 pe-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-[color:var(--mofa-hairline)]">
                                            <div class="h-full rounded-full bg-[color:var(--mofa-terracotta)]" style="width: {{ max($row['pct'], $row['views'] > 0 ? 2 : 0) }}%"></div>
                                        </div>
                                        <span class="w-10 text-end tabular-nums">{{ number_format($row['views']) }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 text-end tabular-nums">{{ number_format($row['visitors']) }}</td>
                                <td class="py-2.5 text-end tabular-nums">{{ number_format($row['added']) }}</td>
                                <td class="py-2.5 text-end tabular-nums font-semibold">{{ number_format($row['ordered']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
