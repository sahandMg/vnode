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
        $res = shell_exec('x-ui status');
        if (!str_contains($res, 'active')) {
            $url = config('bot.interruption_url');
            Http::sendHttp($url, ['msg' => env('SERVER_ID')]);
        }
    }
}