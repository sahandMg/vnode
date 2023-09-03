<?php

use App\Http\Controllers\InboundController;
use App\Models\Usage;
use App\Repositories\InboundsDB;
use App\Repositories\UserDB;
use App\Services\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Morilog\Jalali\Jalalian;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
//    DB::table('inbounds')->where('expiry_time', '!=', 0)
//        ->where('enable', 0)->update(['enable' => 1]);
//    $user = UserDB::getUserData();
//    $login_url = config('bot.login_url').'?username='.$user->username.'&password='.$user->password;
//    foreach ($records as $record) {
//
//        $cookie = Http::sendHttpLogin($login_url);
//        $update_url = config('bot.update_url') . $inbound->id;
//        Http::sendHttp($update_url, $inbound_arr, ['Cookie:'. $cookie]);
//    }
    return view('welcome');
});

Route::get('ports', function () {
    if (!isset($_GET['port'])) {
        return 'Please provide a port';
    }
    $port = $_GET['port'];
    $ports = DB::table('ports')
        ->select('port', 'ips', 'created_at')
        ->where('port', $port)
        ->groupBy('created_at')
        ->get();
    $tmp = [];
    foreach ($ports as $port) {
        $tmp[$port->port][$port->created_at] = unserialize($port->ips);
    }
    return $tmp;
});

Route::get('status', function () {
    $inbounds = DB::table('inbounds')->get();
    $total = $inbounds->count();
    $active = $inbounds->where('enable', 1)->count();
    $inactive = $inbounds->where('enable', 0)->count();
    return compact('total', 'active', 'inactive');
});

Route::get('stats', function () {
    $data = Usage::query()->where('created_at', '>', Carbon::now()->subDays(14))->get();
    $ports = [];
    $data->each(function ($record) use (&$ports) {
        $ports[$record->port][] = ['usage' => $record->usage, 'created_at' => Carbon::parse($record->created_at)->format('Y-m-d H:i')];
    });
    return view('stats', compact('ports'));
});

Route::get('remarks', function () {
    return Cache::get('remarks');
});

Route::get('account', [InboundController::class, 'createAccount']);

Route::get('transpiler', function () {
    $t = 'ewogICJ2IjogIjIiLAogICJwcyI6ICJicjI0Ljc3IiwKICAiYWRkIjogImJyaWRnZTI0LmpveXYueHl6IiwKICAicG9ydCI6IDI5NzkxLAogICJpZCI6ICI0OGNjY2UyZC1kNjA5LTM0MmUtODc0YS05ZjRlOThmZmVkYWMiLAogICJhaWQiOiAwLAogICJuZXQiOiAid3MiLAogICJ0eXBlIjogIm5vbmUiLAogICJob3N0IjogIiIsCiAgInBhdGgiOiAiLyIsCiAgInRscyI6ICJub25lIgp9';
    $t2 = 'ewogICJ2IjogIjIiLAogICJwcyI6ICJicjI0Ljc0IiwKICAiYWRkIjogImJyaWRnZTI0LmpveXYueHl6IiwKICAicG9ydCI6IDI5MjE0LAogICJpZCI6ICIyMmMzZmNlYi0xMjdjLTMwYzEtODk5Ny00Y2IxNzdjZmI4NzQiLAogICJhaWQiOiAwLAogICJuZXQiOiAidGNwIiwKICAidHlwZSI6ICJodHRwIiwKICAiaG9zdCI6ICJzb2Z0OTguaXIiLAogICJwYXRoIjogIi8iLAogICJ0bHMiOiAibm9uZSIKfQ==';
    $t3 = [
        "v" => "2",
        "ps" => "br24.74",
        "add" => "bridge24.joyv.xyz",
        "port" => 29214,
        "id" => "22c3fceb-127c-30c1-8997-4cb177cfb874",
        "aid" => 0,
        "net" => "tcp",
        "type" => "http",
        "host" => "soft98.ir",
        "path" => "/",
        "tls" => "none"
    ];
    $e = 'vmess://' . base64_encode(json_encode($t3));
    return $e;
    dd(base64_decode($t), base64_decode($t2));
});

Route::get('vol', function () {
    $inbounds = InboundsDB::getAllInbounds();
    $s = 0;
    foreach ($inbounds as $inbound) {

        if ($inbound->total !== 0 && $inbound->down > 10 ^ 9) {
            if ($inbound->up + $inbound->down < $inbound->total / 2) {
                $s += 1;
            }
        }
    }
    return $s / $inbounds->count() * 100;
});

Route::get('j', function () {
    return Jalalian::now();
});