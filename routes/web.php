<?php

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