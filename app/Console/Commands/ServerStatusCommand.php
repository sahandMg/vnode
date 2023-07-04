<?php

namespace App\Console\Commands;

use App\Services\Http;
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
        $samples = 10;
        $flag = 0;
        $data = trim(shell_exec("ifstat -q 1 $samples"));
        $data_arr = explode("\n", $data);
        unset($data_arr[0]);
        unset($data_arr[1]);
        $data_arr = array_values($data_arr);
        foreach ($data_arr as $d) {
            $net_in = array_values(array_filter(explode(" ", trim($d))))[0];
            if ($net_in < 300) {
                $flag += 1;
            }
        }
        if ($flag == $samples) {
            $msg = env('SERVER_ID')."☢️ اختلال روی سرور ";
            $url = config('bot.interruption_url');
            Http::sendHttp($url, ['msg' => $msg]);
        }
    }
}
