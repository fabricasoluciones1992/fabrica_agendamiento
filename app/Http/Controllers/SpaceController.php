<?php

namespace App\Http\Controllers;

use App\Models\Space;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($proj_id, $use_id)
    {
            $spaces = Space::all();
            if($spaces == null){
                return response()->json([
                'status' => False,
                'message' => 'There is no spaces availables.'
                ],400);
            }else{
                Controller::NewRegisterTrigger("Se realizó una busqueda de datos en la tabla spaces ",4,$proj_id,$use_id);

                return response()->json([
                    'status'=>True,
                    'data'=>$spaces],200);
            }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($proj_id, $use_id, Request $request )
    {   
        

            if ($request->acc_administrator == 1) {
            // Se establecen los parametros para ingresar datos. 
            $rules =[
                'spa_name' => ['required', 'regex:/^[A-ZÁÉÍÓÚÜÑ\s]+$/']
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
                $space = new Space($request->input());
                $space->spa_name = $request->spa_name;
                $space->spa_status = 1;
                $space ->save();
                // Se guarda la novedad en la base de datos.
                Controller::NewRegisterTrigger("Se realizó una inserción de un dato en la tabla spaces ",3,$proj_id,$use_id);
                return response()->json([
                    'status' => True,
                    'message' => 'space '.$space->spa_name.' created successfully',
                    'data' => $space
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
     * Display the specified resource.
     *
     * @param  \App\Models\Space  $space
     * @return \Illuminate\Http\Response
     */
    public function show($proj_id, $use_id, $id)
    {
        
        // Se busca el id que se pasa por URL en la tabla de la base de datos
        $space = Space::find($id);
        if($space == null){
            return response()->json([
                'status' => False,
                'message' => 'This space does not exist.'
            ],400);
        }else{
            // Se guarda la novedad en la base de datos.
            Controller::NewRegisterTrigger("Se realizó una busqueda de un dato específico en la tabla spaces.",4,$proj_id,$use_id);
            return response()->json([
            'status' => True,
            'data' => $space
            ],200);
        }
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Space  $space
     * @return \Illuminate\Http\Response
     */
    public function update($proj_id, $use_id, Request $request, $id )
    {
        if ($request->acc_administrator == 1) {               
            // Se establecen los parametros para ingresar datos. 
            $rules =[
                'spa_name' => ['required', 'regex:/^[A-ZÁÉÍÓÚÜÑ\s]+$/'],
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
                $space = Space::find($id);
                $space->spa_name = $request->spa_name;
                $space->save();
                // Se guarda la novedad en la base de datos.
                Controller::NewRegisterTrigger("Se realizó una actualización en la información del dato ".$request->spa_name." de la tabla spaces ",1,$proj_id,$use_id);
                return response()->json([
                    'status' => True,
                    'message' => 'space '.$space->spa_name.' modified successfully',
                    'data'=> $space
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
     * @param  \App\Models\Space  $space
     * @return \Illuminate\Http\Response
     */
    public function destroy( $proj_id, $use_id, $id, Request $request)
    {
        if($request->acc_administrator == 1){
            $desactivate = Space::find($id);
            ($desactivate->spa_status == 1)?$desactivate->spa_status=0:$desactivate->spa_status=1;
            $desactivate->save();
            Controller::NewRegisterTrigger("Se cambio el estado de un dato en la tabla spaces ",2,$proj_id,$use_id);
            return response()->json([
                'message' => 'Status of '.$desactivate->spa_name.' changed successfully.',
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