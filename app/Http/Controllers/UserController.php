<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Dirape\Token\Token;

class UserController extends Controller
{
    /**
     * @OA\Info(title="Svet API", version="1.0"),
     * * @OA\Post(
     *     tags={"Пользователь"},
     *     path="/api/v1/user/create",
     *     description="Регистрация",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Parameter(
             *     name="email",
             *     in="query",
             *     description="Почта"),
     *     @OA\Parameter(
             *     name="password",
             *     in="query",
             *     description="Пароль"),
     *     @OA\Parameter(
             *     name="login",
             *     in="query",
             *     description="Логин"),
     *     @OA\Parameter(
             *     name="phone",
             *     in="query",
             *     description="Телефон"),
     * ),
     *    @OA\Post(
     *     tags={"Пользователь"},
     *     path="/api/v1/verified",
     *     description="Подтверждение регистрации по SMS (на данный момент по почте)",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Response(response="404", description="error"),
     *     @OA\Parameter(
             *     name="user_id",
             *     in="query",
             *     description="ID пользователя",),
     *     @OA\Parameter(
             *     name="code",
             *     in="query",
             *     description="Код подтверждения",)
     * ),
     *
     *
     *  @OA\Post(
     *     tags={"Пользователь"},
     *     path="/api/v1/authorize",
     *     description="Авторизация",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Response(response="404", description="error"),
     *     @OA\Parameter(
     *     name="email",
     *     in="query",
     *     description="E-Mail",),
     *     @OA\Parameter(
     *     name="password",
     *     in="query",
     *     description="Пароль",)
     * ),
     * @OA\Post(
     *     tags={"Пользователь"},
     *     path="/api/v1/recovery/password",
     *     description="Запрос (отправка кода) на смену пароля",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Response(response="404", description="error"),
     *     @OA\Parameter(
             *     name="email",
             *     in="query",
             *     description="E-Mail для восстановления",)
     * ),
     * @OA\Post(
     *     tags={"Пользователь"},
     *     path="/api/v1/reset/password",
     *     description="Подтверждение смены пароля",
     *     @OA\Response(response="200", description="The data"),
     *     @OA\Response(response="404", description="error"),
     *     @OA\Parameter(
     *     name="email",
     *     in="query",
     *     description="E-Mail",),
     *     @OA\Parameter(
     *     name="code",
     *     in="query",
     *     description="Код из E-Mail сообщения",),
     *     @OA\Parameter(
     *     name="password1",
     *     in="query",
     *     description="Пароль 1",),
     *     @OA\Parameter(
     *     name="password2",
     *     in="query",
     *     description="Пароль 2",),
     * ),
     */

    # Максимальное количество неверных попыток для сброса пароля
    public static $rememberAttempts = 6;

    # Создание пользователя
    public function create(Request $request) {
        # Валидация
        $validate = Validator::make([
            'login' => $request->login,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => $request->password,
        ], [
            'login' => 'required|min:3|max:40',
            'phone' => 'required|unique:users',
            'email' => 'required|unique:users|email',
            'password' => 'required|max:100',
        ], [
            'login.unique' => 'Имя пользователя уже зарегистрировано',
            'login.required' => 'Отсутствует имя пользователя',
            'login.min' => 'Слишком короткое имя пользователя',
            'login.max' => 'Слишком длинное имя пользователя',
            'phone.required' => 'Телефон отсутствует',
            'phone.unique' => 'Номер телефона занят',
            'email.required' => 'Почта отсутствует',
            'email.unique' => 'Адрес почты занят',
            'email.email' => 'Некорректный адрес почты',
            'password.required' => 'Пароль отсутствует',
            'password.max' => 'Слишком длинный пароль',
        ])->errors()->all();

        if($validate) {
            return response()->json(['status' => ['errors' => $validate]]);
        }

        # Генератор SMS кода
        $faker = Factory::create();
        $verificationCode = $faker->numerify('######');

        $user = new User();
        $user->login = $request->login;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = Hash::make($request->password);
        $user->code = $verificationCode;
        $user->code_sent_at = Carbon::now();
        $user->api_token = (new Token())->Unique('users', 'api_token', 100);
        $user->save();

        # Вернуть ID пользоваетял
        return response()->json([
            'status' => ['success' => 'Пользователь успешно зарегистрирован'],
            'user_id' => $user->id,
            'verification_code' => $verificationCode,
        ]);
    }

    # SMS подтверждение
    public function sms(Request $request) {
        # Выслать новый код спустя время (тоже параметр + поле в БД)
        $user = User::where('id', $request->user_id);
        if(!$user->exists()) return response()->json(['status' => ['error' => 'Пользователь не найден']]);

        $user = $user->first();
        if($user->code == null) return response()->json(['status' => ['error' => 'Код уже подтверждён']]);

        # Отправка нового кода
        if($request->refresh) {
            # Проверка по времени
            if(Carbon::parse($user->code_sent_at)->addMinutes(10) < Carbon::now() ) {
                $faker = Factory::create();

                # Отправка нового кода и обновление даты отправки
                $user->code = $faker->numerify('######');
                $user->code_sent_at = Carbon::now();
                $user->update();

                return response()->json(['status' => ['success' => 'Новый код отправлен по SMS']]);
            } else {
                return response()->json(['status' => ['error' => 'Подождите 10 минут']]);
            }
        }

        # Обработка исключения
        if(!$user) {
            return response()->json(['status' => ['error' => 'Пользователь не найден']]);
        }

        if($user->code == $request->code) {
            # Обнуление поля кода
            $user->code = null;
            $user->update();
            return response()->json([
                'status' => ['success' => 'Аккаунт успешно подтверждён'],
                'user' => User::where('id', $request->user_id)->first()
            ]);
        }

        return response()->json(['status' => ['error' => 'Неверный код подтверждения']]);
    }

    # Авторизация в приложение
    public function auth(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ],[
            'email.required' => 'Введите E-Mail',
            'password.required' => 'Введите пароль',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        # Проверка на подтверждение аккаунта
        $user = User::where('email', $request->email)->first();


        if(!empty($user->code)) {
            return response()->json(['status' => ['error' => 'Аккаунт не подтверждён']]);
        }

        # Проверка данных
        $credentials = $request->validate([
            'email' => ['required'],
            'password' => ['required'],
        ]);

        # Если данные верные - возвращаем массив с данными для пользователя
        if (Auth::attempt($credentials)) {
            return response()->json([
                'status' => ['success' => 'Успешная авторизация'],
                'user' => $user,
                'triggers' => null,
                'notes' => null,
            ]);
        }

        return response()->json(['status' => ['error' => 'Неверные данные']]);
    }

    # Отправка кода для восстановления
    public function recovery(Request $request) {
        $user = User::where('email', $request->email)->first();

        if(!$user) {
            return ['status' => ['error' => 'Пользователь не найден']];
        }

        # Повторная отправка кода только через минуту
        if($user->remember_token != null && Carbon::parse($user->remember_sent_at)->addMinute() >= Carbon::now()) {
            return ['status' => ['error' => 'Новый код можно отправить только через минуту']];
        }

        # Генератор кода
        $faker = Factory::create();
        # Обновляем данные в базе
        $user->remember_token = $faker->numerify('#######');
        $user->remember_sent_at = Carbon::now();
        $user->remember_attempts = 0;
        $user->update();

        # Отправка письма
        Mail::send(['text' => 'mails.recovery'], ['email' => $request->email, 'code' => $user->remember_token], function ($message) use ($request) {
            $message->to($request->email, 'Кому')->subject('Recovery test');
            $message->from($request->email, 'От кого')->subject('Recovery test');
        });

        return ['status' => ['success' => 'Код восстановления отправлен на почту']];
    }

    # Смена пароля
    public function reset(Request $request) {
        $user = User::where('email', $request->email)->first();
        # Проверить по sent_at

        if(!$user) {
            return ['status' => ['error' => 'Пользователь не найден']];
        }

        # Проверка на лимит попыток
        if($user->remember_attempts >= self::$rememberAttempts) {
            return ['status' => ['error' => 'Лимит попыток исчерпан - запросите новый код']];
        }

        # Проверка правильности кода
        if($user->remember_token != $request->code) {
            # Обновляем счетчик попыток
            $user->remember_attempts+= 1;
            $user->update();
            return ['status' => ['error' => 'Неверный код']];
        }

        # Валидация
        $validate = Validator::make($request->all(), [
            'password1' => 'required|min:8',
        ],[
            'password1.required' => 'Введите новый пароль',
            'password1.min' => 'Пароль должен содержать минимум 8 символов',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        if($request->password1 != $request->password2) {
            return ['status' => ['error' => 'Пароли не совпадают']];
        }

        # Обновить Hash
        $user->password = Hash::make($request->password1);
        $user->remember_attempts = 0;
        $user->remember_token = null;
        $user->remember_sent_at = null;
        $user->update();

        return [
            'status' => ['success' => 'Пароль изменён'],
            'password' => $user->password
        ];
    }
}
