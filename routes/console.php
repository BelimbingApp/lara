<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automated backups (daily at 2 AM) - production only
if (app()->environment('production')) {
    Schedule::command('belimbing:backup --type=full')
        ->dailyAt('02:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->onFailure(function () {
            \Illuminate\Support\Facades\Log::error('Scheduled backup failed');
        });
}
