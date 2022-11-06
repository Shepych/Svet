<?php

namespace App\Http\Controllers;

use App\Models\Notes;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isType;

class NotesController extends Controller
{
    /**
     * * @OA\Post(
     *     tags={"Заметки"},
     *     path="/api/v1/notes/create",
     *     description="Добавление заметки",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Parameter(
             *     name="api_token",
             *     in="query",
             *     description="Защитный API токен"),
     *     @OA\Parameter(
             *     name="note",
             *     in="query",
             *     description="Текст заметки"),
     *     ),
     * @OA\Post(
     *     tags={"Заметки"},
     *     path="/api/v1/notes/delete",
     *     description="Удаление заметки",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Parameter(
             *     name="api_token",
             *     in="query",
             *     description="Защитный API токен"),
     *     @OA\Parameter(
             *     name="note_id",
             *     in="query",
             *     description="ID заметки"),
     *     )
     **/

    public static $maxNotes = 10;

    # Добавление заметки +
    public function create(Request $request) {
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

        $notes = Notes::where("created_at", "<", Carbon::tomorrow())->where("created_at", ">", Carbon::yesterday()->addDay())->count();
        # Ограничение на число заметок
        if($notes >= self::$maxNotes) {
            return ['status' => ['error' => 'Ограничение на добавление заметок: ' . self::$maxNotes]];
        }

        Notes::insert([
            'user_id' => $user->id,
            'note' => $request->note,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return [
            'status' => [
                'success' => 'Заметка добавлена'
            ],
        ];
    }

    # Удаление заметки +
    public function destroy(Request $request) {
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
        $note = Notes::where('user_id', $user->id)->where('id', $request->note_id);
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

    # Получение списка заметок +
    public function getList(Request $request) {
        $user = User::where('api_token', $request->api_token)->first();
        return Notes::where('user_id', $user->id)->paginate(5)->items();
    }
}
