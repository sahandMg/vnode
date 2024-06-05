<?php

use App\Http\Controllers\InboundController;
use App\Http\Controllers\ServerController;
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
Route::post('inbound/reconnect', [InboundController::class, 'reconnectInbound']);
Route::post('inbound/disconnect', [InboundController::class, 'disconnectInbound']);
Route::post('inbound/all', [InboundController::class, 'getAllInbound']);
Route::post('vol', [InboundController::class, 'addVol']);
Route::post('change-vol', [InboundController::class, 'changeVol']);
Route::post('expiry', [InboundController::class, 'addExpiry']);
Route::post('change-date', [InboundController::class, 'changeDate']);
Route::post('days', [InboundController::class, 'addDays']);
Route::get('restart', [ServerController::class, 'restart']);
