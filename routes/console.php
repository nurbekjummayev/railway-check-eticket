<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tickets:check')->everyFiveMinutes();
Schedule::command('tickets:check --date='.now()->addDays(2)->format('Y-m-d'))->everyFiveMinutes();
Schedule::command('tickets:check --date='.now()->addDays(3)->format('Y-m-d'))->everyFiveMinutes();
