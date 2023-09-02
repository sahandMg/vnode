<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WhitelistIpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ip:whitelist';

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
        DB::table('ports')->delete();
//        if (Cache::has('blocked')) {
//            $data = Cache::get('blocked');
//            foreach ($data as $port => $ips) {
//                for ($i = 0; $i < count($ips); $i++) {
//                    shell_exec('sudo ufw allow from ' . $ips[$i] . ' to any port ' . $port);
//                }
//            }
//            Cache::forget('blocked');
//        }
    }
}
