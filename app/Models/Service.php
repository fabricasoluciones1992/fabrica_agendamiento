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

    public static function Select()
    {
        $services = DB::table('services AS ser')
            ->join('service_types AS st', 'st.ser_typ_id', '=', 'ser.ser_typ_id')
            ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')
            ->join('users AS u', 'u.use_id', '=', 'ser.use_id')
            ->select('ser.ser_id', 'ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'st.ser_typ_id', 'st.ser_typ_name', 'pro.prof_name', 'u.use_mail', 'u.use_id')
            ->orderBy('ser.ser_date', 'DESC')->limit(100)->get();
        return $services;
    }

    public static function Store($proj_id, $use_id, $request)
    {

        $minHour = Carbon::create($request->ser_start);
        $minHour->add(30, "minute");
        $maxHour = Carbon::create($request->ser_start);
        $maxHour->add(2, "hour");
        $maxHourFormat = $maxHour->format("H:i");
        $minHourFormat = $minHour->format('H:i');
        // Fecha actual
        $date = date('Y-m-d');
        $actualHour = Carbon::now('America/Bogota')->format('H:i');
        // Trae todos los datos de usuarios y salas según el id que trae el request
        $user = User::find($request->use_id);
        if( $user == null){
            return response()->json([
                'status' => False,
                'message' => "El usuario no existe"
            ], 400);
        }
        $profesional = Profesional::find($request->prof_id);
        if( $profesional == null){
            return response()->json([
                'status' => False,
                'message' => "El profesional no existe"
            ], 400);
        }elseif($profesional->prof_status == 0){
            return response()->json([
                'status' => False,
                'message' => "El profesional no está disponible"
            ], 400);
        }
        // Convertimos los valores de hora que nos pasa el usuario a datos tipo Carbon
        $newSerStart = carbon::parse($request->ser_start);
        $newSerEnd = carbon::parse($request->ser_end);
        $serType = ServiceTypes::find($request->ser_typ_id);

        // Se comprueba que solo puedan hacerse reservas del mismo día o días posteriores y la zona horaria de la reserva.
        if ($request->ser_date >= $date && $request->ser_start >= "07:00" && $request->ser_end <= "19:00" && $serType->ser_typ_status != 0) {

            // Se comprueba que el profesional este habilitado
            if ($profesional->prof_status != 0) {
                // Se comprueba que la reserva sea minimo de treinta minutos y máximo de dos horas.
                if ($request->ser_end >= $minHourFormat && $request->ser_end <= $maxHourFormat && $request->ser_start < $request->ser_end) {

                    $servicesSinceDate = DB::select("SELECT COUNT(services.ser_id) AS total_ser
                                                FROM services
                                                WHERE services.ser_date >= '$date'  AND services.use_id = $request->use_id AND services.ser_status = 1");
                    $servicesSinceDateCount = $servicesSinceDate[0]->total_ser;

                    if ($servicesSinceDateCount < 3 || $request->acc_administrator == 1) {
                        $servicesUsers = DB::table('services AS ser')
                            ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')
                            ->join('users AS u', 'u.use_id', '=', 'ser.use_id')
                            ->select('ser.ser_id', 'ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'pro.prof_name', 'pro.prof_id', 'u.use_id')
                            ->where('ser.ser_date', '=', $request->ser_date)
                            ->where('u.use_id', '=', $request->use_id)->get();

                        $validateDay = DB::table('services AS ser')
                            ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')
                            ->join('users AS u', 'u.use_id', '=', 'ser.use_id')
                            ->select('ser.ser_id', 'ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'pro.prof_name', 'pro.prof_id', 'u.use_id')
                            ->where('ser.ser_date', '=', $request->ser_date)
                            ->where('pro.prof_id', '=', $request->prof_id)->get();
                        if ($servicesUsers->isEmpty()) {
                            if ($request->ser_date == $date && $request->ser_start <= $actualHour) {
                                return response()->json([
                                    'status' => False,
                                    'message' => 'La hora inicial de la reserva debe ser igual o mayor a:' . $actualHour . '.'
                                ], 400);
                            } else {
                                if ($validateDay->isEmpty()) {
                                    // Los datos ingresados en el request se almacenan en un nuevo modelo Reservation
                                    $services = new Service($request->input());
                                    $services->ser_status = 1;
                                    $services->save();
                                    Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla services ", 3, $proj_id, $use_id);
                                    return response()->json([
                                        'status' => True,
                                        'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se creo exitosamente el dia ' . $services->ser_date . ' por el usuario: ' . $user->use_mail . '.',
                                    ], 200);
                                } else {

                                    // $ExistReservation = Reservation::TimeZone($validateDay, $request);
                                    foreach ($validateDay as $validateDayKey) {
                                        // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                        $validatedSerStart = carbon::parse($validateDayKey->ser_start);
                                        $validatedSerEnd = carbon::parse($validateDayKey->ser_end);
                                        if ($newSerStart->lt($validatedSerEnd) && $newSerEnd->gt($validatedSerStart) && $validateDayKey->ser_status == 1) {
                                            // Hay superposición, la nueva reserva no es posible
                                            return response()->json([
                                                'status' => False,
                                                'message' => 'Este profesional está reservado'
                                            ], 400);
                                        }
                                    }
                                    // Los datos ingresados en el request se almacenan en un nuevo modelo Reservation
                                    $services = new Service($request->input());
                                    $services->ser_status = 1;
                                    $services->save();
                                    Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla services ", 3, $proj_id, $use_id);
                                    return response()->json([
                                        'status' => True,
                                        'message' => 'La reserva en el profesional  ' . $profesional->prof_name . ' se creó exitosamente el dia ' . $services->ser_date . ' por el usuario: ' . $user->use_mail . '.',
                                    ], 200);
                                }
                            }
                            if (!empty($validateDay)) {
                                // return $validateDay;
                                foreach ($validateDay as $validateDayKey) {
                                    // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                    $validatedResStart = carbon::parse($validateDayKey->ser_start);
                                    $validatedResEnd = carbon::parse($validateDayKey->ser_end);

                                    if ($newSerStart->lt($validatedResEnd) && $newSerEnd->gt($validatedResStart) && $validateDayKey->ser_status == 1) {
                                        // Hay superposición, la nueva reserva no es posible
                                        return response()->json([
                                            'status' => False,
                                            'message' => 'Este profesional está reservado'
                                        ], 400);
                                    }
                                }
                                // Los datos ingresados en el request se almacenan en un nuevo modelo Reservation
                                $services = new Service($request->input());
                                $services->ser_status = 1;
                                $services->save();
                                Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla services ", 3, $proj_id, $use_id);
                                return response()->json([
                                    'status' => True,
                                    'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se creo exitosamente el dia ' . $services->ser_date . ' por el usuario: ' . $user->use_mail . '.',

                                ], 200);

                            } else {
                                // Los datos ingresados en el request se almacenan en un nuevo modelo Reservation
                                $services = new Service($request->input());
                                $services->ser_status = 1;
                                $services->save();
                                Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla services ", 3, $proj_id, $use_id);
                                return response()->json([
                                    'status' => True,
                                    'message' => 'La reserva con el profesional  ' . $profesional->prof_name . ' se creó exitosamente el dia ' . $services->ser_date . ' por el usuario: ' . $user->use_mail . '.',

                                ], 200);
                            }
                        } else {
                            if ($request->ser_date == $date && $request->ser_start <= $actualHour) {
                                return response()->json([
                                    'status' => False,
                                    'message' => 'La hora inicial de la reserva debe ser igual o mayor a:' . $actualHour . '.'
                                ], 400);
                            }
                            if (!empty($validateDay)) {
                                foreach ($servicesUsers as $servicesUsersKey) {
                                    // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                    $validatedResStart = Carbon::parse($servicesUsersKey->ser_start);
                                    $validatedResEnd = carbon::parse($servicesUsersKey->ser_end);
                                    if ($newSerStart->lt($validatedResEnd) && $newSerEnd->gt($validatedResStart) && $servicesUsersKey->ser_status == 1) {
                                        // Hay superposición, la nueva reserva no es posible
                                        return response()->json([
                                            'status' => False,
                                            'message' => 'Este usuario ya tiene una reservacion con el profesional: ' . $servicesUsersKey->prof_name . '.'
                                        ], 400);
                                    }
                                }
                            }
                            // Los datos ingresados en el request se almacenan en un nuevo modelo Reservation
                            $services = new Service($request->input());
                            $services->ser_status = 1;
                            $services->save();
                            Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla services ", 3, $proj_id, $use_id);
                            return response()->json([
                                'status' => True,
                                'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se creo exitosamente el dia ' . $services->ser_date . ' por el usuario: ' . $user->use_mail . '.',
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'status' => False,
                            'message' => 'Este usuario no puede hacer mas reservaciones.'
                        ], 400);
                    }
                } else {
                    return response()->json([
                        'status' => False,
                        'message' => 'Hora invalida, ' . $request->ser_end . ' debe ser mayor a ' . $request->ser_start . ' y el rango de la reserva debe ser mínimo de 30 minutos y máximo 2 horas.'
                    ], 400);
                }
            } else {
                return response()->json([
                    'status' => False,
                    'message' => 'El profesional ' . $profesional->prof_name . ' no está disponible.'
                ], 400);
            }

        } else {
            $message = ($serType->ser_typ_status == 0)
                ? 'El tipo de servicio ' . $serType->ser_typ_name . ' está fuera de servicio.'
                : 'Hora invalida, el profesional se encuentra disponible entre las 7:00AM y las 7:00PM del ' . $date . ', o una fecha posterior.';
            return response()->json([
                'status' => False,
                'message' => $message
            ], 400);
        }
    }

    public static function FindOne($id)
    {
        $service = DB::table('services AS ser')
            ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')
            ->join('users AS u', 'u.use_id', '=', 'ser.use_id')
            ->select('ser.ser_id', 'ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'pro.prof_name', 'u.use_mail', 'u.use_id')
            ->where('ser.ser_id', '=', $id)->first();
        return $service;
    }

    public static function Amend($proj_id, $use_id, $request, $id)
    {

        $minHour = Carbon::create($request->ser_start);
        $minHour->add(30, "minute");
        $maxHour = Carbon::create($request->ser_start);
        $maxHour->add(2, "hour");
        $maxHourFormat = $maxHour->format("H:i");
        $minHourFormat = $minHour->format('H:i');
        // Fecha actual
        $date = date('Y-m-d');
        $actualHour = Carbon::now('America/Bogota')->format('H:i');
        // Trae todos los datos de usuarios y salas según el id que trae el request
        $user = User::find($request->use_id);
        if( $user == null){
            return response()->json([
                'status' => False,
                'message' => "El usuario no existe"
            ], 400);
        }
        $profesional = Profesional::find($request->prof_id);
        if( $profesional == null){
            return response()->json([
                'status' => False,
                'message' => "El profesional no existe"
            ], 400);
        }elseif($profesional->prof_status == 0){
            return response()->json([
                'status' => False,
                'message' => "El profesional no está disponible"
            ], 400);
        }
        $service = Service::find($id);
        if( $service == null){
            return response()->json([
                'status' => False,
                'message' => "La reservación del servicio no existe"
            ], 400);
        }elseif($service->ser_status == 0){
            return response()->json([
                'status' => False,
                'message' => "La reservación del servicio no está disponible"
            ], 400);
        }
        // Convertimos los valores de hora que nos pasa el usuario a datos tipo Carbon

        $newSerStart = carbon::parse($request->ser_start);
        $newSerEnd = carbon::parse($request->ser_end);
        // Se comprueba que solo puedan hacerse reservas del mismo día o días posteriores y la zona horaria de la reserva.
        if ($request->ser_date >= $date && $request->ser_start >= "07:00" && $request->ser_end <= "19:00") {
            // Se comprueba que la sala este habilitada
            // Se comprueba que la reserva sea minimo de treinta minutos y máximo de dos horas.
            if ($request->ser_end >= $minHourFormat && $request->ser_end <= $maxHourFormat && $request->ser_start < $request->ser_end && $profesional->prof_status != 0) {

                $totalservicesDay = DB::select("SELECT COUNT(services.ser_id) AS total_ser
                            FROM services
                            WHERE services.ser_date = '$request->ser_date' AND services.use_id = $request->use_id  AND services.ser_status = 1");
                $totalservicesDayCount = $totalservicesDay[0]->total_ser;
                $servicesUsers = DB::table('services AS ser')
                    ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')
                    ->join('users AS u', 'u.use_id', '=', 'ser.use_id')
                    ->select('ser.ser_id', 'ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'pro.prof_name', 'pro.prof_id', 'u.use_id')
                    ->where('ser.ser_date', '=', $request->ser_date)
                    ->where('u.use_id', '=', $request->use_id)->get();

                $validateDay = DB::table('services AS ser')
                    ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')
                    ->join('users AS u', 'u.use_id', '=', 'ser.use_id')
                    ->select('ser.ser_id', 'ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'pro.prof_name', 'pro.prof_id', 'u.use_id')
                    ->where('ser.ser_date', '=', $request->ser_date)
                    ->where('pro.prof_id', '=', $request->prof_id)->get();

                if ($totalservicesDayCount < 3 || $request->acc_administrator == 1) {
                    if ($servicesUsers->isEmpty()) {
                        if ($request->ser_date == $date && $request->ser_start <= $actualHour) {
                            return response()->json([
                                'status' => False,
                                'message' => 'La hora inicial de la reserva debe ser igual o mayor a:' . $actualHour . '.'
                            ], 400);
                        }
                        if ($validateDay->isEmpty()) {
                            $services = Service::find($id);
                            $services->ser_date = $request->ser_date;
                            $services->ser_start = $request->ser_start;
                            $services->ser_end = $request->ser_end;
                            $services->prof_id = $request->prof_id;
                            $services->use_id = $request->use_id;
                            // Se guarda la actualización
                            $services->save();
                            // Reporte de novedad
                            Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ", 1, $proj_id, $use_id);
                            return response()->json([
                                'status' => True,
                                'message' => 'La reserva en el profesional  ' . $profesional->prof_name . ' se actualizó exitosamente el dia ' . $services->ser_date . ' por el usuario: ' . $user->use_mail . '.'
                            ], 200);
                        } else {
                            foreach ($validateDay as $validateDayKey) {
                                // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                $validatedSerStart = carbon::parse($validateDayKey->ser_start);
                                $validatedSerEnd = carbon::parse($validateDayKey->ser_end);
                                if ($validateDayKey->ser_id == $id) {
                                    $services = Service::find($id);
                                    $services->ser_date = $request->ser_date;
                                    $services->ser_start = $request->ser_start;
                                    $services->ser_end = $request->ser_end;
                                    $services->prof_id = $request->prof_id;
                                    $services->use_id = $request->use_id;
                                    // Se guarda la actualización
                                    $services->save();
                                    // Reporte de novedad
                                    Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ", 1, $proj_id, $use_id);

                                    return response()->json([
                                        'status' => True,
                                        'message' => 'La reserva en el profesional ' . $profesional->prof_name . ' se actualizó exitosamente el dia ' . $services->ser_date . ' por el usuario: ' . $user->use_mail . '.'
                                    ], 200);
                                } elseif ($newSerStart->lt($validatedSerEnd) && $newSerEnd->gt($validatedSerStart) && $validateDayKey->ser_status == 1) {
                                    // Hay superposición, la nueva reserva no es posible
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'Ya existe una reservación en este momento.'
                                    ], 400);
                                }
                            }
                        }
                    } else {
                        if ($request->ser_date == $date && $request->ser_start <= $actualHour) {
                            return response()->json([
                                'status' => False,
                                'message' => 'La hora inicial de la reserva debe ser igual o mayor a:' . $actualHour . '.'
                            ], 400);
                        }
                        if (!$validateDay->isEmpty()) {

                            foreach($validateDay as $validateDayKey){
                                $validatedSerStart = carbon::parse($validateDayKey->ser_start);
                                $validatedSerEnd = carbon::parse($validateDayKey->ser_end);

                                if ($newSerStart->lt($validatedSerEnd) && $newSerEnd->gt($validatedSerStart) && $validateDayKey->prof_id == $request->prof_id) {
                                    // Hay superposición, la nueva reserva no es posible
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'Este profesional está reservado'
                                    ], 400);
                                }
                            }

                            foreach ($servicesUsers as $servicesUsersKey) {

                                $validatedResStart = carbon::parse($servicesUsersKey->ser_start);
                                $validatedResEnd = carbon::parse($servicesUsersKey->ser_end);
                                if ($newSerStart->lt($validatedResEnd) && $newSerEnd->gt($validatedResStart) && $request->prof_id == $servicesUsersKey->prof_id && $servicesUsersKey->ser_status == 1 ) {
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'Ya existe una reservación en este momento.'
                                    ], 400);
                                }
                                if ($servicesUsersKey->ser_id != $id) {
                                //     $services = Service::find($id);
                                //     $services->ser_date = $request->ser_date;
                                //     $services->ser_start = $request->ser_start;
                                //     $services->ser_end = $request->ser_end;
                                //     $services->ser_typ_id = $request->ser_typ_id;
                                //     $services->prof_id = $request->prof_id;
                                //     $services->use_id = $request->use_id;
                                //     // Se guarda la novedad
                                //     $services->save();
                                //     // Reporte de novedad
                                //     Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ", 1, $proj_id, $use_id);

                                //     return response()->json([

                                //         'status' => True,
                                //         'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se actualizó exitosamente el dia' . $services->ser_date . ' por el usuario: ' . $user->use_mail . '.'
                                //     ], 200);
                                // }else{
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'Reserva invalida'
                                    ], 400);

                                }
                            }
                            $services = Service::find($id);
                            $services->ser_date = $request->ser_date;
                            $services->ser_start = $request->ser_start;
                            $services->ser_end = $request->ser_end;
                            $services->ser_typ_id = $request->ser_typ_id;
                            $services->prof_id = $request->prof_id;
                            $services->use_id = $request->use_id;
                            $services->save();
                            Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla services ", 3, $proj_id, $use_id);

                            return response()->json([

                                'status' => True,
                                'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se actualizó exitosamente el dia' . $services->ser_date . ' por el usuario: ' . $user->use_mail . '.',
                            ], 200);
                        } else {
                            foreach ($validateDay as $validateDayKey) {
                                // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                $validatedSerStart = carbon::parse($validateDayKey->ser_start);
                                $validatedSerEnd = carbon::parse($validateDayKey->ser_end);
                                if ($newSerStart->lt($validatedSerEnd) && $newSerEnd->gt($validatedSerStart) && $validateDayKey->ser_status == 1) {
                                    // Hay superposición, la nueva reserva no es posible
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'Ya existe una reserva en este momento'
                                    ], 400);
                                }
                            }
                            $services = Service::find($id);
                            $services->ser_date = $request->ser_date;
                            $services->ser_start = $request->ser_start;
                            $services->ser_end = $request->ser_end;
                            $services->ser_typ_id = $request->ser_typ_id;
                            $services->prof_id = $request->prof_id;
                            $services->use_id = $request->use_id;
                            // Se guarda la novedad
                            $services->save();
                            // Reporte de novedad
                            Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ", 1, $proj_id, $use_id);
                            return response()->json([
                                'status' => True,
                                'message' => 'La reserva con el profesional' . $profesional->prof_name . ' se actualizó exitosamente el dia' . $services->ser_date . ' por el usuario: ' . $user->use_mail . '.'
                            ], 200);
                        }

                    }
                } else {
                    return response()->json([
                        'status' => False,
                        'message' => 'Este usuario no puede hacer mas reservaciones.'
                    ], 400);
                }
            } else {
                return response()->json([
                    'status' => False,
                    'message' => 'Hora invalida, ' . $request->ser_end . ' debe ser mayor a ' . $request->ser_start . ' y el rango de la reserva debe ser mínimo de 30 minutos y máximo 2 horas.'
                ], 400);
            }
        } else {
            $message = ($profesional->prof_status == 0)
                ? 'El profesional ' . $profesional->prof_name . ' no está disponible.'
                : 'Hora invalida, el profesional debe ser reservado entre las 7:00AM y las 7:00PM del ' . $date . ', o una fecha posterior.';
            return response()->json([
                'status' => False,
                'message' => $message
            ], 400);
        }
    }

    public static function ReserFilters($column, $data)
    {
        $reservation = DB::table('services')->select(
            'services.ser_id AS No. Servicio',
            'services.ser_date AS Fecha',
            'services.ser_start AS Hora inicio',
            'services.ser_end AS Hora fin',
            'service_types.ser_typ_name AS Tipo Servicio',
            'profesionals.prof_name AS Profesional',
            'users.use_mail AS Correo',
            'services.ser_status AS Estado'
        )->join('service_types', 'services.ser_typ_id', '=', 'service_types.ser_typ_id')
            ->join('profesionals', 'services.prof_id', '=', 'profesionals.prof_id')
            ->join('users', 'services.use_id', '=', 'users.use_id')->where("services." . $column, 'like', '%' . $data . '%')->OrderBy("services." . $column, 'DESC')->get();
        return $reservation;
    }

    public static function ActiveServiceUser($use_id, $request)
    {
        $date = date('Y-m-d');

        $reservation = ($request->acc_administrator == 1) ? DB::table('services')->select(
            'services.ser_id AS No. Servicio',
            'services.ser_date AS Fecha',
            'services.ser_start AS Hora inicio',
            'services.ser_end AS Hora fin',
            'service_types.ser_typ_name AS Tipo Servicio',
            'profesionals.prof_name AS Profesional',
            'users.use_mail AS Correo',
            'services.ser_status AS Estado'
            )->join('service_types', 'services.ser_typ_id', '=', 'service_types.ser_typ_id')
            ->join('profesionals', 'services.prof_id', '=', 'profesionals.prof_id')
            ->join('users', 'services.use_id', '=', 'users.use_id')->where("ser_date", ">=", $date)->where("ser_status", "=", 1)->OrderBy("services.use_id", 'DESC')->get() : DB::table('services')->select(
                'services.ser_id AS No. Servicio',
                'services.ser_date AS Fecha',
                'services.ser_start AS Hora inicio',
                'services.ser_end AS Hora fin',
                'service_types.ser_typ_name AS Tipo Servicio',
                'profesionals.prof_name AS Profesional',
                'users.use_mail AS Correo',
                'services.ser_status AS Estado'
                 )->join('service_types', 'services.ser_typ_id', '=', 'service_types.ser_typ_id')
                ->join('profesionals', 'services.prof_id', '=', 'profesionals.prof_id')
                ->join('users', 'services.use_id', '=', 'users.use_id')->where("ser_date", ">=", $date)->OrderBy("services.use_id", 'DESC')->where("services.use_id", '=', $use_id)->where("ser_status", "=", 1)->get();
        return $reservation;
    }
    public static function Calendar()
    {
        $date = date('Y-m-d');
        $reservation = DB::select("SELECT services.ser_id AS 'No. Servicio', services.ser_date AS 'Fecha',
        services.ser_start AS 'Hora inicio', services.ser_end AS 'Hora fin',
        service_types.ser_typ_name AS 'Tipo Servicio', profesionals.prof_name AS 'Profesional',
        users.use_mail AS 'Correo', services.use_id AS 'Identificacion', services.ser_status AS 'Estado' FROM services INNER JOIN service_types ON services.ser_typ_id = service_types.ser_typ_id
        INNER JOIN profesionals ON services.prof_id = profesionals.prof_id
        INNER JOIN users ON services.use_id = users.use_id
        WHERE services.ser_date >= '$date' AND services.ser_status = 1");
        return $reservation;
    }

    public static function users()
    {

        $users = DB::select(
            "SELECT us.use_id, MAX(us.use_mail) AS use_mail, MAX(acc.acc_id) AS acc_id FROM users us
            LEFT JOIN access acc ON us.use_id = acc.use_id GROUP BY us.use_id"
        );
        return $users;
    }

    public static function betweenDates($startDate, $endDate)
    {
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
