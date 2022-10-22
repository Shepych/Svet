<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlackList extends Model
{
    use HasFactory;

    protected $table = 'black_list';
    protected $fillable = ['ip'];

    public static function attempt($ipRequest) {
        $ip = self::where('ip', $ipRequest);

        # Если ip не найден - то добавляем его в таблицу
        if(!$ip->exists()) {
            self::create([
                'ip' => $ipRequest,
                'attempts' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } else {
            $ip->increment('attempts');
            $ip->first()->update();
        }
    }
}
