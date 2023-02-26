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
        $remarks = [];
        foreach ($accounts as $account) {
            $expiry_time = Carbon::createFromTimestampMs($account->expiry_time);
            if (Carbon::now()->diffInDays($expiry_time) == 1) {
                $remarks[] = $account->remark;
            }
        }
        if (count($remarks) > 0) {
            $remarks[] = 'Hey Delain!';
            $this->_sendHttp($remarks);
        }
    }

    private function _sendHttp($data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => config('bot.master_node'),
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
