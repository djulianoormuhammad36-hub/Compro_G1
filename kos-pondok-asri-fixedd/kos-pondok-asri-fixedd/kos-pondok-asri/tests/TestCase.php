<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Halaman React dirender lewat Vite; di test tidak perlu aset hasil build.
        $this->withoutVite();
    }
}
