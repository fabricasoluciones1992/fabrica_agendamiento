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

    // function auth(){
    //     session_start();
    //     if (isset($_SESSION['api_token'])) {
    //         $token = $_SESSION['api_token'];
    //         $use_id = $_SESSION['use_id'];
    //         $responseData['acc_administrator']=$_SESSION['acc_administrator'];
 
    //         return [
    //             "token" => $token,
    //             "use_id" => $use_id,
    //             "acc_administrator" => $responseData
    //         ];
    //     } else {
    //         return  'Token not found in session';
    //     }
    // }
 
     public function genders($token) {
        if ($token == "Token not found in session") {
            return $token;
        }else{
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get('http://127.0.0.1:8088/api/projects');
 
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
 
    public function gender($id, $token) {
        // $token = Controller::auth();
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
}