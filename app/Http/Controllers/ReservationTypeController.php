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
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservation_types ",4,env('APP_ID'),1);
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
        $token = Controller::auth();
        if ($_SESSION['acc_administrator'] == 1) {
            $token = Controller::auth();
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
                Controller::NewRegisterTrigger("Se realizó una inserción en la tabla reservation_types ",3,env('APP_ID'),$token['use_id']);
                return response()->json([
                    'status' => True,
                    'message' => 'Reservation type '.$reservationTypes->res_typ_name.' created successfully.'
                ], 200);
            }
        }else{
            return response()->json([
                'status' => False,
            'message' => 'Access denied. This action can only be performed by active administrators.'
            ],403);
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
        $token = Controller::auth();
        $reservationType = ReservationType::find($id);
        if($reservationType == null){
            return response()->json([
                'status' => False,
                'message' => 'This space does not exist.'
            ], 400);
        }else{
            Controller::NewRegisterTrigger("Se realizó una busqueda de un dato específico en la tabla reservation_types ",4,env('APP_ID'),1);
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
        $token = Controller::auth();
        if ($_SESSION['acc_administrator'] == 1) {
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
                Controller::NewRegisterTrigger("Se realizó una actualización en la tabla reservation_types ",1,env('APP_ID'),$token['use_id']);
                return response()->json([
    
                    'status' => True,
                    'message' => 'Reservation type '.$reservationTypes->res_typ_name.' modified successfully.'
                ],200);
            }
        }else{
            return response()->json([
                'status' => False,
                'message' => 'Access denied. This action can only be performed by active administrators.'
                ],403);
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
        Controller::NewRegisterTrigger("Se intentó destruir un dato en la tabla reservation_types ",2,env('APP_ID'),1);
        return response()->json([
            'message' => 'This function is not allowed.'
        ],400);
    }
}
