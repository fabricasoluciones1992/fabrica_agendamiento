<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request){
        $rules = [
            'name' => 'required|string|min:1|max:100',
            'email' => 'required|min:1|max:100|email',
            'password' => 'required|string|min:1|max:100',
        ];

        $validator = Validator::make($request->input(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => False,
                'message' => $validator->errors()->all()
            ],400);
        }else{
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'status' => False,
                    'message' => "Unauthenticated"
                ],400);
            }else{
            $user = DB::table('users')->where('email', '=', $request->email)->first();
            $user = User::find($user->id);
            return response()->json([
                'status' => True,
                'message' => "User login successfully",
                'token' => $user->createToken('API TOKEN')->plainTextToken
            ],200);
        }
        }
    }

    public function register(Request $request){
        $rules = [
            'name' => 'required|string|min:1|max:10',
            'email' => 'required|string|min:1|max:100|unique:users|email',
            'password' => 'required|string|min:1|max:100',
        ];

        $validator = Validator::make($request->input(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => False,
                'message' => $validator->errors()->all()
            ],400);
        }else{
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);
            return response()->json([
                'status' => True,
                'message' => "User created successfully",
                'token' => $user->createToken('API TOKEN')->plainTextToken
            ],200);
        }
    }

    public function logout() {
        
    }
}
