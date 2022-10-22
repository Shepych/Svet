<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotesController;

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

Route::middleware('api_defense')->group(function () {
    # Сохранение результатов теста депрессии
    Route::post('/calendar/depression', [CalendarController::class, 'depression']);

    # Сохранение результатов настроения
    Route::post('/calendar/mood', [CalendarController::class, 'mood']);

    {   # Создание заметки
        Route::post('/notes/create', [NotesController::class, 'create']);
        # Удаление заметки
        Route::post('/notes/delete', [NotesController::class, 'destroy']);
    }
});
