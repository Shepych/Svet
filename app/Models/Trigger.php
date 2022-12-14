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

    public static $dailyLimitTriggers = 10;

    use HasFactory;

    public static function create($user_id, $request) {
        $data = [
            'user_id' => $user_id,
            'event' => $request->event,
            'thoughts' => $request->thoughts,
            'feeling' => $request->feeling,
            'action' => $request->action,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        Trigger::insert($data);
    }
}
