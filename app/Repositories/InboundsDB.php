<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class InboundsDB
{
    public static function getUserByRemark($remark)
    {
        return DB::table('inbounds')->where('remark', $remark)->first();
    }

    public static function updateNetworkTrafficByPort($port, $sent, $received)
    {
        $record = DB::table('inbounds')->where('port', $port)->first();
        if (!is_null($record)) {
            DB::table('inbounds')->where('port', $port)
                ->update(['up' => $record->up + $sent,
                    'down' => $record->down + $received
                ]);
        }
    }

    public static function getAllPorts()
    {
        return DB::table('inbounds')->where('enable', 1)->pluck('port')->toArray();
    }

    public static function getAllInbounds()
    {
        return DB::table('inbounds')->get();
    }

    public static function disableAccountByPort($port)
    {
        return DB::table('inbounds')->where('port', $port)->update(['enable' => 0]);
    }
}