<?php


namespace App\Repositories;


use App\Models\Inbound;
use App\Services\Http;
use App\Services\Utils;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class InboundsDB
{
    public static function getUserByRemark($remark)
    {
        $remark = strtolower($remark);
        $record = DB::table('client_traffics')
            ->where('email', $remark)
            ->select('*', 'client_traffics.up as up', 'client_traffics.down as down', 'client_traffics.total as total', 'client_traffics.expiry_time as expiry_time', 'client_traffics.enable as enable')
            ->join('inbounds', 'client_traffics.inbound_id', '=', 'inbounds.id')
            ->first();
        $settings = json_decode($record->settings);
        $r = collect($settings->clients)->filter(function ($r) use ($remark) {
            return $r->email == $remark;
        });
        $record->enable = $r->first()->enable;
        return $record;
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

    public static function getAllCommonPorts()
    {
        $common_remark = config('bot.common_remark');
        return DB::table('inbounds')->where('remark', 'like', "%$common_remark%")->pluck('port')->toArray();
    }

    public static function getAllInbounds()
    {
        return DB::table('inbounds')->get();
    }

    public static function disableAccountByPort($port)
    {
        return DB::table('inbounds')->where('port', $port)->update(['enable' => 0]);
    }

    public static function getLatestInbound($remarkTempla)
    {
        return DB::table('inbounds')
            ->where('remark', 'like', "%$remarkTempla%")
            ->orderBy('id', 'desc')
            ->first();
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
        $remark = strtolower($remark);
        $inbound = DB::table('client_traffics')
            ->select('*', 'client_traffics.up as up', 'client_traffics.down as down', 'client_traffics.total as total', 'client_traffics.expiry_time as expiry_time')
            ->where('email', $remark)
            ->join('inbounds', 'client_traffics.inbound_id', '=', 'inbounds.id')
            ->first();
        DB::table('client_traffics')
            ->where('email', $remark)
            ->update(['total' => $inbound->total + $vol, 'enable' => 1]);

        $settings = json_decode($inbound->settings);
        $user_id = '';
        $total = $inbound->total + $vol;
        foreach ($settings->clients as $client) {
            if ($client->email == $remark) {
                $client->enable = true;
                $client->totalGB = $total;
                $user_id = $client->id;
                break;
            }
        }
        $inbound_arr = ['id' => $inbound->id, 'settings' => json_encode(['clients' => [$client]])];
        $user = UserDB::getUserData();
        $login_url = config('bot.login_url') . '?username=' . $user->username . '&password=' . $user->password;
        $cookie = trim(Http::sendHttpLogin($login_url));
        $update_url = config('bot.update_url') . $user_id;
        Http::sendHttp($update_url, $inbound_arr, ['Cookie:' . $cookie]);
        return $inbound;
    }

    public static function updateExpiry($remark)
    {
        $remark = strtolower($remark);
        $inbound = DB::table('client_traffics')
            ->select('*', 'client_traffics.up as up', 'client_traffics.down as down', 'client_traffics.total as total', 'client_traffics.expiry_time as expiry_time')
            ->where('email', $remark)
            ->join('inbounds', 'client_traffics.inbound_id', '=', 'inbounds.id')
            ->first();
        $base = 64424509440;
        $used = $inbound->up + $inbound->down;
        if ($inbound->total == $base) {
            $total = 64424509440;
        } elseif ($inbound->total > $base && $used >= $base) {
            $remain = $inbound->total - $used;
            $total = $remain + $base;
        } elseif ($inbound->total > $base && $used < $base) {
            $total = $inbound->total;
        } else {
            $total = $inbound->total;
        }
        $settings = json_decode($inbound->settings);
        $agent = request()->get('agent') ?? 'user';
        $exp_date = $agent == 'user' ?
            Jalalian::now()->addMonths()->toCarbon()->getPreciseTimestamp(3)
            :
            Jalalian::now()->addMonths()->toCarbon()->getPreciseTimestamp(3);
        DB::table('client_traffics')
            ->where('email', $remark)
            ->update([
                'expiry_time' => $exp_date,
                'enable' => 1,
                'up' => 0,
                'down' => 0,
                'total' => $total
            ]);
        $user_id = '';
        foreach ($settings->clients as $client) {
            if ($client->email == $remark) {
                $client->enable = true;
                $client->totalGB = $total;
                $client->expiryTime = $exp_date;
                $user_id = $client->id;
                break;
            }
        }
        $inbound->down = 0;
        $inbound->up = 0;
        $inbound_arr = ['id' => $inbound->id, 'settings' => json_encode(['clients' => [$client]])];
        $user = UserDB::getUserData();
        $login_url = config('bot.login_url') . '?username=' . $user->username . '&password=' . $user->password;
        $cookie = trim(Http::sendHttpLogin($login_url));
        $update_url = config('bot.update_url') . $user_id;
        Http::sendHttp($update_url, $inbound_arr, ['Cookie:' . $cookie]);
        $inbound->expiry_time = $exp_date;
        return $inbound;
    }

    public static function addDays($remark, $days_num)
    {
        $remark = strtolower($remark);
        $inbound = DB::table('client_traffics')
            ->where('email', $remark)
            ->first();
        $ts_in_sec = (int)round($inbound->expiry_time / 1000);
        $exp_date = Carbon::parse($ts_in_sec)->addDays($days_num)->getPreciseTimestamp(3);
        DB::table('client_traffics')
            ->where('email', $remark)
            ->update([
                'expiry_time' => $exp_date,
            ]);
        $inbound->expiry_time = $exp_date;
        return $inbound;
    }

    public static function restart($remark)
    {
        self::disconnect($remark);
        self::reconnect($remark);
    }

    public static function reconnect($remark)
    {
        $remark = strtolower($remark);
        $inbound = DB::table('client_traffics')
            ->where('email', $remark)
            ->join('inbounds', 'client_traffics.inbound_id', '=', 'inbounds.id')
            ->first();
        DB::table('client_traffics')
            ->where('email', $remark)
            ->update([
                'enable' => 1,
            ]);
        $settings = json_decode($inbound->settings);
        foreach ($settings->clients as $client) {
            if ($client->email == $remark) {
                $client->enable = true;
                $user_id = $client->id;
                break;
            }
        }
        $inbound_arr = ['id' => $inbound->id, 'settings' => json_encode(['clients' => [$client]])];
        $user = UserDB::getUserData();
        $login_url = config('bot.login_url') . '?username=' . $user->username . '&password=' . $user->password;
        $cookie = trim(Http::sendHttpLogin($login_url));
        $update_url = config('bot.update_url') . $user_id;
        Http::sendHttp($update_url, $inbound_arr, ['Cookie:' . $cookie]);
        return $inbound;
    }

    public static function disconnect($remark)
    {
        $remark = strtolower($remark);
        $inbound = DB::table('client_traffics')
            ->where('email', $remark)
            ->join('inbounds', 'client_traffics.inbound_id', '=', 'inbounds.id')
            ->first();
        DB::table('client_traffics')
            ->where('email', $remark)
            ->update([
                'enable' => 0,
            ]);
        $settings = json_decode($inbound->settings);
        foreach ($settings->clients as $client) {
            if ($client->email == $remark) {
                $client->enable = false;
                $user_id = $client->id;
                break;
            }
        }
        $inbound_arr = ['id' => $inbound->id, 'settings' => json_encode(['clients' => [$client]])];
        $user = UserDB::getUserData();
        $login_url = config('bot.login_url') . '?username=' . $user->username . '&password=' . $user->password;
        $cookie = trim(Http::sendHttpLogin($login_url));
        $update_url = config('bot.update_url') . $user_id;
        Http::sendHttp($update_url, $inbound_arr, ['Cookie:' . $cookie]);
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
            $records = DB::table('ports')->where('port', $port)->whereDate('created_at', '=', Carbon::today())->get();
            if (count($records) == 0) {
                DB::table('ports')->insert([
                    'port' => $port,
                    'ips' => serialize($ips),
                    'created_at' => Carbon::now()
                ]);
            } else {
                foreach ($records as $record) {
                    $ips_arr = unserialize($record->ips);
                    $new_ips = array_values(array_unique(array_merge($ips_arr, $ips)));
                    DB::table('ports')
                        ->where('port', $port)
                        ->whereDate('created_at', '=', Carbon::today())
                        ->update(['ips' => serialize($new_ips), 'updated_at' => Carbon::now()
                        ]);
                }
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