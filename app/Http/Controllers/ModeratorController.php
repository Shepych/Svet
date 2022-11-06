<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Notification;
use App\Models\Story;
use App\Models\User;
use Carbon\Carbon;
use Dirape\Token\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ModeratorController extends Controller
{
    public static $api_token_1 = 'HdyvCcXdEmGufma8KtFcFughlWF0rI4Rye7Z9X9Vrl7T4WP4Ga8ffgnLp1HQARTOeQFmRnVd4VHQwUamwXQpcJA0WHvc2eIYG3Cg';
    public static $api_token_2 = 'fkDnPNPqpLZmrRyyaB06VikNMBrKbpFB1MmsptSos68lujCMUhw0YXJRYxBex6VYf8jTm2Aiwi0qpq43N92ONvB0vBwgTWzZaKzW';

    # Выдача модерки пользователю +
    public function extradition(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'user_id' => 'required',
        ],[
            'user_id.required' => 'Введите ID пользователя',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        # Выдача модерки
        $user = User::where('id', $request->user_id)->first();

        if($user) $user->assignRole('moderator');
        else return ['status' => ['error' => 'Пользователь не найден']];

        return ['status' => ['success' => 'Права выданы']];
    }

    # Снятие модерки +
    public function revokeRights(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'user_id' => 'required',
        ],[
            'user_id.required' => 'Введите ID пользователя',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        # Ауннулирование прав
        $user = User::where('id', $request->user_id)->first();

        if($user) $user->removeRole('moderator');
        else return ['status' => ['error' => 'Пользователь не найден']];

        return ['status' => ['success' => 'Права аннулированы']];
    }

    # Получение истории для модерирования +
    public function story(Request $request) {
        $user = User::where('api_token', $request->api_token)->first();
        $story = Story::where('status', false)->where('moderator_id', $user->id)->first();

        if(!$story) {
            $story = Story::where('status', false)->where('moderator_id', null)->first();
            if($story) {
                $story->moderator_id = $user->id;
                $story->update();
            } else {
                return ['status' => ['error' => 'Пока нечего модерировать']];
            }
        }

        return $story;
    }

    # Вынесение вердикта +
    public function verdict(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'story_id' => 'required',
            'passed' => 'required',
        ],[
            'story_id.required' => 'Введите ID истории',
            'passed.required' => 'Решение не принято',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        # ID истории
        $user = User::where('api_token', $request->api_token)->first();
        $story = Story::where('id', $request->story_id)->where('moderator_id', $user->id)->first();
        if(!$story) {
            return ['status' => ['error' => 'История не найдена']];
        }

        if($story->status == 1) {
            return ['status' => ['error' => 'История уже модерировалась']];
        }

        switch ($request->passed) {
            case 1:
                # Отправляем уведомление о прохождении модерации
                Notification::default($story->moderator_id, $story->user_id, 1);
                $story->status = true;
                $story->moderated = $story->moderator_id;
                $story->moderator_id = null;
                $story->update();
                break;
            case 0:
                # Отправляем запись в архив
                Archive::insert([
                    'user_id' => $story->moderator_id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'moderator_id' => $user->id,
                    'story_created_at' => $story->created_at,
                    'story_updated_at' => $story->updated_at,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                # Отправляем уведомление о провале модерации
                Notification::default($story->moderator_id, $story->user_id, 2);
                $story->delete();
                return ['status' => ['success' => 'Модерация НЕ пройдена']];
        }

        return ['status' => ['success' => 'Модерация пройдена']];
    }

    # Получение архива с пагинацией +
    public function archive() {
        $list = Archive::paginate(1);
        return $list->items();
    }
}
