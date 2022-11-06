<?php

namespace App\Http\Controllers;

use App\Models\Notes;
use App\Models\Trigger;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isNull;

class TriggerController extends Controller
{
    /**
     * * @OA\Post(
     *     tags={"Триггеры"},
     *     path="/api/v1/trigger/create",
     *     description="Создание триггера",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Parameter(
     *     name="api_token",
     *     in="query",
     *     description="Защитный API токен"),
     *     @OA\Parameter(
     *     name="event",
     *     in="query",
     *     description="Событие",
     * ),
     *     @OA\Parameter(
     *     name="feels",
     *     in="query",
     *     description="Ощущения",
     * ),
     *     @OA\Parameter(
     *     name="action",
     *     in="query",
     *     description="Действией",
     * ),
     *     @OA\Parameter(
     *     name="thoughts",
     *     in="query",
     *     description="Мысли",
     * )),
     *
     * @OA\Post(
     *     tags={"Триггеры"},
     *     path="/api/v1/trigger/thinking",
     *     description="Установка позитивного мышления спустя 24ч",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Parameter(
     *     name="api_token",
     *     in="query",
     *     description="Защитный API токен"),
     *     @OA\Parameter(
     *     name="thinking",
     *     in="query",
     *     description="Описание"
     * )),
     * @OA\Post(
     *     tags={"Триггеры"},
     *     path="/api/v1/trigger/delete",
     *     description="Удаление триггера",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Parameter(
     *     name="api_token",
     *     in="query",
     *     description="Защитный API токен"),
     *     @OA\Parameter(
     *     name="id",
     *     in="query",
     *     description="ID триггера"
     * )),
     */

    # Создание триггера
    public function trigger(Request $request) {
        $user = User::where('api_token', $request->api_token)->first();

        $triggersToday = $user->triggersToday();

        # Ограничение
        if($triggersToday >= Trigger::$dailyLimitTriggers) {
            return ['status' => ["error" => "Сегодня больше нельзя добавлять триггеры"]];
        }

        # Добавление триггера
        Trigger::create($user->id, $request);

        return [
            'status' => [
                'success' => 'Триггер добавлен'
            ]
        ];
    }

    # Создание позитивного мышления +
    public function thinking(Request $request) {
        $validate = Validator::make($request->all(), [
            'thinking' => 'required',
            'id' => 'required|integer',
        ],[
            'thinking.required' => 'Данные отсутствуют',
            'id.required' => 'ID триггера отсутствуют',
            'id.integer' => 'ID триггера может быть только целочисленным значением',
        ])->errors();
        # Валидация
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();

        # Проверить trigger_id на принадлежность к токену
        if(!$user->trigger($request->id, $user->id)) {
            return ['status' => ['error' => 'Триггер не найден']];
        }

        # Добавление данных
        Trigger::where('user_id', $user->id)->where('id', $request->id)->update([
            'thinking' => $request->thinking
        ]);

        return ['status' => ['success' => 'Позитивное мышление добавлено к триггеру']];
    }

    # Удаление триггера
    public function delete(Request $request) {
        $user = User::where('api_token', $request->api_token)->first();
        # Проверить trigger_id на принадлежность к токену
        if(!$user->trigger($request->id, $user->id)) {
            return ['status' => ['error' => 'Триггер не найден']];
        }

        Trigger::where('user_id', $user->id)->where('id', $request->id)->delete();
        return ['status' => ['success' => 'Триггер удалён']];
    }

    # Получение списка триггеров +
    public function getList(Request $request) {
        $user = User::where('api_token', $request->api_token)->first();
        return Trigger::where('user_id', $user->id)->paginate(5)->items();
    }
}
