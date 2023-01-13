<?php

namespace App\Console\Commands;

use App\Repositories\InboundsDB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
        $ports = InboundsDB::getAllPorts();
        foreach ($ports as $port) {
            $txt = shell_exec("iftop -P -n -N -t -s 10 -f 'port '$port");
            $t = array_filter(explode(PHP_EOL, $txt));
            $cumulative = array_splice($t, count($t) - 2, 1);
            $traffic_data = array_values(array_filter(explode(' ', $cumulative[0])));
            if (str_contains($traffic_data[2], 'MB')) {
                $rate = 1000000;
            } elseif (str_contains($traffic_data[2], 'KB')) {
                $rate = 1000;
            } else {
                $rate = 1;
            }
            preg_match('!\d+!', $traffic_data[2], $match1);
            preg_match('!\d!', $traffic_data[3], $match2);
            if (count($match1) > 0) {
                $sent = (int)$match1[0] * $rate;
                $received = (int)$match2[0] * $rate;
                InboundsDB::updateNetworkTrafficByPort($port, $sent, $received);
            }
        }
    }
}
