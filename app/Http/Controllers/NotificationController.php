<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public static $maxGeneral = 5;
    # Список уведомлений для пользователя +
    public function getList(Request $request) {
        $user = User::where('api_token', $request->api_token)->first();
        $notifications = Notification::where('address_id', $user->id)->orWhere('address_id', null)->orderByDesc('created_at')->get();

        # Установка флажка ПРОСМОТРЕНО
        foreach ($notifications as $notice) {

            $firstInitViewed = $notice->viewed;

            if(isset($notice->viewed)) {
                $viewed = json_decode($notice->viewed);
                $found = false;
                foreach ($viewed as $view) {
                    if($view == $user->id) {

                        $found = true;
                        break;
                    }
                }

                if($found) {
                    continue;
                }
            }

            if($notice->general) {
                $viewed[] = $user->id;
                $notice->viewed = $viewed;
            } else {
                $notice->viewed = '[' . $user->id .']';
            }

            $notice->update();

            if($notice->general) {
                $notice->viewed = $firstInitViewed;
            } else {
                $notice->viewed = null;
            }
            $notice->checked = false;
        }

        return $notifications;
    }

    # Глобальное уведомление +
    public function generalNotice(Request $request) {
        # Валидация
        $validate = Validator::make($request->all(), [
            'text' => 'required',
        ],[
            'text.required' => 'Введите текст уведомления',
        ])->errors();
        if($validate->any()) {
            return ['status' => ['error' => $validate->all()]];
        }

        $user = User::where('api_token', $request->api_token)->first();
        # Ограничение на добавление - УЧИТЫВАЯ архивные записи !!!!!!!!!
        $generalsCount = Notification::where("created_at", "<", Carbon::tomorrow())->where("created_at", ">", Carbon::yesterday()->addDay())->where('general', true)->where('user_id', $user->id)->count();
        if($generalsCount >= self::$maxGeneral) {
            return ['status' => ['error' => 'Ограничение на отправку глобальных уведомлений: ' . self::$maxGeneral]];
        }

        Notification::general($user, $request);

        return ['status' => ['success' => 'Уведомление отправлено']];
    }
}
