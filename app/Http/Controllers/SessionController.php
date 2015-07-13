<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\BaseController;
use App\Http\Models\Session as SessionModel;
use App\Http\Models\User as UserModel;

class SessionController extends BaseController
{
    //
	private $_model = null;
 
	public function __construct() {
		$this->_model   = new SessionModel();
	}
	 
	public function create( Request $request ) {
	//Creates a user session using Laravel's own Request object
		$email      = $request->get( 'email' );
		$mobile     = $request->get( 'mobile' );
		$fbId       = $request->get( 'fbId' );
		 
		$result     = new \stdClass();
		if ( ( empty( $email ) && empty( $mobile ) ) || empty( $fbId ) ) {
			$result->error  = "ERROR_INVALID_PARAMETERS";
			$result->status = 403;
		} else {
			$UserModel  = new UserModel();
			$where      = ( !empty( $email ) ) ? array( 'email' => $email ) : array( 'mobile' => $mobile );
			$user       = $UserModel->get( $where );
	
			//Orignal Code had ( array ) before $user
			if ( empty(  $user ) ) {
				$name           = $request->get( 'name' );
				$gender         = $request->get( 'gender' );
				$location       = $request->get( 'location' );
				
				//Orignal Code had ( array ) before $location			 
				if ( empty( $name ) || empty(  $location ) || empty( $gender ) ) {
					$result->error  = "ERROR_INVALID_PARAMETERS";
					$result->status = 403;
				} else {
					if ( gettype( $location ) == "string" ) {
						$location   = json_decode( $location );
					}
					$locObj                 = new \stdClass();
					$locObj->type           = "Point";
					$locObj->coordinates    = array( $location->lon, $location->lat );
				 
					$user->name     = $name;
					$user->fbId     = $fbId;
					$user->email    = $email;
					$user->mobile   = $mobile;
					$user->gender   = $gender;
					$user->location = $locObj;
				 
					$user           = $UserModel->create( $user );
				}
			} else {
				if ( $fbId != $user->fbId ) {
					$result->error  = "ERROR_INVALID_CREDENTIALS";
					$result->status = 403;
				}
			}
			 
			if ( !property_exists( $result, "error" ) ) {
				$result         = $this->_model->create( $user );
				$result->token  = $result->_id;
				unset( $result->_id );
			}
		}
		 
		return $this->_response( $result );
	}
	 
	public function destroy( $token ) {
	//Ends a user session and deletes its token
		$result = new \stdClass();
		if ( !$this->_model->remove( $token ) ) {
			$result->error  = "ERROR_REMOVING_SESSION";
			$result->status = 403;
		}
		 
		return $this->_response( $result );
	}
}
