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
      'res_typ_id',
      'spa_id',
      'use_id'
    ];

    public $timestamps = false;

    public static function Select(){
        $reservations = DB::select(
            "SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end,
            reservation_types.res_typ_name,reservations.res_status, spaces.spa_name, users.use_mail, users.use_id
            FROM reservations
            INNER JOIN reservation_types
            ON reservations.res_typ_id = reservation_types.res_typ_id
            INNER JOIN spaces
            ON reservations.spa_id = spaces.spa_id
            INNER JOIN users
            ON reservations.use_id = users.use_id
            ORDER BY reservations.res_date DESC
            LIMIT 100");
            return $reservations;
    }

    public static function Store($proj_id, $use_id, $request){

        $rules = [
            'res_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
            'res_start' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'res_end' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'res_typ_id' => 'required|integer',
            'spa_id' => 'required',
            'use_id' => 'required'

        ];
        $messages = [
            'res_date.required' => 'La fecha de la reserva es requerida.',
            'res_date.regex' => 'El formato de la fecha de la reserva no es valido.',
            'res_start.required' => 'La hora inicial de la reserva es requerida.',
            'res_start.regex' => 'El formato de la hora inicial de la reserva no es valido.',
            'res_end.required' => 'La hora final de la reserva es requerida.',
            'res_end.regex' => 'El formato de la hora final de la reserva no es valido.',
            'res_typ_id.required' => 'El tipo de reserva es requerido.',
            'res_typ_id.integer' => 'El tipo de reserva no es valido.',
            'spa_id.required' => 'El espacio a reservar es requerido.',
            'use_id.required' => 'El usuario que realiza la reserva es requerido.'
        ];

        $validator = Validator::make($request->input(), $rules, $messages);
        if($validator->fails())
        {
            return response()->json([
              'status' => False,
              'message' => $validator->errors()->all()
            ],400);
        }else{

            $validateDay = DB::select("SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, spaces.spa_name, spaces.spa_id, users.use_id, reservations.res_status
            FROM reservations
            INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id
            INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
            INNER JOIN users ON reservations.use_id = users.use_id
            WHERE reservations.res_date = '$request->res_date' AND spaces.spa_id = $request->spa_id
            ORDER BY reservations.res_start ASC ");

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

            // Los datos ingresados en el request se almacenan en un nuevo modelo Reservation
            $reservations = new Reservation($request->input());
            $reservations->res_date = $request->res_date;
            $reservations->res_start = $request->res_start;
            $reservations->res_end = $request->res_end;
            $reservations->res_typ_id = $request->res_typ_id;
            $reservations->res_status = 1;
            $reservations->spa_id = $request->spa_id;
            $reservations->use_id = $request->use_id;

            // Convertimos los valores de hora que nos pasa el usuario a datos tipo Carbon
            $newResStart = $request->res_start;
            $newResEnd = $request->res_end;
            $newResStart = carbon::parse($newResStart);
            $newResEnd = carbon::parse($newResEnd);


            // Se comprueba que solo puedan hacerse reservas del mismo día o días posteriores y la zona horaria de la reserva.
            if($request->res_date >= $date && $request->res_start >= "07:00" && $request->res_end <= "19:00")
            {

                // Se comprueba que la sala este habilitada
                if ($space->spa_status != 0){
                    // Se comprueba que la reserva sea minimo de treinta minutos y máximo de dos horas.
                    if ($request->res_end >= $minHourFormat && $request->res_end <= $maxHourFormat && $request->res_start < $request->res_end){

                        /* $totalReservationsDay = DB::select("SELECT COUNT(reservations.res_id) AS total_res
                                                            FROM reservations
                                                            WHERE reservations.res_date = '$request->res_date' AND reservations.use_id = $request->use_id");
                        $totalReservationsDayCount = $totalReservationsDay[0]->total_res; */

                        $reservationsUsers = DB::select("SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, spaces.spa_name, spaces.spa_id, users.use_id, reservations.res_status
                                                            FROM reservations
                                                            INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id
                                                            INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
                                                            INNER JOIN users ON reservations.use_id = users.use_id
                                                            WHERE reservations.res_date = '$request->res_date' AND reservations.use_id = $request->use_id");
                       /*  $reservationsSinceDate = DB::select("SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, spaces.spa_name, users.use_id
                        FROM reservations
                        INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id
                        INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
                        INNER JOIN users ON reservations.use_id = users.use_id
                        WHERE reservations.res_date >= $date  AND reservations.use_id = $request->use_id"); */

                        $reservationsSinceDate = DB::select("SELECT COUNT(reservations.res_id) AS total_res
                                                FROM reservations
                                                WHERE reservations.res_date >= '$date'  AND reservations.use_id = $request->use_id AND reservations.res_status = 1");
                        $reservationsSinceDateCount = $reservationsSinceDate[0]->total_res;

                        if($reservationsSinceDateCount < 3 || $request->acc_administrator == 1){

                            if($reservationsUsers == null){
                                if($request->res_date == $date && $request->res_start <= $actualHour){
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'La hora inicial de la reserva debe ser igual o mayor a:'.$actualHour.'.'
                                    ],400);
                                }
                                if($validateDay!=null){
                                    // return $validateDay;
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
                                    Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla reservations ",3,$proj_id, $use_id);
                                        $reservations->save();
                                        return response()->json([
                                            'status' => True,
                                            'message' => 'La reserva en el espacio '.$space->spa_name.' se creo exitosamente el dia '.$reservations->res_date.' por el usuario: '.$user->use_mail.'.',

                                        ],200);

                                }else{
                                    Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla reservations ",3,$proj_id, $use_id);
                                    $reservations->save();
                                    return response()->json([
                                        'status' => True,
                                        'message' => 'La reserva en el espacio  '.$space->spa_name.' se creó exitosamente el dia '.$reservations->res_date.' por el usuario: '.$user->use_mail.'.',

                                    ],200);
                                }
                            }else{
                                // return $reservationsUsers;
                                foreach ($reservationsUsers as $reservationsUsersKey){
                                    // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                    $validatedResStart = carbon::parse($reservationsUsersKey->res_start);
                                    $validatedResEnd = carbon::parse($reservationsUsersKey->res_end);
                                    if ($newResStart->lt($validatedResEnd) && $newResEnd->gt($validatedResStart) && $reservationsUsersKey->res_status == 1) {
                                        // Hay superposición, la nueva reserva no es posible
                                        return response()->json([
                                            'status' => False,
                                            'message' => 'Este usuario ya tiene una reservacion en la sala: '.$reservationsUsersKey->spa_name.'.'
                                        ],400);
                                    }
                                }
                                Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla reservations ",3,$proj_id, $use_id);
                                        $reservations->save();
                                        return response()->json([
                                            'status' => True,
                                            'message' => 'La reserva en el espacio '.$space->spa_name.' se creo exitosamente el dia '.$reservations->res_date.' por el usuario: '.$user->use_mail.'.',

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
                    return response()->json([
                        'status' => False,
                        'message' => 'El espacio '.$space->spa_name.' no está disponible.'
                    ],400);
                }

            }else{
                return response()->json([
                    'status' => False,
                    'message' => 'Hora invalida, el espacio debe ser reservado entre las 7:00AM y las 7:00PM del '.$date.', o una fecha posterior.'
                ],400);
            }
        }
    }
    public static function Show($id){
        $reservation =  DB::select(
            "SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, reservation_types.res_typ_name, spaces.spa_name, users.use_mail
            FROM reservations
            INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
            INNER JOIN users ON reservations.use_id = users.use_id
            WHERE reservations.res_id = $id");
            return $reservation;
    }
    public static function Amend($proj_id, $use_id, $request, $id){
        $rules = [
            'res_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
            'res_start' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'res_end' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'res_typ_id' => 'required|integer',
            'spa_id' => 'required|integer',
            'use_id' => 'required|integer'

        ];

        $messages = [
            'res_date.required' => 'La fecha de la reserva es requerida.',
            'res_date.regex' => 'El formato de la fecha de la reserva no es valido.',
            'res_start.required' => 'La hora inicial de la reserva es requerida.',
            'res_start.regex' => 'El formato de la hora inicial de la reserva no es valido.',
            'res_end.required' => 'La hora final de la reserva es requerida.',
            'res_end.regex' => 'El formato de la hora final de la reserva no es valido.',
            'res_typ_id.required' => 'El tipo de reserva es requerido.',
            'res_typ_id.integer' => 'El tipo de reserva no es valido.',
            'spa_id.required' => 'El espacio a reservar es requerido.',
            'use_id.required' => 'El usuario que realiza la reserva es requerido.'
        ];

        $validator = Validator::make($request->input(), $rules, $messages);
        if($validator->fails()){
            return response()->json([
                'status' => False,
                'message' => $validator->errors()->all()
            ],400);
        }else{

            $validateDay = DB::select("SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, spaces.spa_name, users.use_id, reservations.res_status
            FROM reservations
            INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id
            INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
            INNER JOIN users ON reservations.use_id = users.use_id
            WHERE reservations.res_date = '$request->res_date' AND spaces.spa_id = $request->spa_id
            ORDER BY reservations.res_start ASC ");

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
            $reservations->res_typ_id = $request->res_typ_id;
            $reservations->spa_id = $request->spa_id;
            $reservations->use_id = $request->use_id;

            // Convertimos los valores de hora que nos pasa el usuario a datos tipo Carbon
            $newResStart = $request->res_start;
            $newResEnd = $request->res_end;
            $newResStart = carbon::parse($newResStart);
            $newResEnd = carbon::parse($newResEnd);

            // Se comprueba que solo puedan hacerse reservas del mismo día o días posteriores y la zona horaria de la reserva.
            if($request->res_date >= $date && $request->res_start >= "07:00" && $request->res_end <= "19:00"){
                // Se comprueba que la sala este habilitada
                if ($space->spa_status != 0){
                    // Se comprueba que la reserva sea minimo de treinta minutos y máximo de dos horas.
                    if ($request->res_end >= $minHourFormat && $request->res_end <= $maxHourFormat && $request->res_start < $request->res_end){

                        $totalReservationsDay = DB::select("SELECT COUNT(reservations.res_id) AS total_res
                                                                FROM reservations
                                                                WHERE reservations.res_date = '$request->res_date' AND reservations.use_id = $request->use_id  AND reservations.res_status = 1");
                        $totalReservationsDayCount = $totalReservationsDay[0]->total_res;
                        $reservationsUsers = DB::select("SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, spaces.spa_name, spaces.spa_id, users.use_id, reservations.res_status
                                                    FROM reservations
                                                    INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id
                                                    INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
                                                    INNER JOIN users ON reservations.use_id = users.use_id
                                                    WHERE reservations.res_date = '$request->res_date' AND reservations.res_start = '$request->res_start' AND reservations.use_id = $request->use_id");
                        if($totalReservationsDayCount < 3 || $request->acc_administrator == 1){
                            if($reservationsUsers == null){
                                if($request->res_date == $date && $request->res_start <= $actualHour){
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'La hora inicial de la reserva debe ser igual o mayor a:'.$actualHour.'.'
                                        ],400);
                                    }

                                if($validateDay!=null){

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
                                                'message' => 'Este espacio está reservado'
                                            ],400);
                                        }
                                    }
                                    // Reporte de novedad
                                    Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla reservations ",1,$proj_id, $use_id);
                                    // Se guarda la actualización
                                    $reservations->save();
                                    return response()->json([
                                        'status' => True,
                                        'message' => 'La reserva en el espacio  '.$space->spa_name.' se actualizó exitosamente el dia '.$reservations->res_date.' por el usuario: '.$user->use_mail.'.'
                                    ],200);
                                }
                                // Reporte de novedad
                                Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla reservations ",1,$proj_id, $use_id);
                                // Se guarda la novedad
                                $reservations->save();
                                return response()->json([
                                    'status' => True,
                                    'message' => 'La reserva en el espacio '.$space->spa_name.' se actualizó exitosamente el dia'.$reservations->res_date.' por el usuario: '.$user->use_mail.'.'
                                ],200);
                            }else{
                                // return $reservationsUsers;
                                foreach ($reservationsUsers as $reservationsUsersKey){

                                    $validatedResStart = carbon::parse($reservationsUsersKey->res_start);
                                    $validatedResEnd = carbon::parse($reservationsUsersKey->res_end);
                                    if($reservationsUsersKey->res_id == $id){
                                         // Reporte de novedad
                                        Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla reservations ",1,$proj_id, $use_id);
                                        // Se guarda la novedad
                                        $reservations->save();
                                        return response()->json([
                                          'status' => True,
                                          'message' => 'La reserva en el espacio '.$space->spa_name.' se actualizó exitosamente el dia'.$reservations->res_date.' por el usuario: '.$user->use_mail.'.'
                                        ],200);
                                    }elseif($newResStart->lt($validatedResEnd) && $newResEnd->gt($validatedResStart) && $request->spa_id == $reservationsUsersKey->spa_id && $reservationsUsersKey->res_status == 1){
                                        return response()->json([
                                            'status' => False,
                                            'message' => 'Este usuario ya tiene una reservacion en la sala: '.$reservationsUsers[0]->spa_name.'.'
                                        ],400);
                                    }
                                }
                                Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla reservations ",3,$proj_id, $use_id);
                                        $reservations->save();
                                        return response()->json([
                                            'status' => True,
                                            'message' => 'La reserva en el espacio '.$space->spa_name.' se actualizó exitosamente el dia'.$reservations->res_date.' por el usuario: '.$user->use_mail.'.',

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
                    return response()->json([
                        'status' => False,
                        'message' => 'El espacio '.$space->spa_name.' no está disponible.'
                    ],400);
                }
            }else{
                return response()->json([
                    'status' => False,
                    'message' => 'Hora invalida, el espacio debe ser reservado entre las 7:00AM y las 7:00PM del '.$date.', o una fecha posterior.'
                ],400);
            }
        }
    }


    public static function ReserFilters( $column, $data){
        $reservation = DB::table('reservations')->where($column,'like', '%'.$data.'%')->OrderBy($column, 'DESC')->get();
        return $reservation;
        }

    public static function ActiveReservUser($use_id, $request){
        $date= date('Y-m-d');
        $reservation = ($request->acc_administrator == 1) ?  DB::table('reservations')->where("res_date", ">=" ,$date)->OrderBy("use_id", 'DESC')->get() : DB::table('reservations')->OrderBy("use_id", 'DESC')->where("use_id", '=', $use_id)->where("res_status", "=", 1)->get() ;
        return $reservation;
    }
    public static function Calendar(){
        $date= date('Y-m-d');
        $reservation = DB::select("SELECT reservations.res_id, reservations.res_date,
        reservations.res_start, reservations.res_end, spaces.spa_name, spaces.spa_id, users.use_id, reservations.res_status, reservation_types.res_typ_name FROM reservations INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id
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

}

