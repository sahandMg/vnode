<?php


namespace App\Repositories;


use App\Models\Inbound;
use App\Services\Http;
use App\Services\Utils;
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
        return DB::table('inbounds')->pluck('port')->toArray();
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
        $resp = shell_exec('sudo ufw insert 1 deny from ' . $ip . ' to any port ' . $port);
        if (str_contains($resp, 'Skipping inserting existing rule')) {
            shell_exec('sudo ufw deny from ' . $ip . ' to any port ' . $port);
        }
    }

    public static function releaseIp($ip, $port)
    {
        $resp = shell_exec('sudo insert 1 ufw allow from ' . $ip . ' to any port ' . $port);
        if (str_contains($resp, 'Skipping inserting existing rule')) {
            shell_exec('sudo ufw allow from ' . $ip . ' to any port ' . $port);
        }
    }

    public static function storeBlockedIP($ip, $port)
    {
        if (Cache::has('blocked')) {
            $data = Cache::get('blocked');
            if (isset($data[$port])) {
                if (!in_array($ip, $data[$port])) {
                    $data[$port][] = $ip;
                    Cache::forever('blocked', $data);
                }
            } else {
                $data[$port][] = $ip;
                Cache::forever('blocked', $data);
            }
        } else {
            Cache::forever('blocked', []);
        }
    }

    public static function checkIfIpIsWhiteListed($source_ip, $port)
    {
        $whiteList_ips = self::getWhiteListedIps($port);
        info(json_encode($whiteList_ips));
        if (in_array($source_ip, array_keys($whiteList_ips))) {
            return true;
        }
        return false;
    }

    public static function getWhiteListedIps($port)
    {
        if (Cache::has('allowed')) {
            $data = Cache::get('allowed');
            if (isset($data[$port])) {
                return Cache::get('allowed')[$port];
            } else {
                $data[$port] = [];
                Cache::forever('allowed', $data);
                return [];
            }
        } else {
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

    public static function removeIpFromWhiteList($port)
    {
        $sign = false;
        $data = Cache::get('allowed');
        foreach ($data[$port] as $ip => $date) {
            if (Carbon::now()->diffInMinutes($date) >= config('bot.expire_after')) {
                unset($data[$port][$ip]);
                Cache::forever('allowed', $data);
                $sign = true;
            }
        }
        return $sign;
    }

    public static function updateWhiteListedIpTime($ip, $port)
    {
        $data = Cache::get('allowed');
        $data[$port][$ip] = Carbon::now();
        Cache::forever('allowed', $data);
    }

    public static function checkIfAnyIpExpired($port)
    {
        return self::removeIpFromWhiteList($port);
    }

    public static function checkIfIpBlocked($ip)
    {
        $blocked_ips = self::_getBlockedIps();
        if (in_array($ip, $blocked_ips)) {
            return true;
        }
        return false;
    }

    private static function _getBlockedIps()
    {
        if (Cache::has('blocked')) {
            return Cache::get('blocked');
        } else {
            Cache::forever('blocked', []);
            return [];
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
        $update_url = config('bot.update_url') . $inbound->id;
        $inbound->enable = 1;
        $inbound->total = $inbound->total + $vol;
        $inbound_arr = Utils::prepareInboundForUpdate($inbound);
        Http::sendHttp($update_url, $inbound_arr);
        return $inbound;
    }

    public static function updateExpiry($remark)
    {
        $inbound = DB::table('inbounds')
            ->where('remark', $remark)
            ->first();
        if ($inbound->total == 64424509440) {
            $total = 64424509440;
        } elseif ($inbound->total > 64424509440) {
            $total = $inbound->total - ($inbound->up + $inbound->down) > 64424509440
                ? $inbound->total
                : $inbound->total - ($inbound->up + $inbound->down) + 64424509440;
        }
        $exp_date = Carbon::now()->addDays(33)->getPreciseTimestamp(3);
        DB::table('inbounds')
            ->where('remark', $remark)
            ->update([
                'expiry_time' => $exp_date,
                'enable' => 1,
                'up' => 0,
                'down' => 0,
                'total' => $total
            ]);
        $inbound->enable = 1;
        $inbound->total = $total;
        $inbound->down = 0;
        $inbound->up = 0;
        $inbound->expiry_time = $exp_date;
        $inbound_arr = Utils::prepareInboundForUpdate($inbound);
        $update_url = config('bot.update_url') . $inbound->id;
        Http::sendHttp($update_url, $inbound_arr);
        return $inbound;
    }

    public static function reconnect($remark)
    {
        $inbound = DB::table('inbounds')
            ->where('remark', $remark)
            ->first();
        DB::table('inbounds')
            ->where('remark', $remark)
            ->update([
                'enable' => 1,
            ]);
        $inbound->enable = 1;
        $inbound_arr = Utils::prepareInboundForUpdate($inbound);
        $update_url = config('bot.update_url') . $inbound->id;
        Http::sendHttp($update_url, $inbound_arr);
        return $inbound;
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

    public static function getActiveUserByPort($port)
    {
        return DB::table('inbounds')->where('enable', 1)->where('port', $port)->first();
    }
}