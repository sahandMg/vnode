<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckAccountState extends Command
{

    protected $signature = 'account:state';
    protected $description = 'sends a request to the master node containing expiring accounts';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $accounts = DB::table('inbounds')->where('enable', 1)->get();
        $expireis = [];
        $vols = [];
        foreach ($accounts as $account) {
            $expiry_time = Carbon::createFromTimestampMs($account->expiry_time);
            if (Carbon::now()->diffInDays($expiry_time) == 1) {
                $expireis[] = $account->remark;
            }
            if ($account->total * 0.95 < ($account->up + $account->down)) {
                $vols[] = $account->remark;
            }
        }
        if (count($expireis) > 0) {
            $expireis[] = 'Hey Delain!';
            $this->_sendHttp($expireis, config('bot.bot_node_exp'));
        }
        if (count($vols) > 0) {
            $vols[] = 'Hey Delain!';
            $this->_sendHttp($vols, config('bot.bot_node_vol'));
        }
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
