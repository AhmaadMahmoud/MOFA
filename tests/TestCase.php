<?php

namespace Tests;

use App\Support\Settings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Settings::flush();
    }

    /** What the SetLocale middleware does, for components tested without a request. */
    protected function useLocale(string $locale): void
    {
        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);
    }
}
