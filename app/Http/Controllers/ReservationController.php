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

    // Método para obtener todas las reservas
    public function index($proj_id, $use_id)
    {

        // Selecciona todas las reservas
        $reservations = Reservation::Select();

        // Si no hay reservas, devuelve un mensaje de error
        if ($reservations->isEmpty()) {
            return response()->json([
                'status' => False,
                'message' => 'No se encontraron reservas'
            ], 400);
        } else {
            // Registra un evento de búsqueda en la tabla de reservas
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ", 4, $proj_id, $use_id);
            // Devuelve un JSON con el estado y los datos de las reservas encontradas

            return response()->json([
                'status' => True,
                'data' => $reservations
            ], 200);
        }
    }

    // Método para almacenar una nueva reserva
    public function store($proj_id, $use_id, Request $request)
    {

        // Reglas de validación para los datos de la reserva
        $rules = [
            'res_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
            'res_start' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'res_end' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'spa_id' => 'required|integer',
            'use_id' => 'required|integer'

        ];
        // Mensajes personalizados para las reglas de validación
        $messages = [
            'res_date.required' => 'La fecha de la reserva es requerida.',
            'res_date.regex' => 'El formato de la fecha de la reserva no es valido.',
            'res_start.required' => 'La hora inicial de la reserva es requerida.',
            'res_start.regex' => 'El formato de la hora inicial de la reserva no es valido.',
            'res_end.required' => 'La hora final de la reserva es requerida.',
            'res_end.regex' => 'El formato de la hora final de la reserva no es valido.',
            'spa_id.required' => 'El espacio a reservar es requerido.',
            'use_id.required' => 'El usuario que realiza la reserva es requerido.'
        ];

        // Realiza la validación de los datos de entrada
        $validator = Validator::make($request->input(), $rules, $messages);

        // Si la validación falla, devuelve un mensaje de error
        if ($validator->fails()) {
            return response()->json([
                'status' => False,
                'message' => $validator->errors()->all()
            ], 400);
        } else {

            // Llama al método Store del modelo Reservation para almacenar la reserva
            return Reservation::Store($proj_id, $use_id, $request);
        }
    }

    // Método para mostrar una reserva específica por su ID
    public function show($proj_id, $use_id, $id)
    {

        // Busca la reserva por su ID
        $reservation = Reservation::FindOne($id);

        // Si no existe la reserva, devuelve un mensaje de error

        if ($reservation == null) {
            return response()->json(['status' => False, 'message' => 'No existe la reserva.'], 400);
        } else {

            // Registra un evento de búsqueda específica en la tabla de reservas
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations.", 4, $proj_id, $use_id);

            // Devuelve un JSON con el estado y los datos de la reserva encontrada
            return response()->json(['status' => True, 'data' => $reservation], 200);
        }
    }

    // Método para actualizar una reserva por su ID
    public function update(Request $request, $proj_id, $use_id, $id)
    {

        // Reglas de validación para los datos de la reserva
        $rules = [
            'res_date' => ['required', 'regex:/^(\d{4})(\/|-)(0[1-9]|1[0-2])\2([0-2][0-9]|3[0-1])$/'],
            'res_start' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'res_end' => ['required', 'regex:/^([0-1][0-9]|2[0-3])(:)([0-5][0-9])$/'],
            'spa_id' => 'required|integer',
            'use_id' => 'required|integer'

        ];

        // Mensajes personalizados para las reglas de validación
        $messages = [
            'res_date.required' => 'La fecha de la reserva es requerida.',
            'res_date.regex' => 'El formato de la fecha de la reserva no es valido.',
            'res_start.required' => 'La hora inicial de la reserva es requerida.',
            'res_start.regex' => 'El formato de la hora inicial de la reserva no es valido.',
            'res_end.required' => 'La hora final de la reserva es requerida.',
            'res_end.regex' => 'El formato de la hora final de la reserva no es valido.',
            'spa_id.required' => 'El espacio a reservar es requerido.',
            'use_id.required' => 'El usuario que realiza la reserva es requerido.'
        ];

        // Realiza la validación de los datos de entrada
        $validator = Validator::make($request->input(), $rules, $messages);

        // Si la validación falla, devuelve un mensaje de error
        if ($validator->fails()) {
            return response()->json([
                'status' => False,
                'message' => $validator->errors()->all()
            ], 400);
        } else {

            // Llama al método Amend del modelo Reservation para actualizar la reserva
            return Reservation::Amend($request, $proj_id, $use_id, $id);
        }
    }

    // Método para cambiar el estado (activar o desactivar) de una reserva por su ID
    public function destroy($proj_id, $use_id, $id)
    {
        // Busca la reserva por su ID
        $desactivate = Reservation::find($id);

        // Cambia el estado de la reserva (activa o inactiva)
        ($desactivate->res_status == 1) ? $desactivate->res_status = 0 : $desactivate->res_status = 1;
        $desactivate->save();

        // Mensaje de éxito según el estado cambiado
        $message = ($desactivate->res_status == 1) ? 'Activado' : 'Desactivado';

        // Registra un evento de cambio de estado en la tabla de reservas
        Controller::NewRegisterTrigger("Se cambio el estado de una reserva en la tabla reservations ", 2, $proj_id, $use_id);

        // Devuelve un JSON con el mensaje de éxito y los datos de la reserva actualizada
        return response()->json([
            'message' => '' . $message . ' exitosamente.',
            'data' => $desactivate
        ], 200);
    }

    // Método para filtrar las reservas según un campo específico y su valor
    public function reserFilters($proj_id, $use_id, $column, $data)
    {

        // Llama al método ReserFilters del modelo Reservation para filtrar las reservas
        $reservation = Reservation::ReserFilters($column, $data);

        // Si no hay reservas que coincidan con el filtro, devuelve un mensaje de error
        if ($reservation == null) {
            return response()->json([
                'status' => False,
                'message' => 'No se han hecho reservaciones'
            ], 400);
        } else {

            // Registra un evento de búsqueda en la tabla de reservas
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ", 4, $proj_id, $use_id);

            // Devuelve un JSON con el estado y los datos de las reservas filtradas
            return response()->json([
                'status' => True,
                'data' => $reservation
            ], 200);
        }
    }

    // Método para obtener las reservas activas de un usuario específico
    public function activeReservUser($proj_id, $use_id, Request $request)
    {

        // Llama al método ActiveReservUser del modelo Reservation para obtener las reservas activas del usuario
        $reservation = Reservation::ActiveReservUser($use_id, $request);

        // Si no hay reservas activas para el usuario, devuelve un mensaje de error
        if ($reservation == null) {
            return response()->json([
                'status' => False,
                'message' => 'No se han hecho reservaciones'
            ], 400);
        } else {
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ", 4, $proj_id, $use_id);

            // Retorna los datos en un JSON
            return response()->json([
                'status' => True,
                'data' => $reservation
            ], 200);
        }
    }
    public function calendar($proj_id, $use_id)
    {

        // Llama al método ActiveReservUser del modelo Reservation para obtener las reservas activas del usuario
        $reservation = Reservation::Calendar();
        if ($reservation == null) {

        // Si no hay reservas activas, devuelve un mensaje de error
            return response()->json([
                'status' => False,
                'message' => 'No se han hecho reservaciones.'
            ], 400);
        } else {
            // Control de acciones
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ", 4, $proj_id, $use_id);
            return response()->json([
                'status' => True,
                'data' => $reservation
            ], 200);
        }
    }

    // Función que trae las reservaciones activas de los usuarios
    public function users(Request $request)
    {
        if ($request->acc_administrator == 1) {
            $users = Reservation::Users();
            if ($users != null) {
                return response()->json([
                    'status' => True,
                    'data' => $users
                ], 200);
            } else {
                return response()->json([
                    'status' => False,
                    'message' => 'No se han registrado usuarios'
                ], 400);
            }
        } else {
            return response()->json([
                'status' => False,
                'message' => 'Acceso denegado'
            ], 400);
        }
    }

    // Busca las reservas existentes en la base de datos entre dos fechas
    public function betweenDates($proj_id, $use_id, $startDate, $endDate)
    {
        // en el modelo Reservation se ejecutará la función betweenDates y se le pasaran la fecha de inicio y de fin
        $reservations = Reservation::betweenDates($startDate, $endDate);
        if ($reservations == null) {
            return response()->json([
                'status' => False,
                'message' => 'No se encontraron reservaciones'
            ], 400);
        } else {
            Controller::NewRegisterTrigger("Se realizó una busqueda en la tabla reservations ", 4, $proj_id, $use_id);
            return response()->json([
                'status' => True,
                'data' => $reservations
            ], 200);
        }
    }
}
