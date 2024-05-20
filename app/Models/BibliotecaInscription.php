<?php

namespace App\Models;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class BibliotecaInscription extends Model
{
    use HasFactory;
    protected $table = 'biblioteca_inscriptions';
    protected $primaryKey = 'bio_ins_id';

    protected $fillable = [
        'bio_ins_date',
        'bio_ins_status',
        'stu_id',
        'ser_id'
    ];

    public $timestamps = false;



    public static function make(Request $request, $proj_id, $use_id)
    {
        $date = date('Y-m-d');

        // Se establecen los parametros para ingresar datos.
        $rules = [
            'bio_ins_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
            'stu_id' => ['required'],
            'ser_id' => ['required']

        ];
        // El sistema valida que estos datos sean correctos
        if ($date <= $request->bio_ins_date) {

            $validator = Validator::make($request->input(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => False,
                    'message' => $validator->errors()->all()
                ], 400);
            } else {
                // Si los datos son correctos se procede a guardar los datos en la base de datos.
                $service =  Service::find($request->ser_id);
                $student =  DB::table('students AS st')->where('st.stu_id', '=', $request->stu_id)->first();
                if ($service == null || $student == null) {
                    $message = ($service == null) ? 'El servicio no existe.' : ' El estudiante no existe.';
                    return response()->json([
                        'status' => False,
                        'message' => $message
                    ], 400);
                }
                $biblioteca = new BibliotecaInscription($request->input());
                $biblioteca->bio_ins_status = 1;
                $quotas = Service::substractQuote($request);
                if ($quotas == true) {
                    $biblioteca->save();
                    // Se guarda la novedad en la base de datos.
                    Controller::NewRegisterTrigger("Se realizó una busqueda de datos en la tabla biblioteca_Inscriptions ", 4, $proj_id, $use_id);
                    return response()->json([
                        'status' => True,
                        'message' => 'Inscrito correctamente.',
                        'data' => $biblioteca
                    ], 200);
                } else {
                    return response()->json([
                        'status' => False,
                        'message' => 'No ha sido posible crear la inscripción.'
                    ], 400);
                }
            }
        } else {
            return response()->json([
                'status' => False,
                'message' => 'Fecha invalida.'
            ], 400);
        }
    }

    public static function Amend(Request $request, $proj_id, $use_id, $id)
    {
        $date = date('Y-m-d');


        // Se establecen los parametros para ingresar datos.
        $rules = [
            'bio_ins_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
            'stu_id' => ['required'],
            'ser_id' => ['required']

        ];
        // El sistema valida que estos datos sean correctos

        if ($date <= $request->bio_ins_date) {


            $validator = Validator::make($request->input(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => False,
                    'message' => $validator->errors()->all()
                ], 400);
            } else {

                $biblioteca =  BibliotecaInscription::find($id);
                $service =  Service::find($request->ser_id);
                $student =  DB::table('students AS st')->where('st.stu_id', '=', $request->stu_id)->first();
                if ($biblioteca == null || $service == null || $student == null) {
                    $message = ($biblioteca == null) ? 'La inscripción no existe.' : ($service == null ? 'El servicio no existe.' : ' El estudiante no existe.');
                    return response()->json([
                        'status' => False,
                        'message' => $message
                    ], 400);
                } else {
                    if ($biblioteca->ser_id == $request->ser_id) {
                        // El usuario solo podrá cambiar el tipo de servicio
                        $biblioteca->ser_id = $request->ser_id;
                        $biblioteca->save();
                        Controller::NewRegisterTrigger("Se realizó una actualizacion de datos en la tabla biblioteca_Inscriptions ", 4, $proj_id, $use_id);
                        return response()->json([
                            'status' => True,
                            'message' => 'La inscripción se actualizó correctamente.',
                            'data' => $biblioteca
                        ], 200);
                    } else {

                        $quotas = Service::substractQuote($request);
                        // return $quotas;
                        if ($quotas == false) {
                            return response()->json([
                                'status' => False,
                                'message' => 'No hay cupos disponibles en este servicio.'
                            ]);
                        } else {

                            // Se actualizará la fecha en el día que se genere el cambio.
                            $biblioteca->bio_ins_date = $date;
                            $biblioteca->stu_id = $request->stu_id;
                            $biblioteca->ser_id = $request->ser_id;
                            $biblioteca->save();
                            // Se guarda la novedad en la base de datos.
                            Controller::NewRegisterTrigger("Se realizó una actualizacion de datos en la tabla biblioteca_Inscriptions ", 4, $proj_id, $use_id);
                            return response()->json([
                                'status' => True,
                                'message' => 'La inscripción se actualizó correctamente.',
                                'data' => $biblioteca
                            ], 200);
                        }
                    }
                }
            }
        } else {
            return response()->json([
                'status' => False,
                'message' => 'Fecha invalida.'
            ], 400);
        }
    }
}
