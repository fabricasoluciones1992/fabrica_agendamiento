<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;


class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $reservations = DB::select(
            "SELECT reservations.res_id, reservations.res_date,
            reservation_types.res_typ_name, spaces.spa_name, users.use_mail
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
             'message' => 'No se encontraron reservaciones'
            ], 400);
        }else{
        Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,1,1);
        return response()->json([
            'status'=> True,
            'data'=> $reservations
        ], 200);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'res_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
            'res_start' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'res_end' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'res_typ_id' => 'required',
            'spa_id' => 'required',
            'use_id' => 'required',

        ];

        $validator = Validator::make($request->input(), $rules);
        if($validator->fails())
        {
            return response()->json([
              'status' => False,
              'message' => $validator->errors()->all()
            ]);
        }else{

            $validate = DB::select("SELECT reservations.res_id, reservations.res_date, reservations.res_start, reservations.res_end
            FROM reservations
            INNER JOIN reservation_types
            ON reservations.res_typ_id = reservation_types.res_typ_id
            INNER JOIN spaces
            ON reservations.spa_id = spaces.spa_id
            INNER JOIN users
            ON reservations.use_id = users.use_id
            WHERE reservations.res_date = '$request->res_date'
            AND spaces.spa_id = $request->spa_id
            ORDER BY reservations.res_start ASC ");

            $minHour = Carbon::create($request->res_start);
            $minHour->add(30,"minute");
            $maxHour = Carbon::create($request->res_start);
            $maxHour->add(2,"hour");
            $hora2 = $maxHour->format("H:i");
            $hora = $minHour->format('H:i');



            $date= date('Y-m-d');


            $user = User::find($request->use_id);
            $space = Space::find($request->spa_id);
            $reservations = new Reservation($request->input());
            $reservations->res_date = $request->res_date;
            $reservations->res_start = $request->res_start;
            $reservations->res_end = $request->res_end;
            $reservations->res_typ_id = $request->res_typ_id;
            $reservations->spa_id = $request->spa_id;
            $reservations->use_id = $request->use_id;


            foreach ($validate as $key) {


                if ($key->res_start == $request->res_start && $key->res_end == $request->res_end) {
                        return response()->json([
                            'status' => False,
                            'message' => 'aaaaa '.$space->spa_name.' created succesfully in '.$reservations->res_date.' by user: '.$user->use_mail.'.'
                        ], 400);
                    }else{
                    // if($validate == null){
                        return response()->json([
                            'status' => True,
                            'message' => 'Reservation of the space '.$space->spa_name.' created succesfully in '.$reservations->res_date.' by user: '.$user->use_mail.'.'
                        ], 200);
                    // }

                }
            }

              /*  $request->validate([

                'res_start' => [
                    'required',
                    'date_format:H:i',
                    'before:res_end',
                    Rule::unique('reservations')->where(function ($query) use ($request) {
                      return $query->where('spa_id', $request->spa_id)
                            ->where('res_date', $request->res_date)
                            ->where('use_id', $request->use_id)
                            ->where(function ($query) use ($request) {
                                $query->orwhereBetween('res_start', [$request->res_start, $request->res_end])
                                    ->orWhereBetween('res_end', [$request->res_start, $request->res_end]);
                            });
                    }),
                ],
                'res_end' => [
                'required',
                'date_format:H:i',
                Rule::unique('reservations')->where(function ($query) use ($request) {
                  return $query->where('spa_id', $request->spa_id)
                            ->where('res_date', $request->res_date)
                            ->where('use_id', $request->use_id)
                        ->where(function ($query) use ($request) {
                            $query->orwhereBetween('res_start', [$request->res_start, $request->res_end])
                                ->orWhereBetween('res_end', [$request->res_start, $request->res_end]);
                        });
                }),

            ]]);



             if($request->res_date >= $date && $request->res_start >= "07:00" && $request->res_end <= "19:00" )
            {

                if ($space->spa_status != 0){
                    if ($request->res_end >= $hora && $request->res_end <= $hora2) {
                        Controller::NewRegisterTrigger("Se realizó una inserción de datos en la tabla reservations ",3,1,1);
                        $reservations->save();
                    return response()->json([
                        'status' => True,
                        'message' => 'Reservation of the space '.$space->spa_name.' created succesfully in '.$reservations->res_date.' by user: '.$user->use_mail.'.'
                    ], 200);
                    }else{
                        return response()->json([
                            'status' => False,
                            'message' => 'Unvalid time'
                           ], 400);
                    }


                }else{
                    return response()->json([
                     'status' => False,
                     'message' => 'Unvalid Space'
                    ], 400);
                }
            }else{
                return response()->json([
                    'status' => False,
                    'message' => 'Unvalid date'
                   ], 400);
            }
            */
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
       $reservation = DB::select(
            "SELECT reservations.res_id, reservations.res_date, reservation_types.res_typ_name, spaces.spa_name, users.use_mail
            FROM reservations
            INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
            INNER JOIN users ON reservations.use_id = users.use_id
            WHERE reservations.res_id = $id");

        if ($reservation == null)
        {
            return response()->json([
                'status' => False,
                'message' => 'No reservation found.'
            ], 400);
        }else{
        Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,1,1);

            return $reservation;
        }


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function edit(Reservation $reservation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'res_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])(\s)([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'res_typ_id' => 'required',
            'spa_id' => 'required',
            'use_id' => 'required',

        ];

        $validator = Validator::make($request->input(), $rules);
        if($validator->fails())
        {
            return response()->json([
              'status' => False,
              'message' => $validator->errors()->all()
            ], 400);
        }else{

            $date= date('Y-m-d H:i');
            $space = Space::find($request->spa_id);
            $reservations = Reservation::find($id);
            $reservations->res_date = $request->res_date;
            $reservations->res_typ_id = $request->res_typ_id;
            $reservations->spa_id = $request->spa_id;
            $reservations->use_id = $request->use_id;
            if($request->res_date >= $date)
            {
                if($space->spa_status != 0)
                {
                    // Control de acciones
                    Controller::NewRegisterTrigger("Se realizó una actualización en la tabla reservations ",1,1,1);
                    $reservations->save();
                    return response()->json([
                        'status' => True,
                        'message' => 'Reservation of the space '.$space->spa_name.' updated succesfully in '.$reservations->res_date.'.'
                    ], 200);
                }else
                {
                    return response()->json([
                      'status' => False,
                      'message' => 'The space '.$space->spa_name.' is not available'
                    ], 400);
                }
            }else{
                return response()->json([
                    'status' => False,
                    'message' => 'The reservation date is invalid.'
                ], 400);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reservation $reservation)
    {
        // Control de acciones
        Controller::NewRegisterTrigger("Se intentó destruir un dato en la tabla reservations ",2,1,1);
        return response()->json([
            'status' => False,
            'message'  => 'This function is not allowed'
        ],400);
    }

  public function reserPerUser($id){
    $reservation = DB::select(
        "SELECT reservations.res_id, reservations.res_date, reservation_types.res_typ_name, spaces.spa_name, users.use_mail
        FROM reservations
        INNER JOIN reservation_types ON reservations.res_typ_id = reservation_types.res_typ_id INNER JOIN spaces ON reservations.spa_id = spaces.spa_id
        INNER JOIN users ON reservations.use_id = users.use_id
        WHERE reservations.use_id = $id");

    if ($reservation == null)
    {
        return response()->json([
            'status' => False,
            'message' => 'No reservation made.'
        ], 400);
    }else{
        // Control de acciones
        Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,1,1);
        return $reservation;
    }

  }

}
