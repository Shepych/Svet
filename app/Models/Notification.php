<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $table = 'notices';

    # Отправка уведомления +
    public static function default($user_id, $address_id, $type) {
        # Определяем тип и на основе его формируем сообщение
        $content = NULL;
        switch ($type) {
            case 1:
                $content = 'Ваша история прошла модерацию';
                break;
            case 2:
                $content = 'Ваша история НЕ прошла модерацию';
                break;
        }

        # Отправляем запись в БД
        $notification = [
            'user_id' => $user_id,
            'content' => $content,
            'type' => $type,
            'general' => false,
            'address_id' => $address_id,
            'viewed' => '[]',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        Notification::insert($notification);
    }

    # Глобальное уведомление +
    public static function general($user, $request) {
        # Отправляем запись
        $notification = [
            'user_id' => $user->id,
            'content' => $request->text,
            'type' => null,
            'general' => true,
            'address_id' => null,
            'viewed' => '[]',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        Notification::insert($notification);
    }

}
