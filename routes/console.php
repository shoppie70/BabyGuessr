<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fortune:sync-batch')->everyFiveMinutes()->withoutOverlapping();

