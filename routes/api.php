<?php

// use App\Http\Controllers\EpsController;
// use App\Http\Controllers\GendersController;
use App\Http\Controllers\Controller;
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
Route::post('login', [Controller::class, 'login'])->name('login');
Route::post('logout', [Controller::class, 'logout'])->name('logout');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::resource('eps', EpsController::class)->names('eps');
// Route::get('genders', [controller::class, 'genders'])->names('genders');

Route::Resource('reservations', ReservationController::class)->names('reservations');
// Funciones adicionales ReservationController
Route::get('reservations/user/{id}', [ReservationController::class, "reserPerUser"])->name('reservations.reserPerUser');
Route::get('reservations/date/{date}', [ReservationController::class, "reserPerDate"])->name('reservations.reserPerDate');
Route::get('reservations/space/{space}', [ReservationController::class, "reserPerSpace"])->name('reservations.reserPerSpace');

Route::Resource('reservation_types', ReservationTypeController::class)->names('reservation_types');
Route::Resource('spaces', SpaceController::class)->names('spaces');
