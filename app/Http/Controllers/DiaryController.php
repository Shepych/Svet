<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\DiaryCheckList;
use App\Models\DiaryNote;
use App\Models\Notes;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class DiaryController extends Controller
{
    public static $maxNotes = 10;
    # Чеклист - добавление +
    public function checkListAdd(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'note' => 'required',
        ],[
            'note.required' => 'Напишите заметку',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();
        # Ограничение на добавление в день
        $checks = DiaryCheckList::where("created_at", "<", Carbon::tomorrow())->where("created_at", ">", Carbon::yesterday()->addDay())->where('user_id', $user->id)->count();
        if($checks >= self::$maxNotes) {
            return ['status' => ['error' => 'На сегодня лимит добавляемых записей исчерпан']];
        }

        # Создание записи в чек-лист
        DiaryCheckList::insert([
            'user_id' => $user->id,
            'note' => $request->note,
            'delete_note' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return ['status' => ['success' => 'Запись добавлена']];
    }

    # Чеклист - редактирование +
    public function checkListEdit(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'note' => 'required',
            'note_id' => 'required',
        ],[
            'note.required' => 'Напишите заметку',
            'note_id.required' => 'Введите ID заметки',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        # Поиск записи
        $user = User::where('api_token', $request->api_token)->first();
        $check = DiaryCheckList::where('user_id', $user->id)->where('id', $request->note_id)->first();
        if(!$check->exists()) {
            return ['status' => ['error' => 'Запись не найдена']];
        }

        # Замена данных
        $check->delete_note .= '<br>' . $check->note;
        $check->note = $request->note;
        $check->save();

        return [
            'status' => ['success' => 'Запись изменена'],
            'data' => $check,
        ];
    }

    # Чеклист - удаление +
    public function checkListDelete(Request $request) {
        $validate = Validator::make($request->all(), [
            'note_id' => 'required|integer',
        ],[
            'note_id.required' => 'ID отсутствует',
            'note_id.integer' => 'Номер заметки должен быть целым числом',
        ])->errors();
        # Валидация
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();
        $note = DiaryCheckList::where('user_id', $user->id)->where('id', $request->note_id);
        # Проверка на существование заметки по ID
        if(!$note->exists()) {
            return ['status' => ['error' => 'Заметка не найдена']];
        }

        # Удаление заметки
        $note->delete();

        return [
            'status' => [
                'success' => 'Заметка удалена'
            ],
        ];
    }

    # Получить все записи чек-листа +
    public function getCheckListData(Request $request) {
        $user = User::where('api_token', $request->api_token)->first();

        # Содержимое чек-пункта
        if($request->check_id) {
            $check = DiaryCheckList::where('id', $request->check_id)->where('user_id', $user->id)->first();
            if (!isset($check)) {
                return ['status' => ['error' => 'Запись не найдена']];
            }

            return $check;
        }

        return DiaryCheckList::where('user_id', $user->id)->paginate(5)->items();
    }

    # Заметка - добавление +
    public function noteAdd(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'note' => 'required',
        ],[
            'note.required' => 'Напишите заметку',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();
        # Ограничение на добавление в день
        $checks = DiaryNote::where("created_at", "<", Carbon::tomorrow())->where("created_at", ">", Carbon::yesterday()->addDay())->where('user_id', $user->id)->count();
        if($checks >= self::$maxNotes) {
            return ['status' => ['error' => 'На сегодня лимит добавляемых записей исчерпан']];
        }

        # Создание записи в чек-лист
        DiaryNote::insert([
            'user_id' => $user->id,
            'note' => $request->note,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return ['status' => ['success' => 'Заметка добавлена в дневник']];
    }

    # Заметка - удаление +
    public function noteDelete(Request $request) {
        $validate = Validator::make($request->all(), [
            'note_id' => 'required|integer',
        ],[
            'note_id.required' => 'ID отсутствует',
            'note_id.integer' => 'Номер заметки должен быть целым числом',
        ])->errors();
        # Валидация
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();
        $note = DiaryNote::where('user_id', $user->id)->where('id', $request->note_id);
        # Проверка на существование заметки по ID
        if(!$note->exists()) {
            return ['status' => ['error' => 'Заметка не найдена']];
        }

        # Удаление заметки
        $note->delete();

        return [
            'status' => [
                'success' => 'Заметка удалена'
            ],
        ];
    }

    # Получение списка заметок, а так же одной записи по ID +
    public function getNotesData(Request $request) {
        $user = User::where('api_token', $request->api_token)->first();

        # Содержимое заметки
        if($request->note_id) {
            $check = DiaryNote::where('id', $request->note_id)->where('user_id', $user->id)->first();
            if (!isset($check)) {
                return ['status' => ['error' => 'Заметка не найдена']];
            }

            return $check;
        }

        return DiaryNote::where('user_id', $user->id)->paginate(5)->items();
    }
}
