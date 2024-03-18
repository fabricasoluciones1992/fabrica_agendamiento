<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

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
        return Service::Store($proj_id, $use_id, $request);
    }


    public function show($proj_id, $use_id, $id)
    {
        $services = Service::Show($id);

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



    public function update($proj_id, $use_id, Request $request, $id)
    {
        $reservations = Service::Amend($proj_id, $use_id, $request, $id);
        return $reservations;
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
   $reservation = Service::ReserFilters($column, $data);
   if ($reservation == null){
       return response()->json([
           'status' => False,
           'message' => 'No se han hecho reservaciones de servicios'
       ],400);
   }else{
       // Control de acciones
       Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla services ",4,$proj_id, $use_id);
       return response()->json([
           'status' => True,
           'data' => $reservation
       ],200);
   }
}

public function activeReservUser($proj_id, $use_id, Request $request){
   $reservation = Service::ActiveReservUser($use_id, $request);
   if ($reservation == null){
       return response()->json([
           'status' => False,
           'message' => 'No se han hecho reservaciones de servicios'
       ],400);
   }else{
       // Control de acciones
       Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla services ",4,$proj_id, $use_id);
       return response()->json([
           'status' => True,
           'data' => $reservation
       ],200);
   }
}
public function calendar($proj_id, $use_id){
   $reservation = Service::Calendar();
   if ($reservation == null){
       return response()->json([
           'status' => False,
           'message' => 'No se han hecho reservaciones de servicios'
       ],400);
   }else{
       // Control de acciones
       Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla services ",4,$proj_id, $use_id);
       return response()->json([
           'status' => True,
           'data' => $reservation
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
}

