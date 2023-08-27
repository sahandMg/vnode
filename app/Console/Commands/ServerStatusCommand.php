<?php

namespace App\Console\Commands;

use App\Services\Http;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ServerStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'checks if server has inbound traffic or not';

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
        if (Carbon::now()->greaterThan(Carbon::today()->addHours(10))) {
            $samples = 10;
            $flag = 0;
            $data = trim(shell_exec("ifstat -q 1 $samples"));
            $data_arr = explode("\n", $data);
            unset($data_arr[0]);
            unset($data_arr[1]);
            $data_arr = array_values($data_arr);
            foreach ($data_arr as $d) {
                $net_out = array_values(array_filter(explode(" ", trim($d))))[1];
                if ($net_out < 20) {
                    $flag += 1;
                }
            }
            if ($flag == $samples) {
                $msg =  "☢️ اختلال روی سرور ". env('SERVER_ID');
                shell_exec('x-ui restart');
                $url = config('bot.interruption_url');
                Http::sendHttp($url, ['msg' => $msg]);
            }
        }
    }
}
