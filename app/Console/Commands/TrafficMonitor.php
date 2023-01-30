<?php

namespace App\Console\Commands;

use App\Jobs\TrafficHandlerJob;
use App\Repositories\InboundsDB;
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

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
//        $txt = $ssh->exec("iftop -P -n -N -i ens160 -t -s 20 -L 150");
//        $ssh->disconnect();
//        Cache::forever('traffic', $txt);
//        dd($txt);
//        $txt = shell_exec("iftop -P -n -N -t -s 5 -f -L 100");
//        $txt = Cache::get('traffic');

//        Add theses lines to cronttab -e
// * * * * * php /var/www/html/vnode/artisan traffic
// * * * * * sleep 30; php /var/www/html/vnode/artisan traffic
        $txt = shell_exec("iftop -P -n -N -t -s 25 -L 150");
        $t = array_filter(explode(PHP_EOL, $txt));
        $cumulative = array_splice($t, 7, 200);
        $sum = 0;
        $ips = [];
        for ($i = 0; $i < count($cumulative); $i++) {
            try {
                $tmp = array_values(array_filter(explode(' ', $cumulative[$i])));
                if (!str_contains($tmp[1], env('IP_ADDRESS'))) {
                    continue;
                }
                $ip = explode(':', $tmp[1])[0];
                $port = explode(':', $tmp[1])[1];
                if (!isset($ips[$ip])) {
                    $ips[$ip] = 1;
                } elseif ($ips[$ip] > 1) {
                    InboundsDB::disableAccountByPort($port);
                    Log::info($port." disabled");
                } else {
                    $ips["$ip"] += 1;
                }
                $usage = $tmp[6];
                if (str_contains($usage, 'MB')) {
                    $rate = 1000000;
                } elseif (str_contains($usage, 'KB')) {
                    $rate = 1000;
                } else {
                    $rate = 10;
                }
                preg_match('!\d+!', $usage, $match);
                if (count($match) > 0 && $port != env('TRAFFIC_PORT')) {
                    $received = (int)$match[0] * $rate * env('CORRECTION_RATE');
                    $sent = (int)($match[0] / 10) * $rate * env('CORRECTION_RATE');
                    Log::info("Traffic usage for $ip:$port: sent: $sent & received: $received");
                    InboundsDB::updateNetworkTrafficByPort($port, $sent, $received);
                    $sum += $sent + $received;
                }
            } catch (\Exception $exception) {
                Log::info($tmp);
                Log::info($cumulative);
                Log::info('Error: ' . $exception->getMessage());
            }
        }
        Log::info('============ TOTAL USAGE: ' . $sum);
    }
}
