<?php

namespace App\Console\Commands;

use App\Repositories\InboundsDB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PortAllowCommnad extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'allow:ports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deny All Other Ports Than What Have Been Defined In The Panel';

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
        $ports = InboundsDB::getAllPorts();
        if (Cache::has('all_ports')) {
            $stored_ports = Cache::get('all_ports');
            for ($p = 0; $p < count($ports); $p++) {
                if (!in_array($ports[$p], $stored_ports)) {
                    shell_exec('sudo ufw allow ' . $ports[$p]);
                    $stored_ports[] = $ports[$p];
                    Cache::forever('all_ports', $stored_ports);
                }
            }
        }else {
            Cache::forever('all_ports', $ports);
            for ($p = 0; $p < count($ports); $p++) {
                shell_exec('sudo ufw allow ' . $ports[$p]);
                shell_exec('sudo ufw allow 22');
                shell_exec('sudo ufw allow 5529');
                shell_exec('sudo ufw allow '.env('TRAFFIC_PORT'));
            }
        }
    }
}
