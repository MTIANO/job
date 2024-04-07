<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class MtUser extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    //protected $table = 'user';Base.php
    //const TABLE_NAME = 'user';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    protected $dateFormat = 'U';

    protected $fillable = [
        'winxin_id',
        'status',
        'created_at',
        'updated_at',
    ];


    public function getUserByWinXinId($wixin_id){
        return self::query()->where('winxin_id',$wixin_id)->first();
    }
}
