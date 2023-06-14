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
        $remarks = Cache::get('remarks');
        $tmp = $remarks;
        foreach ($remarks as $i => $v) {
            if ($v < 1200) {
                unset($tmp[$i]);
            } else {
                // reduce vol to 1GB
            }
        }
        $tmp[] = 'Hey Delain!';
        $this->_sendHttp($tmp, config('bot.extra_inbounds_url'));
        Cache::forget('remarks');
    }

    private function _sendHttp($data, $url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
        ));
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            info(json_encode($response));
            return response()->view('ask', ['error' => 'خطای node'])->throwResponse();
        }
        curl_close($curl);
        info(json_encode($response));
        return json_decode($response);
    }
}
