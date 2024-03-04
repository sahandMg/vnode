<?php

namespace App\Console\Commands;

use App\Services\Http;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IntroduceCommand extends Command
{

    protected $signature = 'server:introduce';
    protected $description = 'send availability packet to master every 10 min';
    public $counter = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->_sendRequest();
    }

    private function _sendRequest()
    {
        $resp = Http::sendHttp(config('bot.introduce_url'), ['server' => env('SERVER_ID')]);
        $this->counter += 1;
        if (gettype($resp) == 'boolean' || is_null($resp)) {
            if ($this->counter <= 3) {
                sleep(1);
                $this->_sendRequest();
            } else {
                Log::info('Introducing to the master node failed after 3 times');
            }
        } else {
            if ($resp->status != 200 && $this->counter <= 3) {
                sleep(1);
                $this->_sendRequest();
            }
        }
        $res = shell_exec('x-ui status');
        if (!str_contains($res, 'active')) {
            $url = config('bot.interruption_url');
            Http::sendHttp($url, ['msg' => env('SERVER_ID') .'⚠️ عدم احراز ']);
        }
    }
}