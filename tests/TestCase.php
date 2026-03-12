<?php

namespace Xefi\LaravelOSDD\Tests;

use Xefi\LaravelOSDD\LaravelOSDDServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelOSDDServiceProvider::class];
    }
}
