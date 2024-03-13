<?php

namespace App\Http\Controllers;

use App\Models\Profesional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfesionalsController extends Controller
{

    public function index($proj_id,$use_id)
    {
        $profesional = Profesional::all();
        if($profesional == null){
            return response()->json([
            'status' => False,
            'message' => 'There is no profesionals availables.'
            ],400);
        }else{
            Controller::NewRegisterTrigger("Se realizó una busqueda de datos en la tabla profesionals ",4,$proj_id,$use_id);

            return response()->json([
                'status'=>True,
                'data'=>$profesional],200);
        }
    }

    public function store(Request $request, $proj_id,$use_id)
    {

        if ($request->acc_administrator == 1) {
            // Se establecen los parametros para ingresar datos.
            $rules =[
                'prof_name' => ['required','unique:profesionals', 'regex:/^[a-zA-ZÁÉÍÓÚÜÑ\s]+$/']
            ];
            // El sistema valida que estos datos sean correctos
            $validator = Validator::make($request->input(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => False,
                    'message' => $validator->errors()->all()
                ],400);
            }else{
                // Si los datos son correctos se procede a guardar los datos en la base de datos.
                $profesional = new Profesional($request->input());
                $profesional->prof_name = $request->prof_name;
                $profesional->prof_status = 1;
                $profesional ->save();
                // Se guarda la novedad en la base de datos.
                Controller::NewRegisterTrigger("Se realizó una inserción de un dato en la tabla profesionals ",3,$proj_id,$use_id);
                return response()->json([
                    'status' => True,
                    'message' => 'Profe '.$profesional->prof_name.' created successfully',
                    'data' => $profesional
                ],200);
            }
        }else{
            return response()->json([
               'status' => False,
               'message' => 'Access denied. This action can only be performed by active administrators.'
                ],403);
        }
    }


    public function show($proj_id,$use_id, $id)
    {
        $profesional = Profesional::find($id);
        if($profesional == null){
            return response()->json([
                'status' => False,
                'message' => 'This profesional does not exist.'
            ],400);
        }else{
            // Se guarda la novedad en la base de datos.
            Controller::NewRegisterTrigger("Se realizó una busqueda de un dato específico en la tabla profesionals.",4,$proj_id,$use_id);
            return response()->json([
            'status' => True,
            'data' => $profesional
            ],200);
        }
    }

    public function update(Request $request, $proj_id,$use_id,$id)
    {
        if ($request->acc_administrator == 1) {
            // Se establecen los parametros para ingresar datos.
            $rules =[
                'prof_name' => ['required', 'unique:profesionals', 'regex:/^[a-zA-ZÁÉÍÓÚÜÑ\s]+$/'],
            ];
            // El sistema valida que estos datos sean correctos
            $validator = Validator::make($request->input(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => False,
                    'message' => $validator->errors()->all()
                ],400);
            }else{
                // Se busca el dato en la base de datos.
                $profesional = Profesional::find($id);
                $profesional->prof_name = $request->prof_name;
                $profesional->save();
                // Se guarda la novedad en la base de datos.
                Controller::NewRegisterTrigger("Se realizó una actualización en la información del dato ".$request->prof_name." de la tabla profesionals ",1,$proj_id,$use_id);
                return response()->json([
                    'status' => True,
                    'message' => 'space '.$profesional->prof_name.' modified successfully',
                    'data'=> $profesional
                ],200);
            }

        }else{
            return response()->json([
              'status' => False,
              'message' => 'Access denied. This action can only be performed by active administrators.'
                ],403);
        }

    }


    public function destroy(Request $request, $proj_id,$use_id, $id)
    {
        if($request->acc_administrator == 1){
            $desactivate = Profesional::find($id);
            ($desactivate->prof_status == 1)?$desactivate->prof_status=0:$desactivate->prof_status=1;
            $desactivate->save();
            Controller::NewRegisterTrigger("Se cambio el estado de un dato en la tabla profesionals ",2,$proj_id,$use_id);
            return response()->json([
                'message' => 'Status of '.$desactivate->prof_status.' changed successfully.',
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
