<?php

namespace App\Console;

use App\Http\Middleware\ApiDefense;
use App\Models\BlackList;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        # Удаление неподтверждённых аккаунтов пользователей каждые 2 часа
        $schedule->call(function () {
            $now = Carbon::now();
            $users = User::all();

            foreach ($users as $user) {
                if (Carbon::parse($user->created_at)->addMinute() <= Carbon::parse($now) && !empty($user->code)) {
                    User::where('id', $user->id)->delete();
                }
            }

        })->everyTwoHours();

        # Очистка устаревших токенов
        $schedule->command('auth:clear-resets')->everyFifteenMinutes();

        # Очистка чёрных IP списков
        $schedule->call(function () {
            $list = BlackList::all();

            foreach ($list as $ip) {
                if($ip->attempts >= ApiDefense::$limitAttempts && Carbon::parse($ip->updated_at)->addMinute() < Carbon::now()) {
                    BlackList::where('id', $ip->id)->delete();
                }
            }
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
