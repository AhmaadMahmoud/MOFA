@php
    use App\Support\Costing;

    /** @var array $summary  from App\Support\Costing::summary() */
    $problems = [
        'unpriced' => __('No purchase price yet'),
        'no-amount' => __('Add the amount'),
        'unit-mismatch' => __('Unit differs from how it is bought'),
    ];
    $color = Costing::marginColor($summary['margin']);
@endphp
<div class="mofa-cost">
    @if ($summary['lines'] === [])
        <p class="mofa-cost-empty">{{ __('Add ingredients with their amounts to see the cost.') }}</p>
    @else
        <ul class="mofa-cost-lines">
            @foreach ($summary['lines'] as $line)
                <li>
                    <span>{{ $line['name'] }}</span>
                    @if ($line['problem'])
                        <span class="mofa-cost-problem">{{ $problems[$line['problem']] }}</span>
                    @else
                        <span class="mofa-cost-num">{{ Costing::money($line['cost']) }}</span>
                    @endif
                </li>
            @endforeach
            @if ($summary['packaging'])
                <li><span>{{ __('Packaging and extras') }}</span><span class="mofa-cost-num">{{ Costing::money($summary['packaging']) }}</span></li>
            @endif
        </ul>

        <dl class="mofa-cost-totals">
            <div><dt>{{ __('Cost per item') }}</dt><dd class="mofa-cost-num">{{ Costing::money($summary['total']) }}</dd></div>
            <div><dt>{{ __('Selling price') }}</dt><dd class="mofa-cost-num">{{ Costing::money($summary['price']) }}</dd></div>
            <div class="mofa-cost-profit"><dt>{{ __('Profit per item') }}</dt><dd class="mofa-cost-num">{{ Costing::money($summary['profit']) }}</dd></div>
        </dl>

        @if ($summary['margin'] !== null)
            <div class="mofa-cost-margin">
                <x-filament::badge :color="$color" size="lg">{{ __('Margin :n%', ['n' => number_format($summary['margin'], 0)]) }}</x-filament::badge>
                @unless ($summary['complete'])
                    <span class="mofa-cost-problem">{{ __('Not final: some ingredients are missing a cost.') }}</span>
                @endunless
            </div>
        @endif
    @endif
</div>
