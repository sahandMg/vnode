<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class UserDB
{
    public static function getUserData()
    {
        return DB::table('users')->first();
    }
}