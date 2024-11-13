<?php

namespace App\Http\Controllers;

use App\Models\MUser;
use Illuminate\Http\Request;



class MUserController extends Controller
{
    public function get_all_users(){
      $users= MUser:: all();
      return response()->json($users);
    }

    public function getuser($id){
        $user= MUser:: find($id);
        if (!$user) {
            return response()->json([
                "message" => "User not found",
                "status" => 404,
            ]);
        }
        return response()->json($user);
      }

    public function create_user(Request $request){
        $newuser = MUser:: create($request->all());
        // $name = $request->name;
        // $email = $request->email;
        // $r->message = "User Created successfuly";
        // $r->status = 200;
        return response()->json($newuser);
    
    }

    public function update_user(Request $request ,$id){
        $newuser = MUser:: find($id);
        if (!$newuser) {
            return response()->json([
                "message" => "User not found",
                "status" => 404,
            ]);
        }
        $newuser->update($request->all());
        $response = [
            "message"=> "updated successfuly",
            "status"=>200,
            "data"=>$newuser
        ];
    return response()->json($response);
    }

    public function delete_user($id){
        $newuser = MUser:: find($id);
        $newuser->delete();
        $response = [
            "message"=> "delete successfuly",
            "status"=>200,
            "data"=>$newuser
        ];
    return response()->json($response);
    }
}
