<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Trigger extends Model
{
    protected $table = 'triggers';
    public $timestamps = false;

    public static $dailyLimitTriggers = 5000;

    use HasFactory;

    public static function create($user_id, $request, $triggersCount) {
        $result = json_decode($request, true);
        $allowed = self::$dailyLimitTriggers - $triggersCount;

        # Транзакция
        DB::transaction(function () use ($result, $user_id, $allowed) {
            foreach ($result as $item) {
                $data = [
                    'user_id' => $user_id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                # Сохранение оставшихся полей
                foreach ($item as $key => $value) {
                    switch ($key) {
                        case 'event':
                            $data['event'] = $value;
                            break;
                        case 'thoughts':
                            $data['thoughts'] = $value;
                            break;
                        case 'feeling':
                            $data['feeling'] = $value;
                            break;
                        case 'action':
                            $data['action'] = $value;
                            break;
                        default:
                            break;
                    }
                }

                if(!$allowed) {
                    return;
                }
                # Добавить запись в базу
                Trigger::insert($data);
                $allowed--;
            }
        });
    }
}
