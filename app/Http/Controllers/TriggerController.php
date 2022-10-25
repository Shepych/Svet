<?php

namespace App\Http\Controllers;

use App\Models\Trigger;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isNull;

class TriggerController extends Controller
{
    # Создание триггера +
    public function trigger(Request $request) {
        $validate = Validator::make($request->all(), [
            'triggers' => 'required|json',
        ],[
            'triggers.required' => 'Данные отсутствуют',
            'triggers.json' => 'Только JSON формат',
        ])->errors();
        # Валидация
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();
        $triggersToday = $user->triggersToday();

        # Ограничение
        if($triggersToday >= Trigger::$dailyLimitTriggers) {
            return ['status' => ["error" => "Сегодня больше нельзя создавать тригеры"]];
        }

        # Добавление новых записей
        Trigger::create($user->id, $request->triggers, $triggersToday);

        return [
            'status' => [
                'success' => 'Триггеры добавлены'
            ]
        ];
    }

    # Создание позитивного мышления
    public function thinking(Request $request) {
        $validate = Validator::make($request->all(), [
            'thinking' => 'required|json',
        ],[
            'thinking.required' => 'Данные отсутствуют',
            'thinking.json' => 'Только JSON формат',
        ])->errors();
        # Валидация
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();


        $thinking = json_decode($request->thinking, true);

        # Транзакция
        DB::transaction(function () use ($thinking, $user, &$error) {
            foreach ($thinking as $item) {
                $data = [
                    'updated_at' => Carbon::now(),
                ];

                # Добавление позитивного мышления
                foreach ($item as $key => $value) {
                    $trigger = $user->trigger($key, $user->id);
                    # Проверка на принадлежность с токену
                    if(!$trigger) {
                        if(count($thinking) == 1) {
                            $error = 'Триггер не найден';
                            return;
                        }
                        continue;
                    }
                    # Проверка на NULL
                    if(isset($trigger->thinking)) {
                        if(count($thinking) == 1) {
                            $error = 'Позитивное мышление уже было добавлено к этому триггеру';
                            return;
                        }
                        continue;
                    }
                    # Проверка на 24 часа
                    if(Carbon::parse($trigger->created_at) < Carbon::now()->addHours(-24)) {
                        if(count($thinking) == 1) {
                            $error = '24 часа ещё не прошли';
                            return;
                        }
                        continue;
                    }
                    $data['thinking'] = $value;
                    Trigger::where('id', $key)->update($data);
                }
            }
        }, 6);

        if($error) {
            return ['status' => ['error' => $error]];
        }

        return ['status' => ['success' => 'Позитивное мышление добавлено к триггеру']];
    }

    # Удаление триггера
    public function delete(Request $request) {
        $user = User::where('api_token', $request->api_token)->first();
        # Проверить trigger_id на принадлежность к токену
        if(!$user->trigger($request->trigger_id, $user->id)) {
            return ['status' => ['error' => 'Триггер не найден']];
        }

        Trigger::destroy($request->trigger_id);
        return ['status' => ['success' => 'Триггер удалён']];
    }
}
