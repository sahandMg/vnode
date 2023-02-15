<?php

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
    $data->each(function ($record) use (&$ports){
        $ports[$record->port][] = ['usage' => $record->usage, 'created_at' => Carbon::parse($record->created_at)->format('Y-m-d H:i')];
    });
    return view('stats', compact('ports'));
});