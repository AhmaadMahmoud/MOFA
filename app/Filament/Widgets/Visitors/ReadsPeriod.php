<?php

namespace App\Filament\Widgets\Visitors;

use App\Analytics\Report;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/** Widgets on the Visitors page read the period chosen at the top of the page. */
trait ReadsPeriod
{
    use InteractsWithPageFilters;

    private ?Report $report = null;

    protected function report(): Report
    {
        return $this->report ??= Report::for($this->pageFilters['period'] ?? null);
    }
}
