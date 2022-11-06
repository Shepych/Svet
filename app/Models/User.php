<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token) {
        $this->notify(new ResetPasswordNotification($token));
    }

    # Поиск триггера по ID и USER_ID
    public function trigger($trigger_id, $user_id) {
        $result = $this->hasMany(Trigger::class, 'user_id')
            ->where('user_id', $user_id)->where('id', $trigger_id)->first();

        if(!isset($result)) {
            return false;
        }

        return $result;
    }

    # Сегодняшние триггеры
    public function triggersToday() {
        return $this->hasMany(Trigger::class, 'user_id')->where("created_at", "<", Carbon::tomorrow())->where("created_at", ">", Carbon::yesterday()->addDay())->count();
    }

    # Связь с календарём
    public function calendar() {
        return $this->hasMany(Calendar::class, 'user_id')->first();
    }
}
