<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ClearUsageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'usage:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clearing usage data from cache';

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
        $data = Cache::get('usages');
        DB::beginTransaction();
        foreach ($data as $port => $usage) {
            DB::table('usages')->insert(['port' => $port, 'usage' => $usage, 'created_at' => Carbon::now()]);
        }
        DB::commit();
        Cache::forget('usages');
    }
}
