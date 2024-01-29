<?php

namespace App\Http\Controllers;

use App\Models\Space;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $spaces = Space::all();
        if($spaces == null)
        {
            return response()->json([
             'status' => False,
             'message' => 'There is no spaces availables.'
            ], 400);
        }else{
            return response()->json($spaces);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules =[
            'spa_name' => 'required|string|min:1|max:100',
            'spa_status' => 'required|numeric'
        ];
        $validator = Validator::make($request->input(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => False,
                'message' => $validator->errors()->all()
            ], 400);
        }else{
            $space = new Space($request->input());
            $space->spa_name = $request->spa_name;
            $space ->save();
            return response()->json([
                'status' => True,
                'message' => 'space created successfully',
            ], 200);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Space  $space
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $space = Space::find($id);
        if($space == null)
        {
            return response()->json([
                'status' => False,
                'message' => 'This space does not exist.'
            ], 400);
        }else{
            return $space;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Space  $space
     * @return \Illuminate\Http\Response
     */
    public function edit(Space $space)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Space  $space
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rules =[
            'spa_name' => 'required|string|min:1|max:100',
            'spa_status' => 'required|numeric'
        ];
        $validator = Validator::make($request->input(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => False,
                'message' => $validator->errors()->all()
            ], 400);
        }else{
            $space = Space::find($id);
            $space->spa_name = $request->spa_name;
            $space->spa_status = $request->spa_status;
            $space->save();
            return response()->json([
                'status' => True,
                'message' => 'space modified successfully',
            ], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Space  $space
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $desactivate = Space::find($id);
        ($desactivate->spa_status == 1)?$desactivate->spa_status=0:$desactivate->spa_status=1;
        $desactivate->save();
        return response()->json([
            "message" => "Status changed successfully"
        ], 200);
    }

}
