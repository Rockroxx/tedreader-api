<?php

namespace App\Console;

use App\Console\Commands\ConvertPackageToJson;
use App\Console\Commands\GetDailyPackage;
use App\Console\Commands\GetMonthlyPackage;
use App\Console\Commands\ImportCategories;
use App\Console\Commands\ImportJsonIntoDatabase;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        GetDailyPackage::class,
        GetMonthlyPackage::class,
        ConvertPackageToJson::class,
        ImportJsonIntoDatabase::class,
        ImportCategories::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
