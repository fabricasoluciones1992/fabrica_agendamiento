<?php

namespace App\Http\Controllers;

use App\Models\BibliotecaInscription;
use App\Models\Service;
use Illuminate\Http\Request;

class BibliotecaInscriptionController extends Controller
{
// Función para traer todas las inscripciones que existen en la base de datos
    public function index($proj_id, $use_id)
    {
        // Dirige a la función del modelo con el nombre 'select'.
        $bilioteca = BibliotecaInscription::select();
        // Si no hay ninguna inscripción en la base de datos, se retorna un mensaje de error.
        if($bilioteca == null){
            return response()->json([
            'status' => False,
            'message' => 'No hay inscripciones disponibles.'
            ],400);
            // Al encontrar inscripciones el sistema devuelve los datos en forma de respuesta api.
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
            'message' => 'La inscripción no existe.'
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

    public static function actives($proj_id,$use_id, $id){
        $bilioteca = BibliotecaInscription::studentActive($id);
        if($bilioteca == '[]'){
            return response()->json([
            'status' => False,
            'message' => 'La inscripción no existe.'
            ],400);
        }else{
            Controller::NewRegisterTrigger("Se realizó una busqueda de datos en la tabla biblioteca_Inscriptions ",4,$proj_id,$use_id);
            return response()->json([
                'status'=>True,
                'data'=>$bilioteca],200);
        }
    }
}
