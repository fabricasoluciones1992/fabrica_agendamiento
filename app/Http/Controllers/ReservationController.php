<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpParser\Node\Stmt\ElseIf_;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($proj_id, $use_id)
    {
        $reservations = DB::select(
            "SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end,
            reservation_types.res_typ_name,reservations.res_status, spaces.spa_name, users.use_mail, users.use_id
            FROM reservations
            INNER JOIN reservation_types
            ON reservations.res_typ_id = reservation_types.res_typ_id
            INNER JOIN spaces
            ON reservations.spa_id = spaces.spa_id
            INNER JOIN users
            ON reservations.use_id = users.use_id");

        if ($reservations == null)
        {
            return response()->json([
             'status' => False,
             'message' => 'No reservations found'
            ], 400);
        }else{
        Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,$proj_id, $use_id);
        return response()->json([
            'status'=> True,
            'data'=> $reservations
        ], 200);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($proj_id, $use_id, Request $request)
    {

                $rules = [
                    'res_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
                    'res_start' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
                    'res_end' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
                    'res_typ_id' => 'required|integer',
                    'spa_id' => 'required',
                    'use_id' => 'required'

                ];

                $validator = Validator::make($request->input(), $rules);
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
                                                        WHERE reservations.res_date >= '$date'  AND reservations.use_id = $request->use_id");
                                $reservationsSinceDateCount = $reservationsSinceDate[0]->total_res;

                                if($reservationsSinceDateCount < 3 || $request->acc_administrator == 1 ){

                                    if($reservationsUsers == null){
                                        if($request->res_date == $date && $request->res_start <= $actualHour){
                                            return response()->json([
                                                'status' => False,
                                                'message' => 'The reservation initial hour must need to be equal or higher to '.$actualHour.'.'
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
                                                         'message' => 'This space is reserved'
                                                     ],400);
                                                 }
                                            }
                                            Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla reservations ",3,$proj_id, $use_id);
                                                $reservations->save();
                                                return response()->json([
                                                    'status' => True,
                                                    'message' => 'Reservation of the space '.$space->spa_name.' created succesfully in '.$reservations->res_date.' by user: '.$user->use_mail.'.',

                                                ],200);

                                        }else{
                                            Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla reservations ",3,$proj_id, $use_id);
                                            $reservations->save();
                                            return response()->json([
                                                'status' => True,
                                                'message' => 'Reservation of the space '.$space->spa_name.' created succesfully in '.$reservations->res_date.' by user: '.$user->use_mail.'.',

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
                                                    'message' => 'This user has a reservation in room '.$reservationsUsersKey->spa_name.'.'
                                                ],400);
                                            }
                                        }
                                        Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla reservations ",3,$proj_id, $use_id);
                                                $reservations->save();
                                                return response()->json([
                                                    'status' => True,
                                                    'message' => 'Reservation of the space '.$space->spa_name.' created succesfully in '.$reservations->res_date.' by user: '.$user->use_mail.'.',

                                                    ],200);
                                    }
                                }else{
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'This user can not made more reservations.'
                                    ],400);
                                }
                            }else{
                                return response()->json([
                                    'status' => False,
                                    'message' => 'Unvalid time, '.$request->res_end.' must be higher than '.$request->res_start.' and reservation must be in the range of 30 minutes and 2 hours.'
                                ],400);
                            }
                        }else{
                            return response()->json([
                                'status' => False,
                                'message' => 'The space '.$space->spa_name.' is not available.'
                            ],400);
                        }

                    }else{
                        return response()->json([
                            'status' => False,
                            'message' => 'Unvalid time, the space must be reserved between the 07:00 and 19:00 of '.$date.', or a posterior date.'
                        ],400);
                    }
                }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function show($proj_id, $use_id, $id)
    {
       $reservation = DB::select(
            "SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, reservation_types.res_typ_name, spaces.spa_name, users.use_mail
            FROM reservations
            INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
            INNER JOIN users ON reservations.use_id = users.use_id
            WHERE reservations.res_id = $id");

        if ($reservation == null)
        {
            return response()->json([
                'status' => False,
                'message' => 'No reservation found.'
            ],400);
        }else{
        Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,$proj_id, $use_id);

            return response()->json([
                'status' => True,
                'data' => $reservation
            ],200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function update($proj_id, $use_id, Request $request, $id)
    {
                $rules = [
                    'res_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
                    'res_start' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
                    'res_end' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
                    'res_typ_id' => 'required|integer',
                    'spa_id' => 'required|integer',
                    'use_id' => 'required|integer'

                ];

                $validator = Validator::make($request->input(), $rules);
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
                                                                        WHERE reservations.res_date = '$request->res_date' AND reservations.use_id = $request->use_id");
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
                                                'message' => 'The reservation initial hour must need to be equal or higher to '.$actualHour.'.'
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
                                                        'message' => 'Reservation of the space '.$space->spa_name.' created succesfully in '.$reservations->res_date.' by user: '.$user->use_mail.'.'
                                                    ],200);
                                                }elseif ($newResStart->lt($validatedResEnd) && $newResEnd->gt($validatedResStart) && $validateDayKey->res_status == 1) {
                                                    // Hay superposición, la nueva reserva no es posible
                                                    return response()->json([
                                                        'status' => False,
                                                        'message' => 'This space is reserved'
                                                    ],400);
                                                }
                                            }
                                            // Reporte de novedad
                                            Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla reservations ",1,$proj_id, $use_id);
                                            // Se guarda la actualización
                                            $reservations->save();
                                            return response()->json([
                                                'status' => True,
                                                'message' => 'Reservation of the space '.$space->spa_name.' created succesfully in '.$reservations->res_date.' by user: '.$user->use_mail.'.'
                                            ],200);
                                        }
                                        // Reporte de novedad
                                        Controller::NewRegisterTrigger("Se realizó una actualización de datos en la tabla reservations ",1,$proj_id, $use_id);
                                        // Se guarda la novedad
                                        $reservations->save();
                                        return response()->json([
                                            'status' => True,
                                            'message' => 'Reservation of the space '.$space->spa_name.' created succesfully in '.$reservations->res_date.' by user: '.$user->use_mail.'.'
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
                                                  'message' => 'Reservation of the space '.$space->spa_name.' updated succesfully in '.$reservations->res_date.' by user: '.$user->use_mail.'.'
                                                ],200);
                                            }elseif($newResStart->lt($validatedResEnd) && $newResEnd->gt($validatedResStart) && $request->spa_id == $reservationsUsersKey->spa_id && $reservationsUsersKey->res_status == 1){
                                                return response()->json([
                                                    'status' => False,
                                                    'message' => 'This user have a reservation in room '.$reservationsUsers[0]->spa_name.'.'
                                                ],400);
                                            }
                                        }
                                        Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla reservations ",3,$proj_id, $use_id);
                                                $reservations->save();
                                                return response()->json([
                                                    'status' => True,
                                                    'message' => 'Reservation of the space '.$space->spa_name.' updated succesfully in '.$reservations->res_date.' by user: '.$user->use_mail.'.',

                                                    ],200);
                                    }
                                }else{
                                    return response()->json([
                                        'status' => False,
                                        'message' => 'This user can not made more reservations today.'
                                    ],400);
                                }
                            }else{
                                return response()->json([
                                    'status' => False,
                                    'message' => 'Unvalid time, '.$request->res_end.' must be higher than '.$request->res_start.' and reservation must be in the range of 30 minutes and 2 hours.'
                                ],400);
                            }
                        }else{
                            return response()->json([
                                'status' => False,
                                'message' => 'The space '.$space->spa_name.' is not available.'
                            ],400);
                        }
                    }else{
                        return response()->json([
                            'status' => False,
                            'message' => 'Unvalid time, the space must be reserved between the 07:00 and 19:00 of '.$date.', or a posterior date.'
                        ],400);
                    }
                }


        }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function destroy($proj_id, $use_id, Request $request, $id)
    {
        // Control de acciones
        if($request->acc_administrator == 1){
             $desactivate = Reservation::find($id);
            ($desactivate->res_status == 1)?$desactivate->res_status=0:$desactivate->res_status=1;
            $desactivate->save();
            Controller::NewRegisterTrigger("Se cambio el estado de una reserva en la tabla reservations ",2,$proj_id,$use_id);
            return response()->json([
                'message' => 'Status of '.$desactivate->res_status.' changed successfully.',
                'data' => $desactivate
            ],200);
        }else{
            return response()->json([
                'status' => False,
                'message' => 'Access denied. This action can only be performed by active administrators.'
            ]);
        }
    }

    public function reserPerUser($proj_id, $use_id, $id){
        $reservation = DB::select(
            "SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, reservation_types.res_typ_name, spaces.spa_name, users.use_mail, reservations.res_status
            FROM reservations
            INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
            INNER JOIN users ON reservations.use_id = users.use_id
            WHERE reservations.use_id = $id");

        if ($reservation == null)
        {
            return response()->json([
                'status' => False,
                'message' => 'No reservation made.'
            ],400);
        }else{
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,$proj_id, $use_id);
            return response()->json([
                'status' => True,
                'data' => $reservation
            ],200);
        }

    }

    public function reserPerDate($proj_id, $use_id, $date){
        $reservation = DB::select(
            "SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, reservation_types.res_typ_name, spaces.spa_name, users.use_mail, reservations.res_status
            FROM reservations
            INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
            INNER JOIN users ON reservations.use_id = users.use_id
            WHERE reservations.res_date = '$date'");

        if ($reservation == null)
        {
            return response()->json([
                'status' => False,
                'message' => 'No reservation made.'
            ],400);
        }else{
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,$proj_id, $use_id);
            return response()->json([
                'status' => True,
                'data' => $reservation
            ],200);
        }

    }

    public function AdminActiveReserv($proj_id, $use_id){
        $date = date('Y-m-d');
        $reservation = DB::select(
            "SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, reservation_types.res_typ_name, spaces.spa_name, users.use_mail, reservations.res_status
            FROM reservations
            INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
            INNER JOIN users ON reservations.use_id = users.use_id
            WHERE reservations.res_date >= '$date'");

        if ($reservation == null)
        {
            return response()->json([
                'status' => False,
                'message' => 'No reservation made.'
            ],400);
        }else{
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,$proj_id, $use_id);
            return response()->json([
                'status' => True,
                'data' => $reservation
            ],200);
        }

    }


    public function reserPerSpace($proj_id, $use_id, $space){
        $reservation = DB::select(
            "SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, reservation_types.res_typ_name, spaces.spa_name, users.use_mail, reservations.res_status
            FROM reservations
            INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
            INNER JOIN users ON reservations.use_id = users.use_id
            WHERE reservations.spa_id = $space");

        if ($reservation == null)
        {
            return response()->json([
                'status' => False,
                'message' => 'No reservation made.'
            ], 400);
        }else{
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,$proj_id, $use_id);
            return response()->json([
                'status' => True,
                'data' => $reservation
            ],200);
        }

    }

    public function ActiveReservUser($proj_id, $use_id, $id){
        $date= date('Y-m-d');
        $reservation = DB::select(
            "SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end, reservation_types.res_typ_name, spaces.spa_name, users.use_mail, reservations.res_status
            FROM reservations
            INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
            INNER JOIN users ON reservations.use_id = users.use_id
            WHERE reservations.use_id = $id AND reservations.res_date >= '$date'");
        if ($reservation == null)
        {
            return response()->json([
                'status' => False,
                'message' => 'No reservation made.'
            ],400);
        }else{
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,$proj_id, $use_id);
            return response()->json([
                'status' => True,
                'data' => $reservation
            ],200);
        }

    }

    public function users(Request $request){
       if ($request->acc_administrator == 1){
        $users  = DB::select("SELECT us.use_id, MAX(us.use_mail) AS use_mail, MAX(acc.acc_id) AS acc_id
        FROM users us
        LEFT JOIN access acc ON us.use_id = acc.use_id
        GROUP BY us.use_id");
        if($users != null){
            return response()->json([
                'status' => True,
                'data' => $users
                ],200);
            }else{
                return response()->json([
                    'status' => False,
                    'message' => 'No users registered.'
                ],400);
            }
       }else{
            return response()->json([
                'status' => False,
                'message' => 'Access denied.'
            ],400);
       }
    }

}
