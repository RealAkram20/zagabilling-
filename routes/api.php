<?php

use App\Http\Controllers\Api\DeviceApiController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('device')->name('api.device.')->group(function () {
    Route::post('/enroll', [DeviceApiController::class, 'enroll'])
        ->middleware('throttle:10,1')
        ->name('enroll');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/heartbeat', [DeviceApiController::class, 'heartbeat'])->name('heartbeat');
        Route::get('/token', [DeviceApiController::class, 'token'])->name('token');
    });
});
