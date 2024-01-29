<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
            ]);
        }else{

            $date= date('Y-m-d H:i');

            $reservations = new Reservation($request->input());
            $reservations->res_date = $request->res_date;
            $reservations->res_typ_id = $request->res_typ_id;
            $reservations->spa_id = $request->spa_id;
            $reservations->use_id = $request->use_id;
            if($request->res_date >= $date)
            {
                $reservations->save();
                return response()->json([
                    'status' => True,
                    'message' => 'Reservation created succesfully'
                ], 200);
            }else{
                return response()->json([
                    'status' => False,
                    'message' => 'The date of the reservation is invalid'
                ], 200);
            }
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
            'res_date' => 'required',
            'res_typ_id' => 'required',
            'spa_id' => 'required',
            'use_id' => 'required'
        ];

        $validator = Validator::make($request->input(), $rules);
        if($validator->fails())
        {
            return response()->json([
              'status' => False,
              'message' => $validator->errors()->all()
            ], 400);
        }else{

            $reservations = Reservation::find($id);
            $reservations->res_date = $request->res_date;
            $reservations->res_typ_id = $request->res_typ_id;
            $reservations->spa_id = $request->spa_id;
            $reservations->use_id = $request->use_id;
            $reservations->save();
            return response()->json([
                'status' => True,
                'message' => 'Reservation created succesfully'
            ], 200);
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
        return response()->json([
            'status' => False,
            'message'  => 'This function is not allowed'
        ],400);
    }
}
