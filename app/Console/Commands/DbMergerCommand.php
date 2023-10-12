<?php

namespace App\Console\Commands;

use App\Repositories\InboundsDB;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Net_SSH2;

class DbMergerCommand extends Command
{

    protected $signature = 'db:merge';

    protected $description = 'merge existing db with origin';

    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        $child = DB::connection('sqlite2')->table('inbounds')->get();
        foreach ($child as $record) {
            try{
                DB::connection('sqlite')->table('inbounds')->insert([
                    'user_id' => $record->user_id,
                    'up' => $record->up,
                    'down' => $record->down,
                    'total' => $record->total,
                    'remark' => $record->remark,
                    'enable' => $record->enable,
                    'expiry_time' => $record->expiry_time,
                    'listen' => $record->listen,
                    'port' => $record->port,
                    'protocol' => $record->protocol,
                    'settings' => $record->settings,
                    'stream_settings' => $record->stream_settings,
                    'tag' => $record->tag,
                    'sniffing' => $record->sniffing,
                ]);
                include(app_path('Services/phpseclib/Net/SSH2.php'));
                $ssh = new Net_SSH2(env("IP_ADDRESS"));
                if (!$ssh->login('root', env('PASS2'))) {
                    exit('Login Failed' . env('IP_ADDRESS'));
                }
                $ssh->exec("lsof -t -i:1025 /etc/x-ui/x-ui.db | xargs kill -9");
                $ssh->exec("x-ui start");
            }catch (\Exception $exception) {
                info($record->remark.' Enable:'. $record->enable);
                info($exception->getMessage());
            }
        }
    }
}
