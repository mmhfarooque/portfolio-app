<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Weekly stats digest — every Monday at 9am server time
Schedule::command('stats:weekly')->weeklyOn(1, '09:00');

// Catch any derivative that was never warmed — a failed job, a photo
// published straight from the admin, a newly added width or format.
Schedule::command('photos:variants')->dailyAt('03:30')->withoutOverlapping();
