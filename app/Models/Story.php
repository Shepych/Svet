<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    use HasFactory;
    protected $table = 'stories';

    public static function create($user_id, $request) {
        $data = [
            'user_id' => $user_id,
            'title' => $request->title ? $request->title : null,
            'content' => $request->content,
            'status' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        self::insert($data);
    }
}
