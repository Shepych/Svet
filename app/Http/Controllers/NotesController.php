<?php

namespace App\Http\Controllers;

use App\Models\Notes;
use App\Models\User;
use Illuminate\Http\Request;
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

    public static $maxNotes = 4;

    # Добавление заметки
    public function create(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'notes' => 'required|json',
        ],[
            'notes.required' => 'Напишите заметку',
        ])->errors();
        if($validate->any()) {
            return ['error' => $validate->all()];
        }

        $user = User::where('api_token', $request->api_token)->first();
        $notes = json_decode($user->notes, true);

        # Ограничение на число заметок
        if(count($notes) >= self::$maxNotes) {
            return ['error' => 'Ограничение на добавление заметок: ' . self::$maxNotes];
        }

        foreach (json_decode($request->notes, true) as $note) {
            $notes[] = $note;
        }

        $user->notes = $notes;
        $user->save();

        return ['success' => 'Заметка добавлена'];
    }

    # Удаление заметки
    public function destroy(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'note_id' => 'required|integer',
        ],[
            'note_id.required' => 'Напишите заметку',
            'note_id.integer' => 'Номер заметки должен быть целым числом',
        ])->errors();

        if($validate->any()) {
            return ['error' => $validate->all()];
        }

        $user = User::where('api_token', $request->api_token)->first();
        $notes = json_decode($user->notes);
        # Проверка на существование заметки по ID
        if(!isset($notes[$request->note_id - 1])) {
            return ['error' => 'Заметка не найдена'];
        }

        # Удаление заметки
        unset($notes[$request->note_id - 1]);
        $notes = array_values($notes);
        $user->notes = $notes;
        $user->save();

        return ['success' => 'Заметка №' . $request->note_id . ' удалена'];
    }
}
