<?php

namespace App\Http\Controllers;

use App\Models\BibliotecaInscription;
use App\Models\Service;
use Illuminate\Http\Request;

class BibliotecaInscriptionController extends Controller
{

    public function index($proj_id, $use_id)
    {
        $bilioteca = BibliotecaInscription::select();
        if($bilioteca == null){
            return response()->json([
            'status' => False,
            'message' => 'No hay inscripciones disponibles.'
            ],400);
        }else{
            Controller::NewRegisterTrigger("Se realizó una busqueda de datos en la tabla biblioteca_Inscriptions ",4,$proj_id,$use_id);
            return response()->json([
                'status'=>True,
                'data'=>$bilioteca],200);
        }

    }

    public function store(Request $request, $proj_id, $use_id)
    {
        return BibliotecaInscription::make($request, $proj_id, $use_id);


    }


    public function show($proj_id, $use_id, $id)
    {
        $bilioteca = BibliotecaInscription::findOne($id);
        if($bilioteca == null){
            return response()->json([
            'status' => False,
            'message' => 'La enscripción no existe.'
            ],400);
        }else{
            Controller::NewRegisterTrigger("Se realizó una busqueda de datos en la tabla biblioteca_Inscriptions ",4,$proj_id,$use_id);
            return response()->json([
                'status'=>True,
                'data'=>$bilioteca],200);
        }
    }


    public function update(Request $request, $proj_id, $use_id, $id)
    {
        return BibliotecaInscription::Amend($request, $proj_id, $use_id, $id);

    }

    public function destroy(Request $request, $proj_id, $use_id, $id)
    {
        $desactivate = bibliotecaInscription::find($id);

        $quotas = Service::substractQuote($request);
        if ($quotas == true || $desactivate->bio_ins_status == 1){
            ($desactivate->bio_ins_status == 1)
            ? $desactivate->bio_ins_status=0
            : $desactivate->bio_ins_status=1;
            $desactivate->save();

            $message = ($desactivate->bio_ins_status == 1)?'Activado':'Desactivado';
            Controller::NewRegisterTrigger("Se cambio el estado de una inscripción en la tabla biblioteca_inscriptions ",2,$proj_id,$use_id);
            return response()->json([
                'status' => True,
                'message' => ''.$message.' exitosamente.',
                'data' => $desactivate
            ],200);
        }else{
            return response()->json([
               'status' => False,
               'message' => 'No se puede activar la inscripción, ya que no hay suficientes cupos disponibles.'
            ]);
        }





    }
}
