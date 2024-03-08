<?php

namespace App\Http\Controllers;

use App\Models\ReservationType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ReservationTypeController extends Controller
{

    public function index($proj_id, $use_id)
    {

        $reservationTypes = ReservationType::all();
        if($reservationTypes == null){
            return response()->json([
                'status' => False,
                'message' => 'System does not have reservations.'
            ], 400);
        }else{
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservation_types ",4, $proj_id, $use_id);
            return response()->json([
                'status'=> True,
                'data' => $reservationTypes
            ],200);
        }
    }


    public function store($proj_id, $use_id, Request $request)
    {
        if($request->acc_administrator == 1){
            $rules = [
                'res_typ_name' => ['required','unique:reservation_types', 'regex:/^[A-ZÁÉÍÓÚÜÑ\s]+$/']
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
                Controller::NewRegisterTrigger("Se realizó una inserción en la tabla reservation_types ",3,$proj_id,$use_id);
                return response()->json([
                    'status' => True,
                    'message' => 'Reservation type '.$reservationTypes->res_typ_name.' created successfully.',
                ], 200);
            }
        }else{
            return response()->json([
                'status' => False,
            'message' => 'Access denied. This action can only be performed by active administrators.'
            ],403);
        }
    }


    public function show($proj_id, $use_id, $id)
    {
        $reservationType = ReservationType::find($id);
        if($reservationType == null){
            return response()->json([
                'status' => False,
                'message' => 'This space does not exist.'
            ], 400);
        }else{
            Controller::NewRegisterTrigger("Se realizó una busqueda de un dato específico en la tabla reservation_types ",4,$proj_id,$use_id);
            return response()->json([
                'status' => True,
                'data'=> $reservationType
            ],200);
        }
    }


    public function update($proj_id, $use_id, Request $request, $id)
    {

        if ($request->acc_administrator == 1) {
            $rules = [
                'res_typ_name' => ['required', 'unique:reservation_types', 'regex:/^[A-ZÁÉÍÓÚÜÑ\s]+$/']
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
                Controller::NewRegisterTrigger("Se realizó una actualización en la tabla reservation_types ",1,$proj_id, $use_id);
                return response()->json([

                    'status' => True,
                    'message' => 'Reservation type '.$reservationTypes->res_typ_name.' modified successfully.',
                    'data'=>$reservationTypes
                ],200);
            }
        }else{
            return response()->json([
                'status' => False,
                'message' => 'Access denied. This action can only be performed by active administrators.'
                ],403);
        }
    }


    public function destroy($proj_id, $use_id, ReservationType $reservationType)
    {
        Controller::NewRegisterTrigger("Se intentó destruir un dato en la tabla reservation_types ",2,$proj_id, $use_id);
        return response()->json([
            'message' => 'This function is not allowed.'
        ],400);
    }
}
