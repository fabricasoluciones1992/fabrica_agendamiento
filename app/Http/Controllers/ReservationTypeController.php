<?php

namespace App\Http\Controllers;

use App\Models\ReservationType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ReservationTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $reservationTypes = ReservationType::all();
        if($reservationTypes == null){
            return response()->json([
                'status' => False,
                'message' => 'System do not have reservations.'
            ], 400);
        }else{
            return response()->json($reservationTypes);
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
            'res_typ_name' => 'required|string|min:1|max:60'
        ];
        $validator = Validator::make($request->input(), $rules);
        if($validator->fails()){
            return response()->json([
              'status' => False,
              'message' => $validator->errors()->all()
            ], 400);
        }else{
            $reservationTypes = new ReservationType($request->input());
            $reservationTypes->res_typ_name = $request->res_typ_name;
            $reservationTypes->save();
            return response()->json([
                'status' => True,
                'message' => 'Reservation type created successfully.'
            ], 200);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ReservationType  $reservationTypes
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $reservationType = ReservationType::find($id);
        if($reservationType == null)
        {
            return response()->json([
                'status' => False,
                'message' => 'This space does not exist.'
            ], 400);
        }else{
            return $reservationType;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ReservationType  $reservationTypes
     * @return \Illuminate\Http\Response
     */
    public function edit(ReservationType $reservationType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ReservationTypes  $reservationTypes
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'res_typ_name' => 'required|string|min:1|max:60'
        ];
        $validator = Validator::make($request->input(), $rules);
        if($validator->fails()){
            return response()->json([
              'status' => False,
              'message' => $validator->errors()->all()
            ]);
        }else{
            $reservationTypes = ReservationType::find($id);
            $reservationTypes->res_typ_name = $request->res_typ_name;
            $reservationTypes->save();
            return response()->json([
                'status' => True,
                'message' => 'Reservation type modified successfully'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ReservationType  $reservationTypes
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReservationType $reservationType)
    {
        return response()->json([
            'message' => 'This function is not allowed'
        ]);
    }
}
