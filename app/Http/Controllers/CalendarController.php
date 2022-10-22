<?php

namespace App\Http\Controllers;

use App\Models\BlackList;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class CalendarController extends Controller
{
    /**
     * * @OA\Post(
     *     tags={"Календарь"},
     *     path="/api/v1/calendar/depression",
     *     description="Установка результатов теста на депрессию",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Parameter(
     *     name="api_token",
     *     in="query",
     *     description="Защитный API токен"),
     *     @OA\Parameter(
     *     name="json",
     *     in="query",
     *     description="JSOТ вида: {""22.10.2022"":{""depression"" : 90}, ""23.10.2022"":{""depression"" : 30}}")
     * ),
     * @OA\Post(
     *     tags={"Календарь"},
     *     path="/api/v1/calendar/mood",
     *     description="Установка настроений",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Parameter(
             *     name="api_token",
             *     in="query",
             *     description="Защитный API токен"),
     *     @OA\Parameter(
             *     name="json",
             *     in="query",
             *     description="JSON вида: {""16.10.2022"" : {""mood"" : [2,1,-1,-2]}, ""18.10.2022"" : {""mood"" : [0,0,2,-2]}}"". Где числа в квадратных скобках означают отрезок настроения по цифровой шкале от -2 до 2 включая 0, где -2 это самая низкая оценка, а 2 самая высокая")
     * ),
     */
    # Ежедневный тест на депрессию
    public function depression(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'json' => 'required|json',
        ],[
            'json.json' => 'Только JSON формат',
        ])->errors();
        if($validate->any()) {
            return ['error' => $validate->all()];
        }

        $user = User::where('api_token', $request->api_token);
        $user = $user->first();
        $depression_tests = json_decode($user->depression_tests, true);

        $days = json_decode($request->json, true);
        foreach ($days as $day => $points) {
            # Запретить замену результатов
            if(isset($depression_tests[$day])) {
                continue;
            }

            # Запретить вставлять невалидную дату
            $dayDate = explode('.', $day);
            if(!checkdate($dayDate[1], $dayDate[0], $dayDate[2])) {
                continue;
            }

            # Запретить вставлять дату ниже даты регистрации
            if(Carbon::parse(Carbon::parse($day)->format('d.m.Y')) < Carbon::parse(Carbon::parse($user->created_at)->format('d.m.Y'))) {
                continue;
            }

            # Запретить вставлять дату превышающую настоящее время
            if(Carbon::parse(Carbon::parse($day)->format('d.m.Y')) > Carbon::parse(date('d.m.Y'))) {
                continue;
            }

            # Валидация очков
            if(!is_int($points['depression']) || $points['depression'] < 0 || $points['depression'] > 100 || $points['depression'] == 0) {
                continue;
            }

            $depression_tests[$day]['depression'] = $points['depression'];
        }

        # Обновляем календарь депрессии
        $user->depression_tests = $depression_tests;
        $user->update();

        return $depression_tests;
    }

    # Ежедневные показатели настроения
    public function mood(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'json' => 'required|json',
        ],[
            'json.json' => 'Только JSON формат',
        ])->errors();
        if($validate->any()) {
            return ['error' => $validate->all()];
        }

        $user = User::where('api_token', $request->api_token);
        $user = $user->first();
        $userMood = json_decode($user->mood, true);
        $days = json_decode($request->json, true);
        $result = [];
        foreach ($days as $day => $mood) {
            # Запретить замену результатов КРОМЕ СЕГОДНЯШНЕГО ДНЯ
            if(isset($userMood[$day]) && Carbon::parse($day) != Carbon::parse(date('d.m.Y'))) {

                continue;
            }

            # Запретить вставлять невалидную дату
            $dayDate = explode('.', $day);
            if(!checkdate($dayDate[1], $dayDate[0], $dayDate[2])) {
                continue;
            }

            # Запретить вставлять дату ниже даты регистрации
            if(Carbon::parse(Carbon::parse($day)->format('d.m.Y')) < Carbon::parse(Carbon::parse($user->created_at)->format('d.m.Y'))) {
                continue;
            }

            # Запретить вставлять дату превышающую настоящее время
            if(Carbon::parse(Carbon::parse($day)->format('d.m.Y')) > Carbon::parse(date('d.m.Y'))) {
                continue;
            }

            # Валидация настроений
//            return $mood['mood'];
            foreach ($mood['mood'] as $item) {
                if(!is_int($item) || $item > 2 || $item < -2) {
                    return ['error' => 'Некорректные данные'];
                }
            }


            $result[$day] = $mood;
        }

        # Обновить данные
        foreach ($result as $day => $item) {
            # Данный день надо заменить в массиве $userMood
            $userMood[$day] = $item;
        }

        $user->mood = $userMood;
        $user->save();

        return $userMood;
    }
}
