<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Mockery;
use Illuminate\Support\Facades\Config;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::shouldReceive('get')
            ->with('laravelsimplecart.session_key')
            ->andReturn('laravel_simple_cart');
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
