<?php

namespace App\Jobs;

use App\Repositories\InboundsDB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TrafficHandlerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $port;

    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $txt = shell_exec("iftop -P -n -N -t -s 55 -L 150");
        $t = array_filter(explode(PHP_EOL, $txt));
        $cumulative = array_splice($t, 7, 200);
        $sum = 0;
        for ($i = 0; $i < count($cumulative); $i++) {
            try {
                $tmp = array_values(array_filter(explode(' ', $cumulative[$i])));
                if (!str_contains($tmp[1], env('IP_ADDRESS'))) {
                    continue;
                }
                $ip = explode(':', $tmp[1])[0];
                $port = explode(':', $tmp[1])[1];
                $usage = $tmp[6];
                if (str_contains($usage, 'MB')) {
                    $rate = 1000000;
                } elseif (str_contains($usage, 'KB')) {
                    $rate = 1000;
                } else {
                    $rate = 1;
                }
                preg_match('!\d+!', $usage, $match);
                if (count($match) > 0 && $port != env('TRAFFIC_PORT')) {
                    $received = (int)$match[0] * $rate;
                    $sent = (int)($match[0] / 10) * $rate;
                    Log::info("Traffic usage for $ip:$port: sent: $sent & received: $received");
                    InboundsDB::updateNetworkTrafficByPort($port, $sent, $received);
                    $sum += $sent + $received;
                }
            } catch (\Exception $exception) {
                Log::info($tmp);
                Log::info('Error: ' . $exception->getMessage());
            }
        }
        Log::info('============ TOTAL USAGE: '.$sum);
    }
}
