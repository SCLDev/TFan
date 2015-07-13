<?php namespace App\Http\Models;
use App\Http\Models\Base as Model;

//Handles user related interactions
class User extends Model {
 
    private $_col   = "users";
 
    private $_error = null;
 
    public function get( $where ) {
		//retrieves a single record
		if ( is_array( $where ) ) {
			return $this->_findOne( $this->_col, $where );
		} else {
			$this->_where( '_id', $where );
			return $this->_findOne( $this->_col );
		}	
	}
 
    public function get_error() {
		//Provides more information about the error to the controller
		return $this->_error;
	}
 
    public function create( $user ) {
		//Creates a new user
		if ( is_array( $user ) ) {
			$user   = ( object ) $user;
		}
		$this->_where( '$or', array(
				array(
					"email"     => $user->email
				),
				array(
					"mobile"    => $user->mobile
				)
			)
		);
		$existing   = $this->_findOne( $this->_col );
		 
		//Original Code
		//if ( empty( ( array ) $existing ) ) {
		if ( empty(  $existing ) ) {
			$user   = $this->_insert( $this->_col, $user );
		} else {
			$user   = $existing;
		}
		 
		$user->_id  = ( string ) $user->_id;
		 
		return $user;
		
	}
 
    public function remove( $id ) {
		//Removes a user that is passed to the method, and returns an error if no user has been found, or an error occured
		$this->_where( '_id', $id );
		$user   = $this->_findOne( $this->_col );
		 
		//Original Code
		//if ( empty( ( array ) $user ) ) {
		if ( empty(  $user ) ) {
			$this->_error       = "ERROR_INVALID_ID";
			return false;
		} else {
			$this->_where( '_id', $id );
			if ( !$this->_remove( $this->_col ) ) {
				$this->_error   = "ERROR_REMOVING_USER";
				return false;
			}
		}
		 
		return $user;
	}
 
    public function retrieve( $id, $distance, $limit = 9999, $page = 1 ) {
	//Fetches the list of users, or returns error if no user have been found
		if ( !empty( $id ) && !empty( $distance ) ) {
		$this->_where( '_id', $id );
		$this->_select( 'location' );
		$user   = $this->_findOne( $this->_col );
		
	 //The original code	
	 //	if ( empty( ( array ) $user ) ) {
		if ( empty(  $user ) ) {
			$this->_error   = "ERROR_INVALID_USER";
			return false;
		}
	 
		$this->_where( '$and', array(
				array(
					'_id'       => array( '$ne' => new \MongoId( $id ) )
				),
				array(
					'location'  => array(
						'$nearSphere'       => array(
							'$geometry'     => array(
								'type'          => "Point",
								'coordinates'   => $user->location['coordinates']
							),
							'$maxDistance'  => ( float ) $distance
						)
					)
				)
			) );
		}
		 
		$this->_limit( $limit, ( $limit * --$page ) );
		return $this->_find( $this->_col );
	}
 
    public function update( $id, $data ) {
	//Updates a user
		if ( is_array( $data ) ) {
		$data   = ( object ) $data;
		}
		if ( isset( $data->email ) || isset( $data->mobile ) ) {
				$this->_where( '$and', array(
					array(
						'_id'       => array( '$ne' => new \MongoId( $id ) )
					),
					array(
						'$or'       => array(
							array(
								'email'     => ( isset( $data->email ) ) ? $data->email : ""
							),
							array(
								'mobile'    => ( isset( $data->mobile ) ) ? $data->mobile : ""
							)
						)
					)
				)
			);
			$existing   = $this->_findOne( $this->_col );
			//Original Code
			//if ( !empty( ( array ) $existing ) && $existing->_id != $id ) {
			if ( !empty( $existing ) && $existing->_id != $id ) {
				$this->_error   = "ERROR_EXISTING_USER";
				return false;
			}
		}
		 
		$this->_where( '_id', $id );
		return $this->_update( $this->_col, ( array ) $data );
	}
}
