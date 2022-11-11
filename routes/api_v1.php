<?php

use App\Http\Controllers\ModeratorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\TriggerController;
use App\Http\Controllers\DiaryController;
use App\Http\Controllers\LetterController;

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
    Route::get('/calendar/data', [CalendarController::class, 'data']);

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

    {   # Запрос на смену телефона
        Route::post('/reset/phone', [UserController::class, 'resetPhone']);
        # Подтверждение смены телефона
        Route::post('/reset/phone/confirm', [UserController::class, 'resetPhoneConfirm']);
    }

    {   # Получение списка статей, а так же одной записи
        Route::post('/articles', [ArticleController::class, 'get']);
    }

    {   # Дневник - чеклист
        Route::post('/diary/checklist/add', [DiaryController::class, 'checkListAdd']);
        Route::post('/diary/checklist/edit', [DiaryController::class, 'checkListEdit']);
        Route::post('/diary/checklist/delete', [DiaryController::class, 'checkListDelete']);
        Route::get('/diary/checklist', [DiaryController::class, 'getCheckListData']);

        # Дневник - заметки
        Route::post('/diary/note/add', [DiaryController::class, 'noteAdd']);
        Route::post('/diary/note/delete', [DiaryController::class, 'noteDelete']);
        Route::get('/diary/notes', [DiaryController::class, 'getNotesData']);
    }

    {   # Письма
        Route::post('/letter/add', [LetterController::class, 'add']);
        Route::post('/letter/delete', [LetterController::class, 'delete']);
        Route::get('/letters', [LetterController::class, 'get']);
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

    {   # Добавление статьи
        Route::post('/article/create', [ArticleController::class, 'create']);
        # Изменение статьи
        Route::post('/article/edit', [ArticleController::class, 'edit']);
        # Удаление статьи
        Route::post('/article/delete', [ArticleController::class, 'delete']);
    }
});
