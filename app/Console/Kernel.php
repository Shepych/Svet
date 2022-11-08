<?php

namespace App\Console;

use App\Http\Middleware\ApiDefense;
use App\Models\Archive;
use App\Models\BlackList;
use App\Models\Notification;
use App\Models\Story;
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

        # Удаление уведомлений с недельным сроком годности
        $schedule->call(function () {
            $archive = Notification::where('created_at', '<', Carbon::now()->addWeek())->get();

            foreach ($archive as $item) {
                $item->delete();
            }
        })->everyFiveMinutes();

        # Снятие модерации с истории спустя 30 минут бездействия, для передачи другому модератору
        $schedule->call(function () {
            $stories = Story::where('moderator_id', '<>', NULL)->where('status', false)->where('updated_at', '<', Carbon::now()->addMinutes(-30))->get();

            foreach ($stories as $item) {
                $item->moderator_id = null;
                $item->update();
            }
        })->everyMinute();

        # Удаление архивных записей с месячным сроком годности
        $schedule->call(function () {
            $archive = Archive::where('created_at', '<', Carbon::now()->addMonth())->get();

            foreach ($archive as $item) {
                $item->delete();
            }
        })->everyTwoHours();
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
