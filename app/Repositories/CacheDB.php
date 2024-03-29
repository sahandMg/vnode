<?php


namespace App\Repositories;


use Illuminate\Support\Facades\Cache;

class CacheDB
{
    public static function storeExtraRemark($remark)
    {
        // [br14.22 => 1]
        if (Cache::has('remarks')) {
            $r = Cache::get('remarks');
            if (in_array($remark, array_keys($r))) {
                $r[$remark] += 1;
            } else {
                $r[$remark] = 1;
            }
        } else {
            $r[$remark] = 1;
        }
        Cache::forever('remarks', $r);
    }

    public static function getExtraRemarks()
    {
        return Cache::get('remarks') ?? [];
    }

    public static function storeActiveSessions(array $sessions)
    {
        $tmp = 0;
        foreach ($sessions as $port => $ips) {
            $tmp += count($ips);
        }
        Cache::put('active_sessions', $tmp, 600);
    }

    public static function getActiveSessions()
    {
        return Cache::get('active_sessions');
    }
}