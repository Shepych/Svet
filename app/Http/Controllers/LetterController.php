<?php

namespace App\Http\Controllers;

use App\Models\DiaryNote;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class LetterController extends Controller
{
    public static $maxLetters = 10;

    # Добавление +
    public function add(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'text' => 'required',
        ],[
            'text.required' => 'Напишите письмо',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();
        # Ограничение на добавление в день
        $checks = Letter::where("created_at", "<", Carbon::tomorrow())->where("created_at", ">", Carbon::yesterday()->addDay())->where('user_id', $user->id)->count();
        if($checks >= self::$maxLetters) {
            return ['status' => ['error' => 'На сегодня лимит добавляемых писем исчерпан']];
        }

        # Создание записи в чек-лист
        Letter::insert([
            'user_id' => $user->id,
            'text' => $request->text,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return ['status' => ['success' => 'Письмо добавлено']];
    }

    # Удаление +
    public function delete(Request $request) {
        $validate = Validator::make($request->all(), [
            'letter_id' => 'required|integer',
        ],[
            'letter_id.required' => 'ID отсутствует',
            'letter_id.integer' => 'ID письма должно быть целым числом',
        ])->errors();
        # Валидация
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();
        $letter = Letter::where('user_id', $user->id)->where('id', $request->letter_id);
        # Проверка на существование заметки по ID
        if(!$letter->exists()) {
            return ['status' => ['error' => 'Письмо не найдено']];
        }

        # Удаление заметки
        $letter->delete();

        return [
            'status' => [
                'success' => 'Письмо удалено'
            ],
        ];
    }

    # Получение +
    public function get(Request $request) {
        $user = User::where('api_token', $request->api_token)->first();

        # Содержимое письма
        if($request->letter_id) {
            $letter = Letter::where('id', $request->letter_id)->where('user_id', $user->id)->first();
            if (!isset($letter)) {
                return ['status' => ['error' => 'Письмо не найдено']];
            }

            return $letter;
        }

        return Letter::where('user_id', $user->id)->paginate(5)->items();
    }
}
