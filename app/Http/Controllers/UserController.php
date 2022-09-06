<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\User;

class UserController extends Controller
{
    //

     protected function validator(array $request)
    {
          return Validator::make($request, [
            'ip'        => ['string', 'max:255'],
            'country'   => ['string', 'max:255'],
            'user_type' => ['string', 'max:255'],
            'farm_type' => ['string', 'max:255'],
            'service_type' => ['string', 'max:255'],
            'phone'     => ['required', 'string', 'max:255', 'unique:users'],
            'password'  => ['required', 'string', 'min:6', 'confirmed'],
        ]);
    }

    public function createUser(Request $request){
 
        //$user = User::create($request->all());

        //dispaly code in front end, let user enter the code the could see in a input text field
        $reg_code = str_random(6);//generate unique 6 string

        $user = new User();
        $user->ip          = $request['ip']; //hidden input field. auto get the user ip
        $user->country     = $request['country'];  // hidden field. auto get the user country from his ip
        $user->user_type   = $request['user_type']; 
        $user->farm_type   = $request['farm_type']; //select fron db
        $user->farm_type   = $request['service_type']; //select fron db
        $user->phone       = $request['phone']; 
        $user->reg_code    = $reg_code; 
        $user->password    = Hash::make($request->input('password'));
        $user->status      = 'verify';
        
        $user->save();
 
      return response()->json($user);
 
  }


  public function updateUser(Request $request, $id){
      $user  = User::find($id);
      $user->email          = $request->input('email');
      $user->name           = $request->input('name');
      $user->business_name  = $request->input('business_name');
      $user->location       = $request->input('location');
      $user->address        = $request->input('address');
      $user->bank_name      = $request->input('bank_name');
      $user->account_name   = $request->input('account_name');
      $user->account_number = $request->input('account_number'); 

      $user->save();
 
      return response()->json($user);
  } 

  public function deleteUser($id){
      $user  = User::find($id);
      $user->delete();
 
      return response()->json('Removed successfully.');
  }

  public function index(){
 
      $users  = User::all();
 
      return response()->json($users);
 
  }
}//class
