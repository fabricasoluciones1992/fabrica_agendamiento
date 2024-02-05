<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Http\Request;
 
 
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
 
    public function NewRegisterTrigger($new_description,$new_typ_id, $proj_id, $use_id)
    {
        DB::statement("CALL new_register('" . addslashes($new_description) . "', $new_typ_id, $proj_id, $use_id)");
    }
   
     public function genders($token) {
        if ($token == "Token not found in session") {
            return $token;
        }else{
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get('http://127.0.0.1:8088/api/genders');
 
            if ($response->successful()) {
                return response()->json([
                    'data' => $response->json()
                ],200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'HTTP request failed'.$response
                ],400);
            }
        }
    }
 
    public function gender($id) {
        $token = Controller::auth();
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('http://127.0.0.1:8088/api/genders/'.$id);
 
        if ($response->successful()) {
            return response()->json([
                'status' => true,
                'data' => $response->json()
            ],200);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'HTTP request failed'
            ],400);
        }
    }
 
 
    function auth(){
        session_start();
        if (isset($_SESSION['api_token'])) {
            $token = $_SESSION['api_token'];
            $use_id = $_SESSION['use_id'];
            return [
                "token" => $token,
                "use_id" => $use_id
            ];
        } else {
            return  'Token not found in session';
        }
    }
 
    public function login(Request $request){
 
        $response = Http::post("http://127.0.0.1:8088/api/login", [
            "use_mail" => $request->use_mail,
            "use_password" => $request->use_password,
            "proj_id" => env('APP_ID')
        ]);
        $user=DB::table('users')->where("use_mail",'=',$request->use_mail)->first();
        $user = User::find($user->use_id);
        Auth::login($user);
       
        // Verifica si la solicitud HTTP fue exitosa
        if ($response->successful()) {
            // Obtener el token de la respuesta JSON, si está presente
            $responseData = $response->json();
            $token = isset($responseData['token']) ? $responseData['token'] : null;
   
            // Verifica si se obtuvo un token antes de almacenarlo
            if ($token !== null) {
                // Iniciar la sesión y almacenar el token
                session_start();
                $_SESSION['api_token'] = $token;
                $_SESSION['use_id'] = $user->use_id;
   
                return response()->json([
                    'status' => true,
                    'data' => [
                        "token" => $token,
                        "use_id" => $user->use_id
                    ]
                ]);
            } else {
                // Manejar el caso en el que 'token' no está presente en la respuesta
                return response()->json([
                    'status' => false,
                    'message' => 'Token not found in the response'
                ]);
            }
        } else {
            // Manejar el caso en el que la solicitud HTTP no fue exitosa
            return response()->json([
                'status' => false,
                'message' => 'HTTP request failed'
            ]);
        }
 
    }
    public function logout(Request $id) {
        session_start();
        $tokens = DB::table('personal_access_tokens')->where('tokenable_id', '=', $id->use_id)->delete();
        session_destroy();
        return response()->json([
            'status'=> true,
            'message'=> "logout success."
        ]);
    }
}