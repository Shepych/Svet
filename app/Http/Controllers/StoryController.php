<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Notes;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class StoryController extends Controller
{
    public static $maxStories = 5;

    # Добавление истории +
    public function create(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'content' => 'required',
        ],[
            'content.required' => 'Данные отсутствуют',
        ])->errors();
        # Валидация
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();
        # Ограничение на добавление - УЧИТЫВАЯ архивные записи !!!!!!!!!
        $stories = Story::where("created_at", "<", Carbon::tomorrow())->where("created_at", ">", Carbon::yesterday()->addDay())->where('user_id', $user->id)->count();
        $archive = Archive::where("created_at", "<", Carbon::tomorrow())->where("created_at", ">", Carbon::yesterday()->addDay())->where('user_id', $user->id)->count();
        if($stories + $archive >= self::$maxStories) {
            return ['status' => ['error' => 'Ограничение на добавление историй: ' . self::$maxStories]];
        }

        # Создание истории
        Story::create($user->id, $request);

        return ['status' => ['success' => 'История добавлена']];
    }

    # Редактирование истории +
    public function edit(Request $request, Story $story)
    {
        # Валидация
        $validate = Validator::make($request->all(), [
            'story_id' => 'required',
            'content' => 'required',
        ],[
            'story_id.required' => 'Введите ID истории',
            'content.required' => 'Данные отсутствуют',
        ])->errors();
        # Валидация
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();

        $story = $story->where('id', $request->story_id)->where('user_id', $user->id)->first();
        if(!$story) {
            return ['status' => ['error' => 'История не найдена']];
        }

        # Проверка - модерируется запись или нет
        if($story->moderator_id && !$story->status) {
            return ['status' => ['error' => 'Ваша история на модерации']];
        }

        # Изменить данные
        $story->title = $request->title;
        $story->content = $request->content;
        $story->status = false;
        $story->moderator_id = null;
        $story->update();

        return $story;
    }

    # Удаление истории +
    public function destroy(Request $request, Story $story)
    {
        # Валидация
        $validate = Validator::make($request->all(), [
            'story_id' => 'required',
        ],[
            'story_id.required' => 'Введите ID истории',
        ])->errors();
        # Валидация
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();
        $story = $story->where('id', $request->story_id)->where('user_id', $user->id)->first();
        if(!$story) {
            return ['status' => ['error' => 'История не найдена']];
        }

        # Проверка - модерируется запись или нет
        if($story->moderator_id && !$story->status) {
            return ['status' => ['error' => 'Ваша история на модерации']];
        }

        $story->delete();

        return ['status' => ['success' => 'История была удалена']];
    }

    # Получение списка историй +
    public function getList() {
        $stories = Story::where('status', true)->paginate(2);
        return $stories->items();
    }
}
