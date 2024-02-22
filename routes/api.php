<?php

// use App\Http\Controllers\EpsController;
// use App\Http\Controllers\GendersController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\ReservationTypeController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

define("URL", "/{proj_id}/{use_id}/");
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
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::Resource('reservations'.URL, ReservationController::class)->names('reservations')->parameter('','reservations');
// Funciones adicionales ReservationController
// Route::get('historial/date/{date}'.URL, [ReservationController::class, "reserPerDate"])->name('historial.per.date');
Route::get('active/reserv'.URL, [ReservationController::class, "AdminActiveReserv"])->name('admin.active.reserv');
Route::get('historial/user/{id}'.URL, [ReservationController::class, "reserPerUser"])->name('historial.per.user');
Route::get('active/reserv/user/{id}'.URL, [ReservationController::class, "activeReservUser"])->name('active.reserv.user');
Route::get('historial/space/{space}'.URL, [ReservationController::class, "reserPerSpace"])->name('historial.Per.Space');
Route::get('users'.URL, [ReservationController::class, "users"])->name('users');

Route::Resource('reservation/types'.URL, ReservationTypeController::class)->names('reservation.types')->parameter('','reservation_types');
Route::Resource('spaces'.URL, SpaceController::class)->names('spaces')->parameter('','spaces');
