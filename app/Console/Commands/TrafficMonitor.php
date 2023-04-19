<?php

namespace App\Console\Commands;

use App\Jobs\TrafficHandlerJob;
use App\Repositories\InboundsDB;
use App\Services\Http;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Net_SSH2;


class TrafficMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'traffic';
    protected $description = 'Command description';
    public $exception_ports = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
//        include(app_path('Services/phpseclib/Net/SSH2.php'));
//        $ssh = new Net_SSH2('80.91.208.138');
//        if (!$ssh->login('root', 'Ss44644831')) {
//            exit('Login Failed');
//        }
//        $txt = $ssh->exec("iftop -P -n -N -i ens160 -t -s 5 -L 150");
//        $ssh->disconnect();
//        Cache::forever('traffic', $txt);
//        $txt = Cache::get('traffic');

//        Add theses lines to cronttab -e
        //* * * * * php /var/www/html/vnode/artisan traffic
        //* * * * * sleep 30; php /var/www/html/vnode/artisan traffic
        $txt = shell_exec("sudo iftop -P -n -N -t -s 25 -L 150");
        $t = array_filter(explode(PHP_EOL, $txt));
        $cumulative = array_splice($t, 7, 200);
        $sum = 0;
        $ports = [];
        $port_div = [];
        $remarks = [];
        $all_ports = InboundsDB::getAllPorts();
        for ($i = 0; $i < count($cumulative); $i += 2) {
            try {
                $tmp = array_values(array_filter(explode(' ', $cumulative[$i])));
                if (str_contains($tmp[1], '255.255.255.255')) {
                    continue;
                }
                $ip = explode(':', $tmp[1])[0];
                $port = explode(':', $tmp[1])[1];
                $source = array_values(array_filter(explode(' ', $cumulative[$i + 1])));
                $source_ip = explode(':', $source[0])[0];
                if (!in_array($port, $all_ports)) {
                    continue;
                }
                if (!isset($ports[$port])) {
                    $ports[$port] = $source_ip;
                    $port_div[$port][] = $source_ip;
                    InboundsDB::setAccountDate($port);
                } elseif (!in_array($source_ip, $port_div[$port])) {
                    $port_div[$port][] = $source_ip;
                }
                if (count(array_unique($port_div[$port])) > 2 && $port != env('TRAFFIC_PORT') && !in_array($port, $this->exception_ports)) {
                    $record = InboundsDB::getActiveUserByPort($port);
                    if (!is_null($record) && !in_array($record->remark, $remarks)) {
                        $remarks[] = $record->remark.' ip: '.$source_ip;
//                        InboundsDB::disableAccountByPort($port);
                    }
                }
//                if (env('UNIQUE_IP') == 1 && $port != env('TRAFFIC_PORT') && !in_array($port, $this->exception_ports)) {
//                    info("searching for $source_ip -> $port");
//                    // if ip is not blocked then go for white list check
//                    if (!InboundsDB::checkIfIpBlocked($source_ip)) {
//                        info("$source_ip is not blocked. looking for white listed ips ...");
//                        // update time if ip is white listed
//                        if (InboundsDB::checkIfIpIsWhiteListed($source_ip, $port)) {
//                            info("update time for $source_ip -> $port");
//                            InboundsDB::updateWhiteListedIpTime($source_ip, $port);
//                        } else {
//                            info("$source_ip is not whitelisted ...");
//                            // check how many ips are white listed
//                            // if > 2 then check for the expiry time
//                            if (count(InboundsDB::getWhiteListedIps($port)) == 2) {
//                                // if current ip is not white listed then block it! else update time
//                                if (!in_array($source_ip, InboundsDB::getWhiteListedIps($port))) {
//                                    if (!InboundsDB::checkIfAnyIpExpired($port)) {
//                                        // if its not reached then block the ip
//                                        Log::info("$source_ip in $port has been disabled");
//                                        info("block $source_ip -> $port");
//                                        InboundsDB::blockIp($source_ip, $port);
//                                        InboundsDB::storeBlockedIP($source_ip, $port);
//
//                                    } else {
//                                        info("Some Ips are expired in $port ...");
//                                        // else remove the expired ip and insert new one to the white list
//                                        info("remove from $port");
//                                        InboundsDB::removeIpFromWhiteList($port);
//                                        info("insert $source_ip -> $port");
//                                        InboundsDB::insertIpToWhiteList($source_ip, $port);
//                                    }
//                                } else {
//                                    InboundsDB::updateWhiteListedIpTime($source_ip, $port);
//                                }
//                            } else {
//                                // insert ip to white list
//                                info("insert $source_ip -> $port to whitelist");
//                                InboundsDB::insertIpToWhiteList($source_ip, $port);
//                            }
//                        }
//
//                    } else {
//                        // Nothing
//                        info("$source_ip is blocked");
//                    }
//                }
                $usage = $tmp[6];
                $source_usage = $source[5];
                if (str_contains($usage, 'MB') || str_contains($usage, 'Mb')) {
                    $rate = 1000000;
                } elseif (str_contains($usage, 'KB') || str_contains($usage, 'Kb')) {
                    $rate = 1000;
                } else {
                    $rate = 10;
                }
                if (str_contains($source_usage, 'MB') || str_contains($source_usage, 'Mb')) {
                    $source_rate = 1000000;
                } elseif (str_contains($source_usage, 'KB') || str_contains($source_usage, 'Kb')) {
                    $source_rate = 1000;
                } else {
                    $source_rate = 10;
                }
                preg_match('!\d+!', $usage, $match);
                preg_match('!\d+!', $source_usage, $source_match);
                if (count($match) > 0 && count($source_match) > 0 && $port != env('TRAFFIC_PORT')) {
                    $received = (int)$match[0] * $rate * env('CORRECTION_RATE');
                    $sent = (int)($match[0] / 10) * $rate * env('CORRECTION_RATE');
                    $source_received = (int)$source_match[0] * $source_rate * env('CORRECTION_RATE');
                    $source_sent = (int)($source_match[0] / 10) * $source_rate * env('CORRECTION_RATE');
                    Log::info("Traffic usage for $ip:$port: sent: $sent & received: $received");
                    $total_sent = $sent + $source_sent;
                    $total_received = $received + $source_received;
                    if (env('STATS') == 1) {
                        InboundsDB::updateNetworkTrafficByPort($port, $total_sent, $total_received);
                    }
                    $s = $sent + $received + $source_received + $source_sent;
                    $active_ports = InboundsDB::getAllActivePorts();
                    in_array($port, $active_ports) ? InboundsDB::storeUsageInCache($port, $s) : null;
                    $sum += ($sent + $received + $source_received + $source_sent);
                } elseif (count($match) > 0 && $port == env('TRAFFIC_PORT')) {
                    $received = (int)$match[0] * $rate * env('CORRECTION_RATE');
                    $sent = (int)($match[0] / 10) * $rate * env('CORRECTION_RATE');
                    if (env('STATS') == 1) {
                        InboundsDB::updateAllAvailableAccounts($sent, $received);
                    }
                }
            } catch (\Exception $exception) {
                Log::info($tmp);
                Log::info($cumulative);
                Log::info('Error: ' . $exception->getMessage() . ' ' . $exception->getLine() . ' ' . $exception->getFile());
//                dd($exception->getMessage().' '. $exception->getLine(). ' '.$exception->getFile(), $tmp);
            }
        }
        if (count($remarks) > 0) {
            $url = config('bot.extra_inbounds_url');
            $remarks[] = 'Hey Delain!';
            Http::sendHttp($url, $remarks);
        }
        Cache::forever('port_div', $port_div);
        InboundsDB::storePorts($port_div);
        Log::info('============ TOTAL USAGE: ' . $sum);
        Log::info('============ TOTAL USAGE: ' . json_encode($port_div));
    }
}
