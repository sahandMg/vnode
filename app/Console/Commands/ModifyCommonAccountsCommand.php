<?php

namespace App\Console\Commands;

use App\Repositories\InboundsDB;
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
    protected $signature = 'common:edit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Edit common accounts';

    public $pattern = [
        'br1000.1' => ['uuid' => '4971d586-91c1-4a77-9ee8-41b1d39401bd', 'port' => 8080],
        'br1000.2' => ['uuid' => 'd7d687d6-ee93-4708-e7bb-1512c08d259d', 'port' => 8880],
        'br1000.3' => ['uuid' => 'f235e7df-e7c3-4638-f242-7ef373e396b2', 'port' => 2052],
        'br1000.4' => ['uuid' => '80be645a-6dd7-4b4a-beb8-6d6c7117b6b2', 'port' => 2082],
        'br1000.5' => ['uuid' => 'fa8b8fdb-01e9-4761-934e-e5c9f79480fd', 'port' => 2086],
    ];
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
//        $date = Carbon::now()->addYears(2)->getPreciseTimestamp(3);
        foreach ($this->pattern as $rem => $conf) {
            $record = DB::table('inbounds')
                ->where('remark', $rem)
                ->first();
            $a = json_decode($record->settings);
            $a->clients[0]->id = $conf['uuid'];
            $record->settings = json_encode($a);
            DB::table('inbounds')
                ->where('remark', $rem)
                ->update(['settings' => $record->settings, 'port' => $conf['port']]);
            InboundsDB::reconnect($rem);
        }
    }
}
