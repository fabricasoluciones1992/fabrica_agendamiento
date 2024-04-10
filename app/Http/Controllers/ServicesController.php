<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServicesController extends Controller
{

    public function index($proj_id, $use_id)
    {
        $services = Service::Select();

        if ($services == null)
        {
            return response()->json([
             'status' => False,
             'message' => 'No se encontraron servicios'
            ], 400);
        }else{
        Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla services ",4,$proj_id, $use_id);
        return response()->json([
            'status'=> True,
            'data'=> $services
        ], 200);
        }
    }


    public function create()
    {
        //
    }


    public function store(Request $request,$proj_id, $use_id)
    {
        $rules = [
            'ser_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
            'ser_start' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'ser_end' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'ser_typ_id' => 'required|integer',
            'prof_id' => 'required',
            'use_id' => 'required'

        ];
        $messages = [
            'ser_date.required' => 'La fecha del servicio es requerida.',
            'ser_date.regex' => 'El formato de la fecha del servicio no es valido.',
            'ser_start.required' => 'La hora inicial del servicio es requerida.',
            'ser_start.regex' => 'El formato de la hora inicial del servicio no es valido.',
            'ser_end.required' => 'La hora final del servicio es requerida.',
            'ser_end.regex' => 'El formato de la hora final del servicio no es valido.',
            'ser_typ_id.required' => 'El tipo de servicio es requerido.',
            'ser_typ_id.integer' => 'El tipo de servicio no es valido.',
            'prof_id.required' => 'El profesional del servicio es requerido.',
            'use_id.required' => 'El usuario que realiza la reserva es requerido.'
        ];

        $validator = Validator::make($request->input(), $rules, $messages);
        if($validator->fails())
        {
            return response()->json([
              'status' => False,
              'message' => $validator->errors()->all()
            ],400);
        }else{
        return Service::Store($proj_id, $use_id, $request);
        }
    }


    public function show($proj_id, $use_id, $id)
    {
        $service = Service::FindOne($id);

        if ($service == null)
        {
            return response()->json([
             'status' => False,
             'message' => 'No se encontraron servicios'
            ], 400);
        }else{
        Controller::NewRegisterTrigger("Se realizó la busqueda de un dato en la tabla services ",4,$proj_id, $use_id);
        return response()->json([
            'status'=> True,
            'data'=> $service
        ], 200);
        }
    }



    public function update($proj_id, $use_id, Request $request, $id)
    {
        $rules = [
            'ser_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
            'ser_start' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'ser_end' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'ser_typ_id' => 'required|integer',
            'prof_id' => 'required|integer',
            'use_id' => 'required|integer'

        ];

        $messages = [
            'ser_date.required' => 'La fecha de la reserva es requerida.',
            'ser_date.regex' => 'El formato de la fecha de la reserva no es valido.',
            'ser_start.required' => 'La hora inicial de la reserva es requerida.',
            'ser_start.regex' => 'El formato de la hora inicial de la reserva no es valido.',
            'ser_end.required' => 'La hora final de la reserva es requerida.',
            'ser_end.regex' => 'El formato de la hora final de la reserva no es valido.',
            'ser_typ_id.required' => 'El tipo de reserva es requerido.',
            'ser_typ_id.integer' => 'El tipo de reserva no es valido.',
            'prof_id.required' => 'El profesional a reservar es requerido.',
            'use_id.required' => 'El usuario que realiza la reserva es requerido.'
        ];

        $validator = Validator::make($request->input(), $rules, $messages);
        if($validator->fails()){
            return response()->json([
                'status' => False,
                'message' => $validator->errors()->all()
            ],400);
        }else{
            return Service::Amend($proj_id, $use_id, $request, $id);
        }

    }


    public function destroy($proj_id, $use_id, $id){
        $desactivate = Service::find($id);
       ($desactivate->ser_status == 1)?$desactivate->ser_status=0:$desactivate->ser_status=1;
       $desactivate->save();
       $message = ($desactivate->ser_status == 1)?'Activado':'Desactivado';
       Controller::NewRegisterTrigger("Se cambio el estado de un servicio en la tabla services ",2,$proj_id,$use_id);
       return response()->json([
           'message' => ''.$message.' exitosamente.',
           'data' => $desactivate
       ],200);
}
public function reserFilters($proj_id, $use_id, $column, $data){
   // return $column;
   $services = Service::ReserFilters($column, $data);
   if ($services == null){
       return response()->json([
           'status' => False,
           'message' => 'No se han hecho reservaciones de servicios'
       ],400);
   }else{
       // Control de acciones
       Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla services ",4,$proj_id, $use_id);
       return response()->json([
           'status' => True,
           'data' => $services
       ],200);
   }
}

public function ActiveServiceUser($proj_id, $use_id, Request $request){
   $service = Service::ActiveServiceUser($use_id, $request);
   if ($service == '[]'){
       return response()->json([
           'status' => False,
           'message' => 'No se han hecho reservaciones de servicios'
       ],400);
   }else{
       // Control de acciones
       Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla services ",4,$proj_id, $use_id);
       return response()->json([
           'status' => True,
           'data' => $service
       ],200);
   }
}
public function calendar($proj_id, $use_id){
   $services = Service::Calendar();
   if ($services == null){
       return response()->json([
           'status' => False,
           'message' => 'No se han hecho reservaciones de servicios'
       ],400);
   }else{
       // Control de acciones
       Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla services ",4,$proj_id, $use_id);
       return response()->json([
           'status' => True,
           'data' => $services
       ],200);
   }
}
public function users(Request $request){
   if ($request->acc_administrator == 1){
       $users  = Service::Users();
       if($users != null){
           return response()->json([
               'status' => True,
               'data' => $users
               ],200);
       }else{
           return response()->json([
               'status' => False,
               'message' => 'No se han registrado usuarios'
           ],400);
       }
   }else{
       return response()->json([
           'status' => False,
           'message' => 'Acceso denegado'
       ],400);
   }
}
    public function betweenDates($proj_id, $use_id, $startDate, $endDate){
        $services = Service::betweenDates($startDate, $endDate);
        if ($services == null)
        {
            return response()->json([
             'status' => False,
             'message' => 'No se encontraron servicios'
            ], 400);
        }else{
        Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla services ",4,$proj_id, $use_id);
        return response()->json([
            'status'=> True,
            'data'=> $services
        ], 200);
        }
    }
}

