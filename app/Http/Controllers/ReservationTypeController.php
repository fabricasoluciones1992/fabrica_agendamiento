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
                'message' => 'System does not have reservations.'
            ], 400);
        }else{
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservation_types ",4,1,1);
            return response()->json([
                'status'=> True,
                'data' => $reservationTypes
            ],200);
        }
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
            'res_typ_name' => ['required', 'regex:/^[A-Z ]+$/']
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
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una inserción en la tabla reservation_types ",3,1,1);
            return response()->json([
                'status' => True,
                'message' => 'Reservation type '.$reservationTypes->res_typ_name.' created successfully.'
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
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservation_types ",4,1,1);
            return response()->json([
                'status' => True,
                'data'=> $reservationType
            ],200);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ReservationType  $reservationTypes
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'res_typ_name' => ['required', 'regex:/^[A-Z ]+$/']
        ];
        $validator = Validator::make($request->input(), $rules);
        if($validator->fails()){
            return response()->json([
              'status' => False,
              'message' => $validator->errors()->all()
            ],400);
        }else{
            $reservationTypes = ReservationType::find($id);
            $reservationTypes->res_typ_name = $request->res_typ_name;
            $reservationTypes->save();
            Controller::NewRegisterTrigger("Se realizó una actualización en la tabla reservation_types ",1,1,1);
            return response()->json([

                'status' => True,
                'message' => 'Reservation type '.$reservationTypes->res_typ_name.' modified successfully.'
            ],200);
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
        Controller::NewRegisterTrigger("Se intentó destruir un dato en la tabla reservation_types ",2,1,1);

        return response()->json([
            'message' => 'This function is not allowed.'
        ],400);
    }
}
