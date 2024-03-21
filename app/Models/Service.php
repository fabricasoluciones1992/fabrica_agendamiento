<?php

namespace App\Models;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Service extends Model
{
    use HasFactory;

    protected $primaryKey = 'ser_id';

    protected $fillable = [
      'ser_date',
      'ser_start',
      'ser_end',
      'ser_status',
      'ser_typ_id',
      'prof_id',
      'use_id'
    ];

    public $timestamps = false;

    public static function Select(){
        $services = DB::select(
            "SELECT services.ser_id, services.ser_date, services.ser_start, services.ser_end,
            service_types.ser_typ_name,services.ser_status, profesionals.prof_name, users.use_mail, users.use_id
            FROM services
            INNER JOIN service_types
            ON services.ser_typ_id = service_types.ser_typ_id
            INNER JOIN profesionals
            ON services.prof_id = profesionals.prof_id
            INNER JOIN users
            ON services.use_id = users.use_id
            ORDER BY services.ser_date DESC
            LIMIT 100");
            return $services;
    }

    public static function Store($proj_id, $use_id, $request){

        $rules = [
            'ser_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
            'ser_start' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'ser_end' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'ser_typ_id' => 'required|integer',
            'prof_id' => 'required',
            'use_id' => 'required'

        ];
        $messages = [
            'ser_date.required' => 'La fecha del servicio es requerida.',
            'ser_date.regex' => 'El formato de la fecha del servicio no es valido.',
            'ser_start.required' => 'La hora inicial del servicio es requerida.',
            'ser_start.regex' => 'El formato de la hora inicial del servicio no es valido.',
            'ser_end.required' => 'La hora final del servicio es requerida.',
            'ser_end.regex' => 'El formato de la hora final del servicio no es valido.',
            'ser_typ_id.required' => 'El tipo de servicio es requerido.',
            'ser_typ_id.integer' => 'El tipo de servicio no es valido.',
            'prof_id.required' => 'El profesional del servicio es requerido.',
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

            $validateDay = DB::select("SELECT services.ser_id, services.ser_date, services.ser_start, services.ser_end, profesionals.prof_name, profesionals.prof_id, users.use_id, services.ser_status
            FROM services
            INNER JOIN service_types ON services.ser_typ_id = service_types.ser_typ_id
            INNER JOIN profesionals ON services.prof_id = profesionals.prof_id
            INNER JOIN users ON services.use_id = users.use_id
            WHERE services.ser_date = '$request->ser_date' AND profesionals.prof_id = $request->prof_id
            ORDER BY services.ser_start ASC ");

            $minHour = Carbon::create($request->ser_start);
            $minHour->add(30,"minute");
            $maxHour = Carbon::create($request->ser_start);
            $maxHour->add(2,"hour");
            $maxHourFormat = $maxHour->format("H:i");
            $minHourFormat = $minHour->format('H:i');

            // Fecha actual
            $date= date('Y-m-d');
            $actualHour = Carbon::now('America/Bogota')->format('H:i');

            // Trae todos los datos de usuarios y salas según el id que trae el request
            $user = User::find($request->use_id);
            $profesional = Profesional::find($request->prof_id);

            // Los datos ingresados en el request se almacenan en un nuevo modelo Reservation
            $services = new Service($request->input());
            $services->ser_date = $request->ser_date;
            $services->ser_start = $request->ser_start;
            $services->ser_end = $request->ser_end;
            $services->ser_typ_id = $request->ser_typ_id;
            $services->ser_status = 1;
            $services->prof_id = $request->prof_id;
            $services->use_id = $request->use_id;

            // Convertimos los valores de hora que nos pasa el usuario a datos tipo Carbon
            $newSerStart = $request->ser_start;
            $newSerEnd = $request->ser_end;
            $newSerStart = carbon::parse($newSerStart);
            $newSerEnd = carbon::parse($newSerEnd);
            $serType = ServiceTypes::find($request->ser_typ_id);
            if($serType->ser_typ_status == 0){
                return response()->json([
                    'status' => False,
                    'message' => 'El tipo de reserva '.$serType->ser_typ_name .' está fuera de servicio'
                ],400);
            }

            // Se comprueba que solo puedan hacerse reservas del mismo día o días posteriores y la zona horaria de la reserva.
            if($request->ser_date >= $date && $request->ser_start >= "07:00" && $request->ser_end <= "19:00")
            {

                // Se comprueba que la sala este habilitada
                if ($profesional->prof_status != 0){
                    // Se comprueba que la reserva sea minimo de treinta minutos y máximo de dos horas.
                    if ($request->ser_end >= $minHourFormat && $request->ser_end <= $maxHourFormat && $request->ser_start < $request->ser_end){

                        /* $totalservicesDay = DB::select("SELECT COUNT(services.ser_id) AS total_ser
                                                            FROM services
                                                            WHERE services.ser_date = '$request->ser_date' AND services.use_id = $request->use_id");
                        $totalservicesDayCount = $totalservicesDay[0]->total_ser; */

                        $servicesUsers = DB::select("SELECT services.ser_id, services.ser_date, services.ser_start, services.ser_end, profesionals.prof_name, profesionals.prof_id, users.use_id, services.ser_status
                                                            FROM services
                                                            INNER JOIN service_types ON services.ser_typ_id = service_types.ser_typ_id
                                                            INNER JOIN profesionals ON services.prof_id = profesionals.prof_id
                                                            INNER JOIN users ON services.use_id = users.use_id
                                                            WHERE services.ser_date = '$request->ser_date' AND services.use_id = $request->use_id");
                       /*  $servicesSinceDate = DB::select("SELECT services.ser_id, services.ser_date, services.ser_start, services.ser_end, profesionals.prof_name, users.use_id
                        FROM services
                        INNER JOIN service_types ON services.ser_typ_id = service_types.ser_typ_id
                        INNER JOIN profesionals ON services.prof_id = profesionals.prof_id
                        INNER JOIN users ON services.use_id = users.use_id
                        WHERE services.ser_date >= $date  AND services.use_id = $request->use_id"); */

                        $servicesSinceDate = DB::select("SELECT COUNT(services.ser_id) AS total_ser
                                                FROM services
                                                WHERE services.ser_date >= '$date'  AND services.use_id = $request->use_id AND services.ser_status = 1");
                        $servicesSinceDateCount = $servicesSinceDate[0]->total_ser;

                        if($servicesSinceDateCount < 3 || $request->acc_administrator == 1){

                            if($servicesUsers == null){
                                if($request->ser_date == $date && $request->ser_start <= $actualHour){
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'La hora inicial de la reserva debe ser igual o mayor a:'.$actualHour.'.'
                                    ],400);
                                }
                                if($validateDay!=null){
                                    // return $validateDay;
                                    foreach ($validateDay as $validateDayKey){
                                         // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                         $validatedResStart = carbon::parse($validateDayKey->ser_start);
                                         $validatedResEnd = carbon::parse($validateDayKey->ser_end);

                                         if ($newSerStart->lt($validatedResEnd) && $newSerEnd->gt($validatedResStart) && $validateDayKey->ser_status == 1){
                                            // Hay superposición, la nueva reserva no es posible
                                             return response()->json([
                                                 'status' => False,
                                                 'message' => 'Este profesional está reservado'
                                             ],400);
                                         }
                                    }
                                    Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla services ",3,$proj_id, $use_id);
                                        $services->save();
                                        return response()->json([
                                            'status' => True,
                                            'message' => 'La reserva con el profesional '.$profesional->prof_name.' se creo exitosamente el dia '.$services->ser_date.' por el usuario: '.$user->use_mail.'.',

                                        ],200);

                                }else{
                                    Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla services ",3,$proj_id, $use_id);
                                    $services->save();
                                    return response()->json([
                                        'status' => True,
                                        'message' => 'La reserva con el profesional  '.$profesional->prof_name.' se creó exitosamente el dia '.$services->ser_date.' por el usuario: '.$user->use_mail.'.',

                                    ],200);
                                }
                            }else{
                                // return $servicesUsers;
                                foreach ($servicesUsers as $servicesUsersKey){
                                    // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                    $validatedResStart = Carbon::parse($servicesUsersKey->ser_start);
                                    $validatedResEnd = carbon::parse($servicesUsersKey->ser_end);
                                    if ($newSerStart->lt($validatedResEnd) && $newSerEnd->gt($validatedResStart) && $servicesUsersKey->ser_status == 1) {
                                        // Hay superposición, la nueva reserva no es posible
                                        return response()->json([
                                            'status' => False,
                                            'message' => 'Este usuario ya tiene una reservacion con el profesional: '.$servicesUsersKey->prof_name.'.'
                                        ],400);
                                    }
                                }
                                Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla services ",3,$proj_id, $use_id);
                                        $services->save();
                                        return response()->json([
                                            'status' => True,
                                            'message' => 'La reserva con el profesional '.$profesional->prof_name.' se creo exitosamente el dia '.$services->ser_date.' por el usuario: '.$user->use_mail.'.',

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
                            'message' => 'Hora invalida, '.$request->ser_end.' debe ser mayor a '.$request->ser_start.' y el rango de la reserva debe ser mínimo de 30 minutos y máximo 2 horas.'
                        ],400);
                    }
                }else{
                    return response()->json([
                        'status' => False,
                        'message' => 'El profesional '.$profesional->prof_name.' no está disponible.'
                    ],400);
                }

            }else{
                return response()->json([
                    'status' => False,
                    'message' => 'Hora invalida, el profesional se encuentra disponible entre las 7:00AM y las 7:00PM del '.$date.', o una fecha posterior.'
                ],400);
            }
        }
    }

    public static function Show($id){
         $services = DB::select(
            "SELECT services.ser_id, services.ser_date, services.ser_start, services.ser_end,
            service_types.ser_typ_name,services.ser_status, profesionals.prof_name, users.use_mail, users.use_id
            FROM services
            INNER JOIN service_types
            ON services.ser_typ_id = service_types.ser_typ_id
            INNER JOIN profesionals
            ON services.prof_id = profesionals.prof_id
            INNER JOIN users
            ON services.use_id = users.use_id
            WHERE services.ser_id = $id");
            return $services;
    }

    public static function Amend($proj_id, $use_id, $request, $id){
        $rules = [
            'ser_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
            'ser_start' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'ser_end' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'ser_typ_id' => 'required|integer',
            'prof_id' => 'required|integer',
            'use_id' => 'required|integer'

        ];

        $messages = [
            'ser_date.required' => 'La fecha de la reserva es requerida.',
            'ser_date.regex' => 'El formato de la fecha de la reserva no es valido.',
            'ser_start.required' => 'La hora inicial de la reserva es requerida.',
            'ser_start.regex' => 'El formato de la hora inicial de la reserva no es valido.',
            'ser_end.required' => 'La hora final de la reserva es requerida.',
            'ser_end.regex' => 'El formato de la hora final de la reserva no es valido.',
            'ser_typ_id.required' => 'El tipo de reserva es requerido.',
            'ser_typ_id.integer' => 'El tipo de reserva no es valido.',
            'prof_id.required' => 'El profesional a reservar es requerido.',
            'use_id.required' => 'El usuario que realiza la reserva es requerido.'
        ];

        $validator = Validator::make($request->input(), $rules, $messages);
        if($validator->fails()){
            return response()->json([
                'status' => False,
                'message' => $validator->errors()->all()
            ],400);
        }else{

            $validateDay = DB::select("SELECT services.ser_id, services.ser_date, services.ser_start, services.ser_end, profesionals.prof_name, users.use_id, services.ser_status
            FROM services
            INNER JOIN service_types ON services.ser_typ_id = service_types.ser_typ_id
            INNER JOIN profesionals ON services.prof_id = profesionals.prof_id
            INNER JOIN users ON services.use_id = users.use_id
            WHERE services.ser_date = '$request->ser_date' AND profesionals.prof_id = $request->prof_id
            ORDER BY services.ser_start ASC ");

            $minHour = Carbon::create($request->ser_start);
            $minHour->add(30,"minute");
            $maxHour = Carbon::create($request->ser_start);
            $maxHour->add(2,"hour");
            $maxHourFormat = $maxHour->format("H:i");
            $minHourFormat = $minHour->format('H:i');

            // Fecha actual
            $date= date('Y-m-d');
            $actualHour = Carbon::now('America/Bogota')->format('H:i');

            // Trae todos los datos de usuarios y salas según el id que trae el request

            $user = User::find($request->use_id);
            $profesional = Profesional::find($request->prof_id);

            // Busca el id de la reserva en la base de datos
            $services = Service::find($id);
            $services->ser_date = $request->ser_date;
            $services->ser_start = $request->ser_start;
            $services->ser_end = $request->ser_end;
            $services->ser_typ_id = $request->ser_typ_id;
            $services->prof_id = $request->prof_id;
            $services->use_id = $request->use_id;

            // Convertimos los valores de hora que nos pasa el usuario a datos tipo Carbon
            $newSerStart = $request->ser_start;
            $newSer = $request->ser_end;
            $newSerStart = carbon::parse($newSerStart);
            $newSer = carbon::parse($newSer);

            // Se comprueba que solo puedan hacerse reservas del mismo día o días posteriores y la zona horaria de la reserva.
            if($request->ser_date >= $date && $request->ser_start >= "07:00" && $request->ser_end <= "19:00"){
                // Se comprueba que la sala este habilitada
                if ($profesional->prof_status != 0){
                    // Se comprueba que la reserva sea minimo de treinta minutos y máximo de dos horas.
                    if ($request->ser_end >= $minHourFormat && $request->ser_end <= $maxHourFormat && $request->ser_start < $request->ser_end){

                        $totalservicesDay = DB::select("SELECT COUNT(services.ser_id) AS total_ser
                                                                FROM services
                                                                WHERE services.ser_date = '$request->ser_date' AND services.use_id = $request->use_id  AND services.ser_status = 1");
                        $totalservicesDayCount = $totalservicesDay[0]->total_ser;
                        $servicesUsers = DB::select("SELECT services.ser_id, services.ser_date, services.ser_start, services.ser_end, profesionals.prof_name, profesionals.prof_id, users.use_id, services.ser_status
                                                    FROM services
                                                    INNER JOIN service_types ON services.ser_typ_id = service_types.ser_typ_id
                                                    INNER JOIN profesionals ON services.prof_id = profesionals.prof_id
                                                    INNER JOIN users ON services.use_id = users.use_id
                                                    WHERE services.ser_date = '$request->ser_date' AND services.ser_start = '$request->ser_start' AND services.use_id = $request->use_id");
                        if($totalservicesDayCount < 3 || $request->acc_administrator == 1){
                            if($servicesUsers == null){
                                if($request->ser_date == $date && $request->ser_start <= $actualHour){
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'La hora inicial de la reserva debe ser igual o mayor a:'.$actualHour.'.'
                                        ],400);
                                    }

                                if($validateDay!=null){

                                    foreach ($validateDay as $validateDayKey){
                                        // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                        $validatedResStart = carbon::parse($validateDayKey->ser_start);
                                        $validatedResEnd = carbon::parse($validateDayKey->ser_end);
                                        if($validateDayKey->ser_id == $id){
                                            // Reporte de novedad
                                            Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ",1,$proj_id, $use_id);
                                            // Se guarda la actualización
                                            $services->save();
                                            return response()->json([
                                                'status' => True,
                                                'message' => 'La reserva con el profesional '.$profesional->prof_name.' se actualizó exitosamente el dia '.$services->ser_date.' por el usuario: '.$user->use_mail.'.'
                                            ],200);
                                        }elseif ($newSerStart->lt($validatedResEnd) && $newSer->gt($validatedResStart) && $validateDayKey->ser_status == 1) {
                                            // Hay superposición, la nueva reserva no es posible
                                            return response()->json([
                                                'status' => False,
                                                'message' => 'Este profesional está reservado'
                                            ],400);
                                        }
                                    }
                                    // Reporte de novedad
                                    Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ",1,$proj_id, $use_id);
                                    // Se guarda la actualización
                                    $services->save();
                                    return response()->json([
                                        'status' => True,
                                        'message' => 'La reserva con el profesional  '.$profesional->prof_name.' se actualizó exitosamente el dia '.$services->ser_date.' por el usuario: '.$user->use_mail.'.'
                                    ],200);
                                }
                                // Reporte de novedad
                                Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ",1,$proj_id, $use_id);
                                // Se guarda la novedad
                                $services->save();
                                return response()->json([
                                    'status' => True,
                                    'message' => 'La reserva con el profesional '.$profesional->prof_name.' se actualizó exitosamente el dia'.$services->ser_date.' por el usuario: '.$user->use_mail.'.'
                                ],200);
                            }else{
                                // return $servicesUsers;
                                foreach ($servicesUsers as $servicesUsersKey){

                                    $validatedResStart = carbon::parse($servicesUsersKey->ser_start);
                                    $validatedResEnd = carbon::parse($servicesUsersKey->ser_end);
                                    if($servicesUsersKey->ser_id == $id){
                                         // Reporte de novedad
                                        Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ",1,$proj_id, $use_id);
                                        // Se guarda la novedad
                                        $services->save();
                                        return response()->json([
                                          'status' => True,
                                          'message' => 'La reserva con el profesional '.$profesional->prof_name.' se actualizó exitosamente el dia'.$services->ser_date.' por el usuario: '.$user->use_mail.'.'
                                        ],200);
                                    }elseif($newSerStart->lt($validatedResEnd) && $newSer->gt($validatedResStart) && $request->prof_id == $servicesUsersKey->prof_id && $servicesUsersKey->ser_status == 1){
                                        return response()->json([
                                            'status' => False,
                                            'message' => 'Este usuario ya tiene una reservacion con el profesional: '.$servicesUsers[0]->prof_name.'.'
                                        ],400);
                                    }
                                }
                                Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla services ",3,$proj_id, $use_id);
                                        $services->save();
                                        return response()->json([
                                            'status' => True,
                                            'message' => 'La reserva con el profesional '.$profesional->prof_name.' se actualizó exitosamente el dia'.$services->ser_date.' por el usuario: '.$user->use_mail.'.',

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
                            'message' => 'Hora invalida, '.$request->ser_end.' debe ser mayor a '.$request->ser_start.' y el rango de la reserva debe ser mínimo de 30 minutos y máximo 2 horas.'
                        ],400);
                    }
                }else{
                    return response()->json([
                        'status' => False,
                        'message' => 'El profesional '.$profesional->prof_name.' no está disponible.'
                    ],400);
                }
            }else{
                return response()->json([
                    'status' => False,
                    'message' => 'Hora invalida, el profesional se encuentra disponible entre las 7:00AM y las 7:00PM del '.$date.', o una fecha posterior.'
                ],400);
            }
        }
    }

    public static function ReserFilters( $column, $data){
        $reservation = DB::table('services')->select('services.ser_id AS No. Servicio', 'services.ser_date AS Fecha',
        'services.ser_start AS Hora inicio', 'services.ser_end AS Hora fin',
        'service_types.ser_typ_name AS Tipo Servicio', 'profesionals.prof_name AS Profesional',
        'users.use_mail AS Correo', 'services.ser_status AS Estado')->join('service_types', 'services.ser_typ_id', '=', 'service_types.ser_typ_id')
        ->join('profesionals', 'services.prof_id', '=', 'profesionals.prof_id')
        ->join('users', 'services.use_id', '=', 'users.use_id')->where("services.".$column,'like', '%'.$data.'%')->OrderBy("services.".$column, 'DESC')->get();
        return $reservation;
        }

    public static function ActiveReservUser($use_id, $request){
        $date= date('Y-m-d');
        $reservation = ($request->acc_administrator == 1) ?  DB::table('services')->select('services.ser_id AS No. Servicio', 'services.ser_date AS Fecha',
        'services.ser_start AS Hora inicio', 'services.ser_end AS Hora fin',
        'service_types.ser_typ_name AS Tipo Servicio', 'profesionals.prof_name AS Profesional',
        'users.use_mail AS Correo', 'services.ser_status AS Estado')->join('service_types', 'services.ser_typ_id', '=', 'service_types.ser_typ_id')
        ->join('profesionals', 'services.prof_id', '=', 'profesionals.prof_id')
        ->join('users', 'services.use_id', '=', 'users.use_id')->where("ser_date", ">=" ,$date)->where("ser_status","=", 1)->OrderBy("services.use_id", 'DESC')->get() : DB::table('services')->select('services.ser_id AS No. Servicio', 'services.ser_date AS Fecha',
        'services.ser_start AS Hora inicio', 'services.ser_end AS Hora fin',
        'service_types.ser_typ_name AS Tipo Servicio', 'profesionals.prof_name AS Profesional',
        'users.use_mail AS Correo', 'services.ser_status AS Estado')->join('service_types', 'services.ser_typ_id', '=', 'service_types.ser_typ_id')
        ->join('profesionals', 'services.prof_id', '=', 'profesionals.prof_id')
        ->join('users', 'services.use_id', '=', 'users.use_id')->OrderBy("services.use_id", 'DESC')->where("services.use_id", '=', $use_id)->where("ser_status", "=", 1)->get() ;
        return $reservation;
    }
    public static function Calendar(){
        $date= date('Y-m-d');
        $reservation = DB::select("SELECT services.ser_id AS 'No. Servicio', services.ser_date AS 'Fecha',
        services.ser_start AS 'Hora inicio', services.ser_end AS 'Hora fin',
        service_types.ser_typ_name AS 'Tipo Servicio', profesionals.prof_name AS 'Profesional',
        users.use_mail AS 'Correo', services.use_id AS 'Identificacion', services.ser_status AS 'Estado' FROM services INNER JOIN service_types ON services.ser_typ_id = service_types.ser_typ_id
        INNER JOIN profesionals ON services.prof_id = profesionals.prof_id
        INNER JOIN users ON services.use_id = users.use_id
        WHERE services.ser_date >= '$date' AND services.ser_status = 1");
        return $reservation;
    }

    public static function users(){

        $users  = DB::select(
            "SELECT us.use_id, MAX(us.use_mail) AS use_mail, MAX(acc.acc_id) AS acc_id FROM users us
            LEFT JOIN access acc ON us.use_id = acc.use_id GROUP BY us.use_id");
        return $users;
    }

    public static function betweenDates($startDate, $endDate){
        return DB::select("SELECT services.ser_id AS 'No. Servicio', services.ser_date AS 'Fecha',
        services.ser_start AS 'Hora inicio', services.ser_end AS 'Hora fin',
        service_types.ser_typ_name AS 'Tipo Servicio', profesionals.prof_name AS 'Profesional',
        users.use_mail AS 'Correo', services.use_id AS 'Identificacion', services.ser_status AS 'Estado' FROM services INNER JOIN service_types ON services.ser_typ_id = service_types.ser_typ_id
        INNER JOIN profesionals ON services.prof_id = profesionals.prof_id
        INNER JOIN users ON services.use_id = users.use_id
        WHERE services.ser_date BETWEEN '$startDate' AND '$endDate'
        ORDER BY services.ser_date DESC");

    }
}
