<?php

use App\Http\Controllers\InboundController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('inbound', [InboundController::class, 'getInbound']);
Route::post('inbound/all', [InboundController::class, 'getAllInbound']);
Route::post('vol', [InboundController::class, 'addVol']);
Route::post('expiry', [InboundController::class, 'addExpiry']);