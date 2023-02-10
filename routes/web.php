<?php

use Illuminate\Support\Facades\Cache;
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
    if (isset($_GET['port'])) {
        $ports = Cache::get('port_div');
        $req_port = $_GET['port'];
        return isset($ports[$req_port]) ? $ports[$req_port] : 'Not Found';
    }
    return Cache::get('port_div');
});