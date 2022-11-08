<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public static $dailyArticles = 10;
    # Создание статьи
    public function create(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'title' => 'required',
            'text' => 'required',
            'description' => 'required',
        ],[
            'title.required' => 'Заголовок отсутствует',
            'description.required' => 'Описание отсутствует',
            'text.required' => 'Текст отсутствует',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        # Ограничение
        $user = User::where('api_token', $request->api_token)->first();
        $articles = Article::where('user_id', $user->id)->where("created_at", "<", Carbon::tomorrow())->where("created_at", ">", Carbon::yesterday()->addDay())->count();
        if($articles >= self::$dailyArticles) {
            return ['status' => ['error' => 'Ежедневный лимит на добавление статей исчерпан']];
        }

        # Добавляем статью
        Article::insert([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'text' => $request->text,
            'link' => $request->link,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return ['status' => ['success' => 'Статья добавлена']];
    }

    # Изменение статьи
    public function edit(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'title' => 'required',
            'text' => 'required',
            'description' => 'required',
        ],[
            'title.required' => 'Заголовок отсутствует',
            'description.required' => 'Описание отсутствует',
            'text.required' => 'Текст отсутствует',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        # Проверка ID
        $article = Article::where('id', $request->article_id)->first();
        if(!isset($article)) {
            return ['status' => ['error' => 'Статья не найдена']];
        }

        $article->title = $request->title;
        $article->description = $request->description;
        $article->text = $request->text;

        if($request->link) {
            $article->link = $request->link;
        }

        $article->update();

        return ['status' => ['success' => 'Статья успешно изменена']];
    }

    # Удаление статьи
    public function delete(Request $request) {
        $article = Article::where('id', $request->article_id)->first();
        if(!isset($article)) {
            return ['status' => ['error' => 'Статья не найдена']];
        }

        $article->delete();
        return ['status' => ['success' => 'Статья успешно удалена']];
    }

    # Отображение контента
    public function get(Request $request) {
        $user = User::where('api_token', $request->api_token)->first();
        $viewed = json_decode($user->viewed_articles);

        # Страница статьи
        if($request->article_id) {
            $article = Article::where('id', $request->article_id)->first();
            if(!isset($article)) {
                return ['status' => ['error' => 'Статья не найдена']];
            }


            $found = false;
            foreach ($viewed as $item) {
                if($item == $request->article_id) $found = true;
            }

            if(!$found) {
                $viewed[] = $request->article_id;
            }
            $user->viewed_articles = $viewed;
            $user->update();

            return $article;
        }

        # Список статей
        $articles = Article::paginate(4);
        foreach ($articles as $item) {
            foreach ($viewed as $viewed_id) {
                if($item->id == $viewed_id) {
                    $item->viewed = true;
                }
            }
        }

        return $articles->items();
    }
}
