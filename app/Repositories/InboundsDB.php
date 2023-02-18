<?php


namespace App\Repositories;


use App\Models\Inbound;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
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
        } else {
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

    public static function blockIp($ip, $port)
    {
        shell_exec('sudo ufw deny from ' . $ip . ' to any port ' . $port);
    }

    public static function releaseIp($ip, $port)
    {
        shell_exec('sudo ufw allow from ' . $ip . ' to any port ' . $port);
    }

    public static function storeBlockedIP($ip, $port)
    {
        if (Cache::has('blocked')) {
            $data = Cache::get('blocked');
            $data[$port][] = $ip;
            Cache::forever('blocked', $data);
        } else {
            $data[$port][] = $ip;
            Cache::forever('blocked', $data);
        }
    }

    public static function searchForWhiteListIps($source_ip, $port)
    {
        $whiteList_ips = self::getWhiteListedIps($port);
        if (in_array($source_ip, $whiteList_ips)) {
            return true;
        }
        return false;
    }

    public static function getWhiteListedIps($port)
    {
        if (Cache::has('allowed')) {
            if (isset(Cache::get('allowed')[$port])) {
                return Cache::get('allowed')[$port];
            }else {
                Cache::forever('allowed',[$port => []]);
                return [];
            }
        }else {
            Cache::forever('allowed', []);
            return [];
        }
    }

    public static function insertIpToWhiteList($ip, $port)
    {
        if (Cache::has('allowed')) {
            $data = Cache::get('allowed');
            $data[$port][$ip] = Carbon::now();
            Cache::forever('allowed', $data);
        } else {
            $data[$port][$ip] = Carbon::now();
            Cache::forever('allowed', $data);
        }
    }

    public static function removeIpFromWhiteList($ip, $port)
    {
        $data = Cache::get('allowed');
        if (isset($data[$port][$ip])) {
            unset($data[$port][$ip]);
        }
    }

    public static function updateWhiteListedIpTime($ip, $port)
    {
        $data = Cache::get('allowed');
        $data[$port][$ip] = Carbon::now();
        Cache::forever('allowed', $data);
    }

    public static function checkIfIpExpired($ip, $port)
    {
        $data = Cache::get('allowed');
        $date = $data[$port][$ip];
        if (Carbon::now()->diffInMinutes($date) > 5) {
            return true;
        }
        return false;
    }

    private static function _getBlockedIps($port)
    {
        if (Cache::has('blocked')) {
            return Cache::get('blocked')[$port];
        }
    }

    public static function storeUsageInCache($port, $usage)
    {
        if (Cache::has('usages')) {
            $data = Cache::get('usages');
            if (isset($data[$port])) {
                $data[$port] += $usage;
            } else {
                $data[$port] = $usage;
            }
            Cache::forever('usages', $data);
        } else {
            $data[$port] = $usage;
            Cache::forever('usages', $data);
        }
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
            } else {
                $ips_arr = unserialize($record->ips);
                $new_ips = array_values(array_unique(array_merge($ips_arr, $ips)));
                DB::table('ports')->where('port', $port)->update(['ips' => serialize($new_ips)]);
            }
        }
    }

    public static function getAllActivePorts()
    {
        return DB::table('inbounds')->where('enable', 1)->get()->pluck('port')->toArray();
    }
}