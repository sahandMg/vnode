<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Net_SSH2;

class XuiRestartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xui:restart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'restart xui safely';

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
        if (!request()->has('pass')) {
            exit();
        }
        include(app_path('Services/phpseclib/Net/SSH2.php'));
        $ssh = new Net_SSH2(env("IP_ADDRESS"));
        if (!$ssh->login('root', request()->get('pass'))) {
            exit('Login Failed' . env('IP_ADDRESS'));
        }
        $ssh->exec("lsof -t -i:1025 /etc/x-ui/x-ui.db | xargs kill -9");
        $ssh->exec("sudo x-ui start");
    }
}
