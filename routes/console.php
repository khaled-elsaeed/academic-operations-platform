<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command('backup:run --only-db')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground();

// Clean up old backups daily at 2 AM
Schedule::command('backup:clean')
                ->dailyAt('02:00');

// Monitor backup health daily at 3 AM
Schedule::command('backup:monitor')
                ->dailyAt('03:00');