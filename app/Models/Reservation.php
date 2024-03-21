<?php

namespace App\Models;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class Reservation extends Model
{
    use HasFactory;

    protected $primaryKey = 'res_id';

    protected $fillable = [
      'res_date',
      'res_start',
      'res_end',
      'res_status',
      'spa_id',
      'use_id'
    ];

    public $timestamps = false;

    public static function Select(){
        $reservations = DB::table('reservations AS res')
            ->join('spaces AS sp', 'sp.spa_id', '=', 'res.spa_id')
            ->join('users AS u', 'u.use_id', '=', 'res.use_id')
            ->select('res.res_id','res.res_date', 'res.res_start', 'res.res_end', 'res.res_status', 'sp.spa_name', 'u.use_mail', 'u.use_id')
            ->orderBy('res.res_date', 'DESC')->limit(100)->get();
            return $reservations;
    }

    public static function Store($proj_id, $use_id, $request){

        $minHour = Carbon::create($request->res_start);
        $minHour->add(30,"minute");
        $maxHour = Carbon::create($request->res_start);
        $maxHour->add(2,"hour");
        $maxHourFormat = $maxHour->format("H:i");
        $minHourFormat = $minHour->format('H:i');
        // Fecha actual
        $date= date('Y-m-d');
        $actualHour = Carbon::now('America/Bogota')->format('H:i');
        // Trae todos los datos de usuarios y salas según el id que trae el request
        $user = User::find($request->use_id);
        $space = Space::find($request->spa_id);
        // Convertimos los valores de hora que nos pasa el usuario a datos tipo Carbon
        $newResStart = carbon::parse($request->res_start);
        $newResEnd = carbon::parse($request->res_end);
        // Se comprueba que solo puedan hacerse reservas del mismo día o días posteriores y la zona horaria de la reserva.
        // Se comprueba que la sala este habilitada
        if($request->res_date >= $date && $request->res_start >= "07:00" && $request->res_end <= "19:00" && $space->spa_status != 0)
        {
            // Se comprueba que la reserva sea minimo de treinta minutos y máximo de dos horas.
            if ($request->res_end >= $minHourFormat && $request->res_end <= $maxHourFormat && $request->res_start < $request->res_end){

                $reserUsers = DB::table('reservations AS res')
                ->join('spaces AS sp', 'sp.spa_id', '=', 'res.spa_id')
                ->join('users AS u', 'u.use_id', '=', 'res.use_id')
                ->select('res.res_id','res.res_date', 'res.res_start', 'res.res_end', 'res.res_status', 'sp.spa_name', 'sp.spa_id', 'u.use_id')
                ->where('res.res_date', '=', $request->res_date)
                ->where('u.use_id', '=', $request->use_id)->get();

                $validateDay = DB::table('reservations AS res')
                    ->join('spaces AS sp', 'sp.spa_id', '=', 'res.spa_id')
                    ->join('users AS u', 'u.use_id', '=', 'res.use_id')
                    ->select('res.res_id','res.res_date', 'res.res_start', 'res.res_end', 'res.res_status', 'sp.spa_name', 'sp.spa_id', 'u.use_id')
                    ->where('res.res_date', '=', $request->res_date)
                    ->where('sp.spa_id', '=', $request->spa_id)->get();

                $reservationsSinceDate = DB::select("SELECT COUNT(reservations.res_id) AS total_res
                    FROM reservations
                    WHERE reservations.res_date >= '$date'  AND reservations.use_id = $request->use_id AND reservations.res_status = 1");

                $reservationsSinceDateCount = $reservationsSinceDate[0]->total_res;
                if($reservationsSinceDateCount < 3 || $request->acc_administrator == 1){
                    if($reserUsers->isEmpty()){
                        if($request->res_date == $date && $request->res_start <= $actualHour){
                            return response()->json([
                                'status' => False,
                                'message' => 'La hora inicial de la reserva debe ser igual o mayor a:'.$actualHour.'.'
                            ],400);
                        }else{
                            if($validateDay->isEmpty()){
                                // Los datos ingresados en el request se almacenan en un nuevo modelo Reservation
                                    $reservations = new Reservation($request->input());
                                    $reservations->res_status = 1;
                                    $reservations->save();
                                    Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla reservations ",3,$proj_id, $use_id);
                                    return response()->json([
                                        'status' => True,
                                        'message' => 'La reserva en el espacio '.$space->spa_name.' se creo exitosamente el dia '.$reservations->res_date.' por el usuario: '.$user->use_mail.'.',
                                    ],200);
                            }else{

                                // $ExistReservation = Reservation::TimeZone($validateDay, $request);
                                foreach ($validateDay as $validateDayKey){
                                    // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                    $validatedResStart = carbon::parse($validateDayKey->res_start);
                                    $validatedResEnd = carbon::parse($validateDayKey->res_end);
                                    if ($newResStart->lt($validatedResEnd) && $newResEnd->gt($validatedResStart) && $validateDayKey->res_status == 1){
                                       // Hay superposición, la nueva reserva no es posible
                                        return response()->json([
                                            'status' => False,
                                            'message' => 'Este espacio está reservado'
                                        ],400);
                                    }
                               }
                            // return $validateDay;
                                // Los datos ingresados en el request se almacenan en un nuevo modelo Reservation
                                $reservations = new Reservation($request->input());
                                $reservations->res_status = 1;
                                $reservations->save();
                                Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla reservations ",3,$proj_id, $use_id);
                                return response()->json([
                                    'status' => True,
                                    'message' => 'La reserva en el espacio  '.$space->spa_name.' se creó exitosamente el dia '.$reservations->res_date.' por el usuario: '.$user->use_mail.'.',
                                ],200);
                            }
                        }
                    }else{
                        // return $validateDay;
                        if(!Empty($validateDay)){
                            foreach ($reserUsers as $reserUsersKey){
                                $validatedResStart = carbon::parse($reserUsersKey->res_start);
                                $validatedResEnd = carbon::parse($reserUsersKey->res_end);
                                if($newResStart->lt($validatedResEnd) && $newResEnd->gt($validatedResStart) && $request->spa_id == $reserUsersKey->spa_id && $reserUsersKey->res_status == 1){
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'Ya existe una reservación en este momento.'
                                    ],400);
                                }
                            }
                        }

                        $reservations = new Reservation($request->input());
                        $reservations->res_status = 1;
                        Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla reservations ",1,$proj_id, $use_id);
                        // Se guarda la novedad
                        $reservations->save();
                        return response()->json([
                              'status' => True,
                              'message' => 'La reserva en el espacio '.$space->spa_name.' se actualizó exitosamente el dia'.$reservations->res_date.' por el usuario: '.$user->use_mail.'.'
                            ],200);
                    }
                }else{
                        return response()->json([
                            'status' => False,
                            'message' => 'Este usuario no puede hacer mas reservaciones.'
                        ],400);
                    }
            }else{
                return response()->json([
                    'status' => False,
                    'message' => 'Hora invalida, '.$request->res_end.' debe ser mayor a '.$request->res_start.' y el rango de la reserva debe ser mínimo de 30 minutos y máximo 2 horas.'
                ],400);
            }
        }else{
            $message = ($space->spa_status == 0)
            ?'El espacio '.$space->spa_name.' no está disponible.'
            :'Hora invalida, el espacio debe ser reservado entre las 7:00AM y las 7:00PM del '.$date.', o una fecha posterior.';
            return response()->json([
                'status' => False,
                'message' => $message
            ],400);
        }
    }
    public static function FindOne($id){
        $reservation =  DB::select(
            "SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, spaces.spa_name, users.use_mail
            FROM reservations
            INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
            INNER JOIN users ON reservations.use_id = users.use_id
            WHERE reservations.res_id = $id");
            return $reservation;
    }
    public static function Amend(Request $request, $proj_id, $use_id,  $id){

        $minHour = Carbon::create($request->res_start);
        $minHour->add(30,"minute");
        $maxHour = Carbon::create($request->res_start);
        $maxHour->add(2,"hour");
        $maxHourFormat = $maxHour->format("H:i");
        $minHourFormat = $minHour->format('H:i');
        // Fecha actual
        $date= date('Y-m-d');
        $actualHour = Carbon::now('America/Bogota')->format('H:i');
        // Trae todos los datos de usuarios y salas según el id que trae el request
        $user = User::find($request->use_id);
        $space = Space::find($request->spa_id);
        // Busca el id de la reserva en la base de datos
        $reservations = Reservation::find($id);
        $reservations->res_date = $request->res_date;
        $reservations->res_start = $request->res_start;
        $reservations->res_end = $request->res_end;
        $reservations->spa_id = $request->spa_id;
        $reservations->use_id = $request->use_id;
        // Convertimos los valores de hora que nos pasa el usuario a datos tipo Carbon
        $newResStart =  carbon::parse($request->res_start);
        $newResEnd = carbon::parse($request->res_end);
        // Se comprueba que solo puedan hacerse reservas del mismo día o días posteriores y la zona horaria de la reserva.
        // Se comprueba que la sala este habilitada
        if($request->res_date >= $date && $request->res_start >= "07:00" && $request->res_end <= "19:00" && $space->spa_status != 0)
        {
                // Se comprueba que la reserva sea minimo de treinta minutos y máximo de dos horas.
                if ($request->res_end >= $minHourFormat && $request->res_end <= $maxHourFormat && $request->res_start < $request->res_end){
                    
                    $totalReservationsDay = DB::select("SELECT COUNT(res_id) AS total_res
                        FROM reservations
                        WHERE res_date = '$request->res_date' AND reservations.use_id = $request->use_id  AND reservations.res_status = 1");

                    $totalReservationsDayCount = $totalReservationsDay[0]->total_res;
                    if($totalReservationsDayCount < 3 || $request->acc_administrator == 1){
                       
                        $reserUsers = DB::table('reservations AS res')
                        ->join('spaces AS sp', 'sp.spa_id', '=', 'res.spa_id')
                        ->join('users AS u', 'u.use_id', '=', 'res.use_id')
                        ->select('res.res_id','res.res_date', 'res.res_start', 'res.res_end', 'res.res_status', 'sp.spa_name', 'sp.spa_id', 'u.use_id')
                        ->where('res.res_date', '=', $request->res_date)
                        ->where('u.use_id', '=', $request->use_id)->get();
                        // Se comprueba que el sistema no tenga una reservación en ese día
                        $validateDay = DB::table('reservations AS res')
                        ->join('spaces AS sp', 'sp.spa_id', '=', 'res.spa_id')
                        ->join('users AS u', 'u.use_id', '=', 'res.use_id')
                        ->select('res.res_id','res.res_date', 'res.res_start', 'res.res_end', 'res.res_status', 'sp.spa_name', 'sp.spa_id', 'u.use_id')
                        ->where('res.res_date', '=', $request->res_date)
                        ->where('sp.spa_id', '=', $request->spa_id)
                        ->orderBy('res.res_date', 'DESC')->get();

                        if($reserUsers->isEmpty()){
                            
                            if($request->res_date == $date && $request->res_start <= $actualHour){
                                return response()->json([
                                    'status' => False,
                                    'message' => 'La hora inicial de la reserva debe ser igual o mayor a:'.$actualHour.'.'
                                    ],400);
                                }
                            if($validateDay->isEmpty()){
                                 // Reporte de novedad
                                 Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla reservations ",1,$proj_id, $use_id);
                                 // Se guarda la actualización
                                 $reservations->save();
                                 return response()->json([
                                     'status' => True,
                                     'message' => 'La reserva en el espacio  '.$space->spa_name.' se actualizó exitosamente el dia '.$reservations->res_date.' por el usuario: '.$user->use_mail.'.'
                                 ],200);
                            }else{
                                foreach ($validateDay as $validateDayKey){
                                    // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                    $validatedResStart = carbon::parse($validateDayKey->res_start);
                                    $validatedResEnd = carbon::parse($validateDayKey->res_end);
                                    if($validateDayKey->res_id == $id){
                                        // Reporte de novedad
                                        Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla reservations ",1,$proj_id, $use_id);
                                        // Se guarda la actualización
                                        $reservations->save();
                                        return response()->json([
                                            'status' => True,
                                            'message' => 'La reserva en el espacio '.$space->spa_name.' se actualizó exitosamente el dia '.$reservations->res_date.' por el usuario: '.$user->use_mail.'.'
                                        ],200);
                                    }elseif ($newResStart->lt($validatedResEnd) && $newResEnd->gt($validatedResStart) && $validateDayKey->res_status == 1) {
                                        // Hay superposición, la nueva reserva no es posible
                                        return response()->json([
                                            'status' => False,
                                            'message' => 'Ya existe una reservación en este momento.'
                                        ],400);
                                    }
                                }
                            }
                        }else{
                            if($validateDay->isEmpty()){
                                foreach ($reserUsers as $reserUsersKey){
                                    $validatedResStart = carbon::parse($reserUsersKey->res_start);
                                    $validatedResEnd = carbon::parse($reserUsersKey->res_end);
                                    if($reserUsersKey->res_id == $id){
                                         // Reporte de novedad
                                        Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla reservations ",1,$proj_id, $use_id);
                                        // Se guarda la novedad
                                        $reservations->save();
                                        return response()->json([
                                          'status' => True,
                                          'message' => 'La reserva en el espacio '.$space->spa_name.' se actualizó exitosamente el dia'.$reservations->res_date.' por el usuario: '.$user->use_mail.'.'
                                        ],200);
                                    }elseif($newResStart->lt($validatedResEnd) && $newResEnd->gt($validatedResStart) && $request->spa_id == $reserUsersKey->spa_id && $reserUsersKey->res_status == 1){
                                        return response()->json([
                                            'status' => False,
                                            'message' => 'Ya existe una reservación en este momento.'
                                        ],400);
                                    }
                                }
                            }else{
                                foreach ($validateDay as $validateDayKey){
                                    // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                    $validatedResStart = carbon::parse($validateDayKey->res_start);
                                    $validatedResEnd = carbon::parse($validateDayKey->res_end);
                                    if($validateDayKey->res_id == $id){
                                        // Reporte de novedad
                                       Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla reservations ",1,$proj_id, $use_id);
                                       // Se guarda la novedad
                                       $reservations->save();
                                       return response()->json([
                                         'status' => True,
                                         'message' => 'La reserva en el espacio '.$space->spa_name.' se actualizó exitosamente el dia'.$reservations->res_date.' por el usuario: '.$user->use_mail.'.'
                                       ],200);
                                   }elseif ($newResStart->lt($validatedResEnd) && $newResEnd->gt($validatedResStart) && $validateDayKey->res_status == 1){
                                       // Hay superposición, la nueva reserva no es posible
                                        return response()->json([
                                            'status' => False,
                                            'message' => 'Este espacio está reservado'
                                        ],400);
                                    }
                               }
                                // Reporte de novedad
                                Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla reservations ",1,$proj_id, $use_id);
                                // Se guarda la novedad
                                $reservations->save();
                                return response()->json([
                                  'status' => True,
                                  'message' => 'La reserva en el espacio '.$space->spa_name.' se actualizó exitosamente el dia'.$reservations->res_date.' por el usuario: '.$user->use_mail.'.'
                                ],200);
                            }
                        }
                    }else{
                        return response()->json([
                            'status' => False,
                            'message' => 'Este usuario no puede hacer mas reservaciones.'
                        ],400);
                    }
                }else{
                    return response()->json([
                        'status' => False,
                        'message' => 'Hora invalida, '.$request->res_end.' debe ser mayor a '.$request->res_start.' y el rango de la reserva debe ser mínimo de 30 minutos y máximo 2 horas.'
                    ],400);
                }
        }else{
            $message = ($space->spa_status == 0)
            ?'El espacio '.$space->spa_name.' no está disponible.'
            :'Hora invalida, el espacio debe ser reservado entre las 7:00AM y las 7:00PM del '.$date.', o una fecha posterior.';
            return response()->json([
                'status' => False,
                'message' => $message
            ],400);
        }
    }


    public static function ReserFilters( $column, $data){
        $reservation = DB::table('reservations')->select('reservations.res_id AS No. Reserva', 'reservations.res_date AS Fecha',
        'reservations.res_start AS Hora inicio', 'reservations.res_end AS Hora fin', 'spaces.spa_name AS Espacio',
        'users.use_mail AS Correo', 'reservations.res_status AS Estado')
        ->join('spaces', 'reservations.spa_id', '=', 'spaces.spa_id')
        ->join('users', 'reservations.use_id', '=', 'users.use_id')->where("reservations.".$column,'like', '%'.$data.'%')->OrderBy("reservations.".$column, 'DESC')->get();
        return $reservation;
        }

    public static function ActiveReservUser($use_id, $request){
        $date= date('Y-m-d');
        $reservation = ($request->acc_administrator == 1) ?  DB::table('reservations')->select('reservations.res_id AS No. Reserva', 'reservations.res_date AS Fecha',
        'reservations.res_start AS Hora inicio', 'reservations.res_end AS Hora fin', 'spaces.spa_name AS Espacio',
        'users.use_mail AS Correo', 'reservations.res_status AS Estado')
        ->join('spaces', 'reservations.spa_id', '=', 'spaces.spa_id')
        ->join('users', 'reservations.use_id', '=', 'users.use_id')->where("res_date", ">=" ,$date)->where("res_status","=", 1)->OrderBy("reservations.use_id", 'DESC')->get() : DB::table('reservations')->select('reservations.res_id AS No. Reserva', 'reservations.res_date AS Fecha',
        'reservations.res_start AS Hora inicio', 'reservations.res_end AS Hora fin',
        'spaces.spa_name AS Espacio',
        'users.use_mail AS Correo', 'reservations.res_status AS Estado')
        ->join('spaces', 'reservations.spa_id', '=', 'spaces.spa_id')
        ->join('users', 'reservations.use_id', '=', 'users.use_id')->OrderBy("reservations.use_id", 'DESC')->where("reservations.use_id", '=', $use_id)->where("res_status", "=", 1)->get() ;
        return $reservation;
    }
    public static function Calendar(){
        $date= date('Y-m-d');
        $reservation = DB::select("SELECT reservations.res_id AS 'No. Reserva', reservations.res_date AS 'Fecha',
        reservations.res_start AS 'Hora inicio', reservations.res_end AS 'Hora fin', spaces.spa_name AS 'Espacio',
        users.use_mail AS 'Correo', reservations.use_id AS 'Identificacion', reservations.res_status AS 'Estado' FROM reservations
        INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
        INNER JOIN users ON reservations.use_id = users.use_id
        WHERE reservations.res_date >= '$date' AND reservations.res_status = 1");
        return $reservation;
    }

    public static function users(){

        $users  = DB::select(
            "SELECT us.use_id, MAX(us.use_mail) AS use_mail, MAX(acc.acc_id) AS acc_id FROM users us
            LEFT JOIN access acc ON us.use_id = acc.use_id GROUP BY us.use_id");
        return $users;
    }
    private function TimeZone($validateDay, $request){
        // Convertimos los valores de hora que nos pasa el usuario a datos tipo Carbon
        $newResStart =  carbon::parse($request->res_start);
        $newResEnd = carbon::parse($request->res_end);
        foreach ($validateDay as $validateDayKey)
            // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
            $validatedResStart = carbon::parse($validateDayKey->res_start);
            $validatedResEnd = carbon::parse($validateDayKey->res_end);
            if ($newResStart->lt($validatedResEnd) && $newResEnd->gt($validatedResStart) && $validateDayKey->res_status == 1) {
                // Hay superposición, la nueva reserva no es posible
                return response()->json([
                    'status' => False,
                    'message' => 'Ya existe una reservación activa en este momento.'
                ],400);
            }
    }
}

