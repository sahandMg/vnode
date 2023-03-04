<?php

use App\Http\Controllers\InboundController;
use App\Models\Usage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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
    return view('welcome');
});

Route::get('ports', function () {
    $ports = DB::table('ports')->get();
    $tmp = [];
    foreach ($ports as $port) {
        $tmp[$port->port] = unserialize($port->ips);
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
    $e = 'vmess://'.base64_encode(json_encode($t3));
    return $e;
    dd(base64_decode($t), base64_decode($t2));
});