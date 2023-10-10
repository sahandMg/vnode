<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Net_SSH2;

class ServerController extends Controller
{
    public function restart()
    {
        if (!request()->has('pass')) {
            return response()->json('Error', 404);
        }
        include(app_path('Services/phpseclib/Net/SSH2.php'));
        $ssh = new Net_SSH2(env("IP_ADDRESS"));
        if (!$ssh->login('root', env('PASS2'))) {
            exit('Login Failed' . env('IP_ADDRESS'));
        }
        $ssh->exec("lsof -t -i:1025 /etc/x-ui/x-ui.db | xargs kill -9");
        $ssh->exec("x-ui start");
        return response()->json('ok', 200);
    }
}
