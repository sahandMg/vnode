<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearExtraRemarkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:remarks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clear remarks array from cache every day';

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
        // send a request to joyvpn to inform admin of bad remarks
        Cache::forget('remarks');
    }
}
