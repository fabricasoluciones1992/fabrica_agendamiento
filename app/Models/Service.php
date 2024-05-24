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
        'ser_name',
        'ser_start',
        'ser_end',
        'ser_status',
        'ser_quotas',
        'ser_typ_id',
        'prof_id',

    ];

    public $timestamps = false;

    public static function Select()
    {
        $services = DB::table('services AS ser')
            ->join('service_types AS st', 'st.ser_typ_id', '=', 'ser.ser_typ_id')
            ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')

            ->select('ser.ser_id','ser.ser_name', 'ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'ser_quotas', 'st.ser_typ_id', 'st.ser_typ_name', 'pro.prof_name')
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

        $profesional = Profesional::find($request->prof_id);
        if ($profesional == null) {
            return response()->json([
                'status' => False,
                'message' => "El profesional no existe."
            ], 400);
        } elseif ($profesional->prof_status == 0) {
            return response()->json([
                'status' => False,
                'message' => "El profesional no está disponible."
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
                                                WHERE services.ser_date >= '$date'  AND services.ser_status = 1");
                    $servicesSinceDateCount = $servicesSinceDate[0]->total_ser;


                        $servicesUsers = DB::table('services AS ser')
                            ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')

                            ->select('ser.ser_id', 'ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'pro.prof_name', 'pro.prof_id')
                            ->where('ser.ser_date', '=', $request->ser_date)
                         ->get();

                        $validateDay = DB::table('services AS ser')
                            ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')

                            ->select('ser.ser_id', 'ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'pro.prof_name', 'pro.prof_id')
                            ->where('ser.ser_date', '=', $request->ser_date)
                            ->where('pro.prof_id', '=', $request->prof_id)->get();
                        if ($servicesUsers->isEmpty()) {
                            if ($request->ser_date == $date && $request->ser_start <= $actualHour) {
                                return response()->json([
                                    'status' => False,
                                    'message' => 'La hora inicial de la reserva debe ser igual o mayor a: ' . $actualHour . '.'
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
                                        'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se creo exitosamente el dia ' . $services->ser_date . '.',
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
                                                'message' => 'Este profesional está reservado.'
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
                                        'message' => 'La reserva con el profesional  ' . $profesional->prof_name . ' se creó exitosamente el dia ' . $services->ser_date . '.' ,
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
                                            'message' => 'Este profesional está reservado.'
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
                                    'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se creo exitosamente el dia ' . $services->ser_date . '.',

                                ], 200);
                            } else {
                                // Los datos ingresados en el request se almacenan en un nuevo modelo Reservation
                                $services = new Service($request->input());
                                $services->ser_status = 1;
                                $services->save();
                                Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla services ", 3, $proj_id, $use_id);
                                return response()->json([
                                    'status' => True,
                                    'message' => 'La reserva con el profesional  ' . $profesional->prof_name . ' se creó exitosamente el dia ' . $services->ser_date . '.',

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
                                            'message' => 'El profesional ' . $servicesUsersKey->prof_name . ' se encuentra ocupado en esta hora o fecha especifica.'
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
                                'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se creo exitosamente el dia ' . $services->ser_date .  '.',
                            ], 200);
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

            ->select('ser.ser_id', 'ser.ser_name','ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'ser.ser_quotas', 'pro.prof_name')
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

        $profesional = Profesional::find($request->prof_id);
        if ($profesional == null) {
            return response()->json([
                'status' => False,
                'message' => "El profesional no existe."
            ], 400);
        } elseif ($profesional->prof_status == 0) {
            return response()->json([
                'status' => False,
                'message' => "El profesional no está disponible."
            ], 400);
        }
        $service = Service::find($id);
        if ($service == null) {
            return response()->json([
                'status' => False,
                'message' => "La reservación del servicio no existe."
            ], 400);
        } elseif ($service->ser_status == 0) {
            return response()->json([
                'status' => False,
                'message' => "La reservación del servicio no está disponible."
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
                            WHERE services.ser_date = '$request->ser_date' AND services.ser_status = 1");
                $totalservicesDayCount = $totalservicesDay[0]->total_ser;
                $servicesUsers = DB::table('services AS ser')
                    ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')

                    ->select('ser.ser_id', 'ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'pro.prof_name', 'pro.prof_id')
                    ->where('ser.ser_date', '=', $request->ser_date)
                    ->get();

                $validateDay = DB::table('services AS ser')
                    ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')

                    ->select('ser.ser_id', 'ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'pro.prof_name', 'pro.prof_id')
                    ->where('ser.ser_date', '=', $request->ser_date)
                    ->where('pro.prof_id', '=', $request->prof_id)->get();

                if ($totalservicesDayCount < 3) {
                    if ($servicesUsers->isEmpty()) {
                        if ($request->ser_date == $date && $request->ser_start <= $actualHour) {
                            return response()->json([
                                'status' => False,
                                'message' => 'La hora inicial de la reserva debe ser igual o mayor a: ' . $actualHour . '.'
                            ], 400);
                        }
                        if ($validateDay->isEmpty()) {
                            $services = Service::find($id);
                            $services->ser_name = $request->ser_name;
                            $services->ser_date = $request->ser_date;
                            $services->ser_start = $request->ser_start;
                            $services->ser_end = $request->ser_end;
                            $services->ser_quotas = $request->ser_quotas;
                            $services->ser_typ_id = $request->ser_typ_id;
                            $services->prof_id = $request->prof_id;
                            // Se guarda la actualización
                            $services->save();
                            // Reporte de novedad
                            Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ", 1, $proj_id, $use_id);
                            return response()->json([
                                'status' => True,
                                'message' => 'La reserva con el profesional  ' . $profesional->prof_name . ' se actualizó exitosamente el dia ' . $services->ser_date .  '.'
                            ], 200);
                        } else {
                            foreach ($validateDay as $validateDayKey) {
                                // Pasamos los datos de la hora de reserva que llegan de la base de datos a tipo carbon
                                $validatedSerStart = carbon::parse($validateDayKey->ser_start);
                                $validatedSerEnd = carbon::parse($validateDayKey->ser_end);
                                if ($validateDayKey->ser_id == $id) {
                                    $services = Service::find($id);
                                    $services->ser_date = $request->ser_date;
                                    $services->ser_name = $request->ser_name;
                                    $services->ser_start = $request->ser_start;
                                    $services->ser_end = $request->ser_end;
                                    $services->ser_quotas = $request->ser_quotas;
                                    $services->ser_typ_id = $request->ser_typ_id;
                                    $services->prof_id = $request->prof_id;
                                    // Se guarda la actualización
                                    $services->save();
                                    // Reporte de novedad
                                    Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ", 1, $proj_id, $use_id);

                                    return response()->json([
                                        'status' => True,
                                        'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se actualizó exitosamente el dia ' . $services->ser_date . '.'
                                    ], 200);
                                } elseif ($newSerStart->lt($validatedSerEnd) && $newSerEnd->gt($validatedSerStart) && $validateDayKey->ser_status == 1) {
                                    // Hay superposición, la nueva reserva no es posible
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'Ya existe una reservación en este momento.'
                                    ], 400);
                                }
                            }

                            $services = Service::find($id);
                            $services->ser_date = $request->ser_date;
                            $services->ser_name = $request->ser_name;
                            $services->ser_start = $request->ser_start;
                            $services->ser_end = $request->ser_end;
                            $services->ser_quotas = $request->ser_quotas;
                            $services->prof_id = $request->prof_id;
                            // Se guarda la actualización
                            $services->save();
                            // Reporte de novedad
                            Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ", 1, $proj_id, $use_id);

                            return response()->json([
                                'status' => True,
                                'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se actualizó exitosamente el dia ' . $services->ser_date .  '.'
                            ], 200);
                        }
                    } else {
                        if ($request->ser_date == $date && $request->ser_start <= $actualHour) {
                            return response()->json([
                                'status' => False,
                                'message' => 'La hora inicial de la reserva debe ser igual o mayor a:' . $actualHour . '.'
                            ], 400);
                        }
                        if (!$validateDay->isEmpty()) {

                            foreach ($validateDay as $validateDayKey) {
                                $validatedSerStart = carbon::parse($validateDayKey->ser_start);
                                $validatedSerEnd = carbon::parse($validateDayKey->ser_end);

                                if ($newSerStart->lt($validatedSerEnd) && $newSerEnd->gt($validatedSerStart) && $validateDayKey->prof_id == $request->prof_id && $validateDayKey->ser_id != $id) {
                                    // Hay superposición, la nueva reserva no es posible
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'Este profesional está reservado.'
                                    ], 400);
                                }
                            }

                            foreach ($servicesUsers as $servicesUsersKey) {
                                $validatedResStart = carbon::parse($servicesUsersKey->ser_start);
                                $validatedResEnd = carbon::parse($servicesUsersKey->ser_end);
                                if ($newSerStart->lt($validatedResEnd) && $newSerEnd->gt($validatedResStart) && $request->prof_id == $servicesUsersKey->prof_id && $servicesUsersKey->ser_status == 1 && $servicesUsersKey->ser_id != $id) {
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'Ya existe una reservación en este momento.'
                                    ], 400);
                                }

                                if ($servicesUsersKey->ser_id == $id) {
                                        $services = Service::find($id);
                                        $services->ser_date = $request->ser_date;
                                        $services->ser_name = $request->ser_name;
                                        $services->ser_start = $request->ser_start;
                                        $services->ser_end = $request->ser_end;
                                        $services->ser_quotas = $request->ser_quotas;
                                        $services->ser_typ_id = $request->ser_typ_id;
                                        $services->prof_id = $request->prof_id;
                                        // Se guarda la novedad
                                        $services->save();
                                        // Reporte de novedad
                                        Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ", 1, $proj_id, $use_id);

                                        return response()->json([

                                            'status' => True,
                                            'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se actualizó exitosamente el dia' . $services->ser_date  . '.'
                                        ], 200);
                                    }

                            }

                                // return response()->json([
                                //     'status' => False,
                                //     'message' => 'Reserva invalida.'
                                // ], 400);
                                $services = Service::find($id);
                                        $services->ser_date = $request->ser_date;
                                        $services->ser_name = $request->ser_name;
                                        $services->ser_start = $request->ser_start;
                                        $services->ser_end = $request->ser_end;
                                        $services->ser_quotas = $request->ser_quotas;
                                        $services->ser_typ_id = $request->ser_typ_id;
                                        $services->prof_id = $request->prof_id;
                                        // Se guarda la novedad
                                        $services->save();
                                        // Reporte de novedad
                                        Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ", 1, $proj_id, $use_id);

                                        return response()->json([

                                            'status' => True,
                                            'message' => 'La reserva con el profesional ' . $profesional->prof_name . ' se actualizó exitosamente el dia' . $services->ser_date . '.'
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
                                        'message' => 'Ya existe una reserva en este momento.'
                                    ], 400);
                                }
                            }
                            $services = Service::find($id);
                            $services->ser_date = $request->ser_date;
                            $services->ser_name = $request->ser_name;
                            $services->ser_start = $request->ser_start;
                            $services->ser_end = $request->ser_end;
                            $services->ser_quotas = $request->ser_quotas;
                            $services->ser_typ_id = $request->ser_typ_id;
                            $services->prof_id = $request->prof_id;
                            // Se guarda la novedad
                            $services->save();
                            // Reporte de novedad
                            Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla services ", 1, $proj_id, $use_id);
                            return response()->json([
                                'status' => True,
                                'message' => 'La reserva con el profesional' . $profesional->prof_name . ' se actualizó exitosamente el dia' . $services->ser_date .  '.'
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
            'services.ser_name AS Nombre del servicio',
            'services.ser_date AS Fecha',
            'services.ser_start AS Hora inicio',
            'services.ser_end AS Hora fin',
            'services.ser_quotas AS Cupos',
            'service_types.ser_typ_name AS Tipo Servicio',
            'profesionals.prof_name AS Profesional',

            'services.ser_status AS Estado'
        )->join('service_types', 'services.ser_typ_id', '=', 'service_types.ser_typ_id')
            ->join('profesionals', 'services.prof_id', '=', 'profesionals.prof_id')
           ->where("services." . $column, 'like', '%' . $data . '%')->OrderBy("services." . $column, 'DESC')->get();
        return $reservation;
    }

    public static function ActiveServiceUser( $request)
    {
        $date = date('Y-m-d');

        $reservation = ($request->acc_administrator == 1) ? DB::table('services')->select(
            'services.ser_id AS No. Servicio',
            'services.ser_name AS Nombre del servicio',
            'services.ser_date AS Fecha',
            'services.ser_start AS Hora inicio',
            'services.ser_end AS Hora fin',
            'services.ser_quotas AS Cupos',
            'service_types.ser_typ_name AS Tipo Servicio',
            'profesionals.prof_name AS Profesional',

            'services.ser_status AS Estado'
        )->join('service_types', 'services.ser_typ_id', '=', 'service_types.ser_typ_id')
            ->join('profesionals', 'services.prof_id', '=', 'profesionals.prof_id')
            ->where("ser_date", ">=", $date)->where("ser_status", "=", 1)->get()

            : DB::table('services')->select(
                'services.ser_id AS No. Servicio',
                'services.ser_name AS Nombre del servicio',
                'services.ser_date AS Fecha',
                'services.ser_start AS Hora inicio',
                'services.ser_end AS Hora fin',
                'services.ser_quotas AS Cupos',
                'service_types.ser_typ_name AS Tipo Servicio',
                'profesionals.prof_name AS Profesional',

                'services.ser_status AS Estado'
            )->join('service_types', 'services.ser_typ_id', '=', 'service_types.ser_typ_id')
            ->join('profesionals', 'services.prof_id', '=', 'profesionals.prof_id')
          ->where("ser_date", ">=", $date)->where("ser_status", "=", 1)->get();
        return $reservation;
    }
    public static function Calendar()
    {
        $date = date('Y-m-d');
        $reservation = DB::select("SELECT services.ser_id AS 'No. Servicio', services.ser_name AS 'Nombre del servicio',services.ser_date AS 'Fecha',
        services.ser_start AS 'Hora inicio', services.ser_end AS 'Hora fin', services.ser_quotas AS Cupos,
        service_types.ser_typ_name AS 'Tipo Servicio', profesionals.prof_name AS 'Profesional',
        services.ser_status AS 'Estado' FROM services INNER JOIN service_types ON services.ser_typ_id = service_types.ser_typ_id
        INNER JOIN profesionals ON services.prof_id = profesionals.prof_id

        WHERE services.ser_date >= '$date' AND services.ser_status = 1");
        return $reservation;
    }


    public static function betweenDates($startDate, $endDate)
    {
        return DB::select("SELECT services.ser_id AS 'No. Servicio', services.ser_name AS 'Nombre del servicio', services.ser_date AS 'Fecha',
        services.ser_start AS 'Hora inicio', services.ser_end AS 'Hora fin', services.ser_quotas AS Cupos,
        service_types.ser_typ_name AS 'Tipo Servicio', profesionals.prof_name AS 'Profesional',
         services.ser_status AS 'Estado' FROM services INNER JOIN service_types ON services.ser_typ_id = service_types.ser_typ_id
        INNER JOIN profesionals ON services.prof_id = profesionals.prof_id
        WHERE services.ser_date BETWEEN '$startDate' AND '$endDate'
        ORDER BY services.ser_date DESC");
    }

    public static function substractQuote($request)
    {
        $valQuotes = DB::table('services AS ser')
            ->select('ser.ser_id', 'ser.ser_quotas')
            ->where('ser.ser_id', $request->ser_id)
            ->first();

        $quotesCount = DB::table('biblioteca_inscriptions')
            ->where('ser_id', $request->ser_id)
            ->where('bio_ins_status', 1)
            ->count();
        // return $quotesCount;
        // return $valQuotes->ser_quotas;
        if ($valQuotes->ser_quotas > $quotesCount) {
            return true;
        } else {
            return false;
        }
    }

    public static function incriptionsPerService($id){
        $service = DB::table('services AS ser')
            ->join('profesionals AS pro', 'pro.prof_id', '=', 'ser.prof_id')
            ->join('users AS u', 'u.use_id', '=', 'ser.use_id')

            ->select('ser.ser_id', 'ser.ser_name','ser.ser_date', 'ser.ser_start', 'ser.ser_end', 'ser.ser_status', 'ser.ser_quotas', 'pro.prof_name', 'u.use_mail', 'u.use_id')
            ->where('ser.ser_id', '=', $id)->first();

            $users = DB::table('biblioteca_inscriptions as bi')
            ->join('services as se', 'bi.ser_id', '=','se.ser_id')
            ->join('students as st', 'bi.stu_id', '=','st.stu_id')
            ->join('persons as pe', 'st.per_id','=','pe.per_id')
            ->join('users as u', 'pe.use_id', '=','u.use_id')

            ->select('bi.bio_ins_id', 'bi.bio_ins_date', 'u.use_mail', 'bi.bio_ins_status', 'bi.stu_id', 'bi.ser_id','pe.per_name','pe.per_lastname','pe.per_document')
            ->where('bi.ser_id', $id)
            ->orderBy('bi.bio_ins_date', 'asc')
            ->get();
            $service->users = ($users == '[]') ? 'No hay usuarios ingresados' : $users;
            return $service;
    }
}
