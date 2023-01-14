<?php

namespace App\Console\Commands;

use App\Jobs\TrafficHandlerJob;
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
//        $txt = $ssh->exec("iftop -P -n -N -i ens160 -t -s 5 -L 100");
//        $ssh->disconnect();
//        Cache::forever('traffic', $txt);
//        dd($txt);
//        $txt = shell_exec("iftop -P -n -N -t -s 5 -f -L 100");
//        $txt = Cache::get('traffic');

        $ports = InboundsDB::getAllPorts();
        foreach ($ports as $port) {
            TrafficHandlerJob::dispatch($port);
        }
    }
}
