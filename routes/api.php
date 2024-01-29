<?php

use App\Http\Controllers\EpsController;
use App\Http\Controllers\GendersController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\ReservationTypeController;
use App\Http\Controllers\ReservationController;
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

Route::resource('eps', EpsController::class)->names('eps');
Route::resource('genders', GendersController::class)->names('genders');

// CRUD espacios salas
Route::Resource('reservations', ReservationController::class)->names('reservations');
Route::Resource('spaces', SpaceController::class)->names('spaces');
Route::Resource('reservation_types', ReservationTypeController::class)->names('reservation_types');
