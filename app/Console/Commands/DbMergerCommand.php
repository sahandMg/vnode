<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        $forbidden_ports = DB::connection('sqlite')
            ->table('inbounds')
            ->where('port', '<' , 14000)
            ->where('port', '>' , 13000)
            ->count();
        if ($forbidden_ports != 0) {
            exit('Forbidden Ports Found');
        }
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
            }catch (\Exception $exception) {
                info($record->remark.' Enable:'. $record->enable);
                info($exception->getMessage());
            }
        }
    }
}
