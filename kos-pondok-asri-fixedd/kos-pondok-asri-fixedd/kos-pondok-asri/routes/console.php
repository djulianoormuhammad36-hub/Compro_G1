<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hanguskan booking kedaluwarsa otomatis (butuh cron: * * * * * php artisan schedule:run)
Schedule::command('booking:kedaluwarsa')->everyFiveMinutes();
