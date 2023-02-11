<?php


namespace App\Repositories;


use App\Models\Inbound;
use Carbon\Carbon;
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
        }else{
            self::updateAllAvailableAccounts($sent, $received);
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

    public static function updateAllAvailableAccounts($sent, $received)
    {
        $active_accounts = DB::table('inbounds')->where('enable', 1)->count();
        $normalized_sent = (int)($sent / $active_accounts);
        $normalized_received = (int)($received / $active_accounts);
        $records = DB::table('inbounds')->get();
        DB::beginTransaction();
        foreach ($records as $record) {
            DB::table('inbounds')->where('enabled', 1)
                ->update([
                    'up' => $record->up + $normalized_sent,
                    'down' => $record->down + $normalized_received
                ]);
        }
        DB::commit();
    }

    public static function updateUserVol($remark, $vol)
    {
        $inbound = DB::table('inbounds')->where('remark', $remark)->first();
        DB::table('inbounds')
            ->where('remark', $remark)
            ->update(['total' => $inbound->total + $vol, 'enable' => 1]);
    }

    public static function updateExpiry($remark)
    {
        DB::table('inbounds')
            ->where('remark', $remark)
            ->update(['expiry_time' => Carbon::now()->addMonth()->getPreciseTimestamp(3)]);
    }

    public static function setAccountDate($port)
    {
        $inbound = DB::table('inbounds')->where('port', $port)->where('expiry_time', 0)->first();
        if (!is_null($inbound)) {
            DB::table('inbounds')->where('port', $port)->update(['expiry_time' => Carbon::now()->addMonth()->getPreciseTimestamp(3)]);
        }
    }

    public static function storePorts($ports)
    {
        foreach ($ports as $port => $ips) {
            $record = DB::table('ports')->where('port', $port)->first();
            if (is_null($record)) {
                DB::table('ports')->insert(['port' => $port, 'ips' => serialize($ips)]);
            }else {
                $ips_arr = unserialize($record->ips);
                $new_ips = array_values(array_unique(array_merge($ips_arr, $ips)));
                DB::table('ports')->where('port', $port)->update(['ips' => serialize($new_ips)]);
            }
        }
    }
}