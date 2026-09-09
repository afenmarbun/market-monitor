<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('market:tick')->everyFiveSeconds()->withoutOverlapping(1);
