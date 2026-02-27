<?php

use App\Console\Commands\ProcessDunning;
use App\Console\Commands\SendDueSoonReminders;
use Illuminate\Support\Facades\Schedule;

Schedule::command(SendDueSoonReminders::class)->dailyAt('09:00');
Schedule::command(ProcessDunning::class)->dailyAt('09:00');
