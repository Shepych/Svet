<?php

use App\Http\Controllers\ModeratorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\TriggerController;

# Регистрация
Route::post('/user/create', [UserController::class, 'create']);

# Подтверждение регистрации
Route::post('/verified', [UserController::class, 'sms']);

# Авторизация пользователя
Route::middleware('throttle:10')->post('/authorize', [UserController::class, 'auth']);

# Запрос на восстановление
Route::post('/recovery/password', [UserController::class, 'recovery']);

# Смена пароля
Route::post('/reset/password', [UserController::class, 'reset']);

# Список историй
Route::post('/story/list', [StoryController::class, 'getList']);

Route::middleware(['api_defense'])->group(function () {
    # Сохранение результатов теста депрессии
    Route::post('/calendar/depression', [CalendarController::class, 'depression']);

    # Сохранение результатов настроения
    Route::post('/calendar/mood', [CalendarController::class, 'mood']);

    # Получение календаря
    Route::post('/calendar/data', [CalendarController::class, 'data']);

    {   # Создание заметки
        Route::post('/notes/create', [NotesController::class, 'create']);
        # Удаление заметки
        Route::post('/notes/delete', [NotesController::class, 'destroy']);
        # Получение заметок
        Route::post('/notes/list', [NotesController::class, 'getList']);
    }

    {   # Создание триггера
        Route::post('/trigger/create', [TriggerController::class, 'trigger']);
        # Мышление после 24 часов
        Route::post('/trigger/thinking/create', [TriggerController::class, 'thinking']);
        # Удаление триггера
        Route::post('/trigger/delete', [TriggerController::class, 'delete']);
        # Список триггеров
        Route::post('/trigger/list', [TriggerController::class, 'getList']);
    }

    {   # Создание истории
        Route::post('/story/create', [StoryController::class, 'create']);
        Route::post('/story/edit', [StoryController::class, 'edit']);
        Route::post('/story/destroy', [StoryController::class, 'destroy']);
    }

    {   # Список уведомлений
        Route::post('/notifications/list', [NotificationController::class, 'getList']);
    }
});

# Контроллеры модератора
Route::middleware('api_moderator_defense')->controller(ModeratorController::class)->group(function () {
    # Выдача модерки
    Route::post('/moderator/extradition', 'extradition');
    # Анулирование модерки
    Route::post('/moderator/revoke', 'revokeRights');
});

Route::middleware(['api_defense', 'moderator_check'])->controller(ModeratorController::class)->group(function () {
    # Получение материала для модерирования
    Route::post('/moderator/story', 'story');
    # Вынесение вердикта
    Route::post('/moderator/verdict', 'verdict');
    # Список архивных записей
    Route::post('/moderator/archive', 'archive');
    # Глобальное уведомление
    Route::post('/notifications/general', [NotificationController::class, 'generalNotice']);
});
