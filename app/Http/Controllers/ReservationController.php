<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationController extends Controller
{

    public function index($proj_id, $use_id)
    {
        $reservations = Reservation::Select();

        if ($reservations == null)
        {
            return response()->json([
             'status' => False,
             'message' => 'No se encontraron reservas'
            ], 400);
        }else{
        Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,$proj_id, $use_id);
        return response()->json([
            'status'=> True,
            'data'=> $reservations
        ], 200);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($proj_id, $use_id, Request $request)
    {
        return Reservation::Store($proj_id, $use_id, $request);
    }

    public function show($proj_id, $use_id, $id)
    {
       $reservation = Reservation::Show($id);

        if ($reservation == null)
        {
            return response()->json(['status' => False, 'message' => 'No existe la reserva.'],400);
        }else{
        Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations.",4,$proj_id, $use_id);
            return response()->json(['status' => True, 'data' => $reservation],200);
        }
    }
    public function update($proj_id, $use_id, Request $request, $id)
    {
        $reservations = Reservation::Amend($proj_id, $use_id, $request, $id);
        return $reservations;
    }
    public function destroy($proj_id, $use_id, $id){
             $desactivate = Reservation::find($id);
            ($desactivate->res_status == 1)?$desactivate->res_status=0:$desactivate->res_status=1;
            $desactivate->save();
            $message = ($desactivate->res_status == 1)?'Desactivado':'Activado';
            Controller::NewRegisterTrigger("Se cambio el estado de una reserva en la tabla reservations ",2,$proj_id,$use_id);
            return response()->json([
                'message' => ''.$message.' exitosamente.',
                'data' => $desactivate
            ],200);
    }
    public function reserFilters($proj_id, $use_id, $data, $column){
        // return $column;
        $reservation = Reservation::ReserFilters($column, $data);
        if ($reservation == null){
            return response()->json([
                'status' => False,
                'message' => 'No se han hecho reservaciones'
            ],400);
        }else{
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,$proj_id, $use_id);
            return response()->json([
                'status' => True,
                'data' => $reservation
            ],200);
        }
    }

    public function activeReservUser($column, $proj_id, $use_id, Request $request){
        $reservation = Reservation::ActiveReservUser($column, $use_id, $request);
        if ($reservation == null){
            return response()->json([
                'status' => False,
                'message' => 'No se han hecho reservaciones'
            ],400);
        }else{
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ",4,$proj_id, $use_id);
            return response()->json([
                'status' => True,
                'data' => $reservation
            ],200);
        }
    }
    public function users(Request $request){
        if ($request->acc_administrator == 1){
            $users  = Reservation::Users();
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
