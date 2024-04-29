<?php

namespace App\Http\Controllers;

use App\Models\ServiceTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceTypesController extends Controller
{

    public function index( $proj_id, $use_id)
    {
        $serviceTypes = ServiceTypes::all();
        if($serviceTypes == null){
            return response()->json([
                'status' => False,
                'message' => 'No se encontraron servicios'
            ], 400);
        }else{
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla service_types ",4, $proj_id, $use_id);
            return response()->json([
                'status'=> True,
                'data' => $serviceTypes
            ],200);
        }
    }




    public function store(Request $request, $proj_id,$use_id)
    {
        if($request->acc_administrator == 1){
            $rules = [
                'ser_typ_name' => ['required','unique:service_types', 'regex:/^([0-9A-ZÁÉÍÓÚÜÑ\s#¿?!@$ %^&*:-])+$/']
            ];
            $validator = Validator::make($request->input(), $rules);
            if($validator->fails()){
                return response()->json([
                  'status' => False,
                  'message' => $validator->errors()->all()
                ], 400);
            }else{
                $serviceTypes = new ServiceTypes($request->input());
                $serviceTypes->ser_typ_name = $request->ser_typ_name;
                $serviceTypes->ser_typ_status = 1;
                $serviceTypes->save();
                // Control de acciones
                Controller::NewRegisterTrigger("Se realizó una inserción en la tabla service_types ",3,$proj_id,$use_id);
                return response()->json([
                    'status' => True,
                    'message' => 'Reservation type '.$serviceTypes->ser_typ_name.' created successfully.',
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
        $serviceTypes = ServiceTypes::find($id);
        if($serviceTypes == null){
            return response()->json([
                'status' => False,
                'message' => 'This space does not exist.'
            ], 400);
        }else{
            Controller::NewRegisterTrigger("Se realizó una busqueda de un dato específico en la tabla service_types ",4,$proj_id,$use_id);
            return response()->json([
                'status' => True,
                'data'=> $serviceTypes
            ],200);
        }
    }




    public function update(Request $request, $proj_id, $use_id, $id)
    {
        if ($request->acc_administrator == 1) {
            $rules = [
                'ser_typ_name' => ['required', 'unique:service_types', 'regex:/^([0-9A-ZÁÉÍÓÚÜÑ\s#¿?!@$ %^&*:-])+$/']
            ];
            $validator = Validator::make($request->input(), $rules);
            if($validator->fails()){
                return response()->json([
                  'status' => False,
                  'message' => $validator->errors()->all()
                ],400);
            }else{
                $serviceTypes = ServiceTypes::find($id);
                $serviceTypes->ser_typ_name = $request->ser_typ_name;
                $serviceTypes->save();
                Controller::NewRegisterTrigger("Se realizó una actualización en la tabla service_types ",1,$proj_id, $use_id);
                return response()->json([

                    'status' => True,
                    'message' => 'El tipo de servicio: '.$serviceTypes->ser_typ_name.', se modificó exi.',
                    'data'=>$serviceTypes
                ],200);
            }
        }else{
            return response()->json([
                'status' => False,
                'message' => 'Access denied. This action can only be performed by active administrators.'
                ],403);
        }
    }


    public function destroy( $proj_id, $use_id, $id, Request $request)
    {
        if($request->acc_administrator == 1){
            $desactivate = ServiceTypes::find($id);
            ($desactivate->ser_typ_status == 1)?$desactivate->ser_typ_status=0:$desactivate->ser_typ_status=1;
            $desactivate->save();
            Controller::NewRegisterTrigger("Se cambio el estado de un dato en la tabla spaces ",2,$proj_id,$use_id);
            return response()->json([
                'message' => 'Status of '.$desactivate->ser_typ_name.' changed successfully.',
                'data' => $desactivate
            ],200);
        }else{
            return response()->json([
                'status' => False,
                'message' => 'Access denied. This action can only be performed by active administrators.'
            ]);
        }
    }
}
