<?php

namespace App\Console\Commands;

use App\Services\Http;
use Illuminate\Console\Command;

class IntroduceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server:introduce';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send availability packet to master every 10 min';

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
        Http::sendHttp(config('bot.introduce_url'), ['server' => env('SERVER_ID')]);
    }
}
