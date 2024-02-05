<?php

use App\Http\Controllers\AuthController;
// use App\Http\Controllers\EpsController;
// use App\Http\Controllers\GendersController;
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
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::resource('eps', EpsController::class)->names('eps');
// Route::resource('genders', GendersController::class)->names('genders');
// CRUD espacios salas
Route::Resource('reservations', ReservationController::class)->names('reservations');
Route::get('reservations/user/{id}', [ReservationController::class, "reserPerUser"])->name('reservations.reserPerUser');
Route::get('reservations/date/{date}', [ReservationController::class, "reserPerDate"])->name('reservations.reserPerDate');
Route::get('reservations/space/{space}', [ReservationController::class, "reserPerSpace"])->name('reservations.reserPerSpace');
Route::Resource('spaces', SpaceController::class)->names('spaces');
Route::Resource('reservation_types', ReservationTypeController::class)->names('reservation_types');
