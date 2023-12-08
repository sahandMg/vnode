<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ModifyCommonAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'common:date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'set date for common accounts';

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
        $date = Carbon::now()->addYears(2)->getPreciseTimestamp(3);
        DB::table('inbounds')->where('remark', 'like', "br1000.%")->update(['expiry_time' => $date, 'enable' => 1, 'total' => 0]);
    }
}
