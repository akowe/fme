<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Helper\ResponseBuilder;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\UserProfile;
use App\Otp;
use App\Role;
use App\Country;
use App\FarmType;
use App\FeedBack;
use App\ServiceType;
use App\OrderRequest;
use Carbon\Carbon;
use Carbon\Profile;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Laravel\Lumen\Auth\Authorizable;

class UserController extends Controller
{
  
  public function __construct()
  {
      //create superadmin  
      $user = User::firstOrNew(['name' => 'superadmin', 'phone' => '08188373898']);
      $user->ip = 'none';
      $user->name ="superadmin";
      $user->phone ="08188373898";
      $user->country      = 'Nigeria';
      $user->country_code ='+234';
      $user->user_type   =  '1'; // can select from role table
      $user->password    = Hash::make('password');
      $user->status      = 'verified';
      $user->save();
  }

   public function getOtp(Request $request){
        //generate new otp
          $code   = str_random(6);
          $new_otp        = new Otp();
          $new_otp->code  = $code;
          $new_otp->save();

        //send otp to user phone

    }


  //update user with  otp
  public function verifyUser(Request $request){
      
      //Input::get('code')
      $getCode = $request->input('code');

      //check if exist
        $otp =  Otp::where('code', $getCode)->exists();
        if($otp){
          $user  = User::where('reg_code', $getCode)
          ->update([
            'status' =>'verified'
          ]);

          $user  = User::where('reg_code', $getCode)->first();
             
        // users profile page
          $profile = new UserProfile();
          $profile->user_id  = $user->id; //get inserted user id
          $profile->save(); 
          $status = true;
          $message ="verified";
          $error = "";
          $data = "";
          $code = 200;                
          return ResponseBuilder::result($status, $message, $error, $data, $code); 
       }else{
        
        $status = false;
        $message ="kindly put your right verification code";
        $error = "";
        $data = "";
        $code = 401;                
        return ResponseBuilder::result($status, $message, $error, $data, $code); 
       }
 
  } 


  public function deleteUser(Request $request){

    $id = $request->id;
    $user  = User::where('id', $id)->first();
    if($user){
      $user  = User::where('id', $id)
      ->update([
        'status' =>'delete'
      ]);
      $status = true;
      $message ="You have succssfully deleted a user ";
      $error = "";
      $data = "";
      $code = 200;                
      return ResponseBuilder::result($status, $message, $error, $data, $code);  
    }else{
      $status = false;
      $message ="User not found";
      $error = "";
      $data = "";
      $code = 401;                
      return ResponseBuilder::result($status, $message, $error, $data, $code);  
    }
 
     
  }
  
  // update profile details
  public function updateProfile(Request $request){
 
    // validation
    $validator =Validator ::make($request->all(), [
      'email' => 'required',
      'business_name' => 'required',
      'address' => 'required',
      'location' => 'required',
      'bank_name' => 'required',
      'account_name' => 'required',
      'account_number' => 'required|numeric',
      ]);  

      if($validator->fails()){
        $status = false;
        $message ="";
        $error = $validator->errors()->first();
        $data = "";
        $code = 401;                
        return ResponseBuilder::result($status, $message, $error, $data, $code);   
      }else{ 


        $user_id = $request->user_id;
        $profile = array(
          'email' => $request->input('email'), 
          'business_name'   => $request->input('business_name'),
          'address' => $request['address'],
          'location' => $request['location'],
          'bank_name' => $request->input('bank_name'),
          'account_name' => $request->input('account_name'), 
          'account_number'  => $request->input('account_number')
        );

      $profile  = UserProfile::where('user_id', $user_id)
      ->update($profile);
      $status = true;
      $message ="";
      $error = "";
      $data = $profile;
      $code = 200;                
      return ResponseBuilder::result($status, $message, $error, $data, $code);    
    }
  }

    // get profile details
    public function getProfile(Request $request){
      $id =  $request->id;
      $profile = UserProfile::where('user_id', $id)->first();
      if($profile){
        $status = true;
        $message ="";
        $error = "";
        $data = $profile;
        $code = 200;                
        return ResponseBuilder::result($status, $message, $error, $data, $code);
      }else{
        $status = false;
        $message ="No user with this profile";
        $error = "";
        $data = "";
        $code = 401;                
        return ResponseBuilder::result($status, $message, $error, $data, $code);        
      }

    } 

  public function index(){
 
      $users  = User::all();
 
      $status = true;
      $message ="";
      $error = "";
      $data = $users;
      $code = 200;                
      return ResponseBuilder::result($status, $message, $error, $data, $code);
 
  }

  public function user(Request $request){
 
    $id =  $request->id;
    $user = User::where('id', $id)->first();
    if($user){
      $status = true;
      $message ="";
      $error = "";
      $data = $user;
      $code = 200;                
      return ResponseBuilder::result($status, $message, $error, $data, $code);
    }else{
      $status = false;
      $message ="User with id ".$request->id." is not found";
      $error = "";
      $data = "";
      $code = 401;                
      return ResponseBuilder::result($status, $message, $error, $data, $code);      
    }
   
   

 }

  function random_code($length)
  {
    return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $length);
  }
  
  

   //forgot passowrd
  public  function userForgotPassword(Request $request){

    //validattion
    $this->validate($request, [
      'phone' => 'required|min:11|numeric',
      ]);
     $country = new Country();
        //check if exist
      $user =  User::where('phone', $request->phone)->exists();
      if($user){

        // bulk sms will be replaced here
        $password_reset_code  =random_int(100000, 999999); //random_code(6);
        $otp            = new Otp();
        $otp->code      = $password_reset_code;
        $otp->save();

      
        $country_code = $country->get_country_code($request->country);
        $sms_api_key = 'TLLXf8lLQZpsvuFouxWoN89YzoxL23RyXDUtDKAgNcniDpgGdpMUkgqxilO0tW';
        $sms_message = 'Kindly use this '.$password_reset_code.' code to reset your password.'. "\r\n";
        //$country_code = $country->get_country_code($query_country);
        $payload = array(   
          'to'=>$country_code.ltrim($request['phone'], '0'),
          'from'=>'fastbeep',
          'sms'=>$sms_message,
          'channel'=> 'generic',
          'type'=>'plain',
          'api_key'=>$sms_api_key, 
        );
        $post_data = json_encode($payload);   
            
        if (isset($request['phone']) && !empty($request['phone'])) {
          $curl = curl_init();
          curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.ng.termii.com/api/sms/send',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          //CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_SSL_VERIFYPEER => false,
          //CURLOPT_CAINFO, "C:/xampp/cacert.pem",
          //CURLOPT_CAPATH, "C:/xampp/cacert.pem",
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>$post_data,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
          ),
          ));
          $response = curl_exec($curl);
          $err = curl_error($curl);
          $res = json_decode($response, true);
          
          if($err){
            return response()->json(["error"=>$err, "message"=>"Message is not sent"]);
          }else{
            if($response){
              $status = true;
              $message ="Message successfully sent";
              $error = "";
              $data = "";
              $code = 200;                
              return ResponseBuilder::result($status, $message, $error, $data, $code);   
             
            }else{
              $status = true;
              $message ="Message is not sent";
              $error = "";
              $data = "";
              $code = 400;                
              return ResponseBuilder::result($status, $message, $error, $data, $code);   
                    
            }
          }
                        
        } else{
          $status = false;
          $message ="Phone number can not be determined";
          $error = "";
          $data = "";
          $code = 401;                
          return ResponseBuilder::result($status, $message, $error, $data, $code);   
         
        }
      }


   }

   //reset new passowrd
   public  function userResetPassword(Request $request){

    //validattion
    $validator =Validator ::make($request->all(), [
      'phone' => 'required|numeric',
      'new_password' => 'required',
      'reset' => 'reuired'
    ]);      
   if($validator->fails()){
    $status = false;
    $message ="";
    $error = $validator->errors()->first();
    $data = "";
    $code = 401;                
    ResponseBuilder::result($status, $message, $error, $data, $code);   
   } 
        //check if exist
      $user =  User::where('reg_code', $request->reset_code)->exists();
      if($user){

        $user  = User::where('phone', $request->phone)
        ->update([
          'password' => Hash::make($request['new_password'])
        ]);
        $status = true;
        $message ="Message successfully sent";
        $error = "";
        $data = "";
        $code = 200;                
        return ResponseBuilder::result($status, $message, $error, $data, $code);   
       
      }else{
        $status = false;
        $message ="Reset Code is wrong";
        $error = "";
        $data = "";
        $code = 401;                
        return ResponseBuilder::result($status, $message, $error, $data, $code);   
       
      }


   }

  // authenticate user for login
  public function authenticateUser(Request $request){
      // validation
             // validation
             $validator =Validator ::make($request->all(), [
              'phone' => 'required|numeric',
              'password' => 'required'

          ]);      
           if($validator->fails()){
            $status = false;
            $message ="";
            $error = $validator->errors()->first();
            $data = "";
            $code = 401;                
            return ResponseBuilder::result($status, $message, $error, $data, $code);   
           } 
      $condition= array('phone'=>$request->phone);
      $user = User::where($condition)->first();
      if($user){
         if($user->status =="verified"){
          if (Hash::check($request->input('password'),$user->password)) {
            $apikey = base64_encode(str_random(40));
            User::where('phone', $request->input('phone'))->update(['api_key' => $apikey]);
            $status = true;
            $message ="";
            $error = "";
            $data = $apikey;
            $code = 200;                
            return ResponseBuilder::result($status, $message, $error, $data, $code); 
          }else{
            $status = false;
            $message ="Kindly provide the right password";
            $error = "";
            $data = "";
            $code = 401;                
            return ResponseBuilder::result($status, $message, $error, $data, $code);             
          }
         }else{
          $status = false;
          $message ="Kindly verify your account";
          $error = "";
          $data = "";
          $code = 401;                
          return ResponseBuilder::result($status, $message, $error, $data, $code);         
         }
      }else{
        $status = true;
        $message ="Kindly put the right phone number";
        $error = "";
        $data = "";
        $code = 401;                
        return ResponseBuilder::result($status, $message, $error, $data, $code);        
      }
      
   }
  

   // fetch all countries
   public function allCountries(){
 
    $countries = Country::all();
    $status = true;
    $message ="";
    $error = "";
    $data = $countries;
    $code = 200; 
    return ResponseBuilder::result($status, $message, $error, $data, $code);  

  }  
   public function logout(){

    if(Auth::user()){
 
      $status = true;
      $message ="Successfully logout";
      $error = "";
      $data ="";
      $code = 200; 
      $user = Auth::user();
      $user->api_key = null;
      $user->save();
      return ResponseBuilder::result($status, $message, $error, $data, $code);  

    }else{
      $status = true;
      $message ="Already logout";
      $error = "";
      $data ="";
      $code = 200; 
      return ResponseBuilder::result($status, $message, $error, $data, $code);  
     
    }

   }

    // get location by user_id
    public function getLocation(Request $request){
      $user_id = $request->user_id;
      $profile = UserProfile::where("user_id", $user_id)->first();
      if($profile ){
          $location = array("location" => $profile->location);
          $status = true;
          $message ="";
          $error = "";
          $data = $location;
          $code = 200;                
          return ResponseBuilder::result($status, $message, $error, $data, $code); 
      }else{
          $status = false;
          $message ="";
          $error = "";
          $data = "No location found";
          $code = 401;                
          return ResponseBuilder::result($status, $message, $error, $data, $code);
      }

  
    } 
   //fetch country code from databade. country table
    public function CountryCode(){
 
    $country_code  = Country::all();

    $status = true;
    $message ="";
    $error = "";
    $data =$country_code;
    $code = 200; 
    return ResponseBuilder::result($status, $message, $error, $data, $code); 

  } 


    // feedback
    public function feedBack (Request $request){
      // validation
          $validator =Validator ::make($request->all(), [
  
              'subject' => 'required',
              'service_type' => 'required',
              'message' => 'required',
              'user_id' => 'required'
          
      
          ]);      
          if($validator->fails()){
          $status = false;
          $message ="";
          $error = $validator->errors()->first();
          $data = "";
          $code = 401;                
          return ResponseBuilder::result($status, $message, $error, $data, $code);   
          }else{
              $feedback = new FeedBack();
              $feedback->subject = $request->subject;
              $feedback->service_type = $request->service_type;
              $feedback->message = $request->message;
              $feedback->user_id = $request->user_id;
              $feedback->save();
              $status = true;
              $message ="Feedback successfully submitted";
              $error = "";
              $data = "";
              $code = 200;                
              return ResponseBuilder::result($status, $message, $error, $data, $code); 
          
          }            
}
 
  // fetch all feedbacks
  public function getFeedBack(){
 
    $feedbacks  = FeedBack::all();
    $status = true;
    $message ="";
    $error = "";
    $data = $feedbacks;
    $code = 200;                
    return ResponseBuilder::result($status, $message, $error, $data, $code); 

  } 


}