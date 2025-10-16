<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command('backup:run --only-db')
                ->hourly()
                ->withoutOverlapping();
