
<?php namespace App\Http\Models;

//In charge of creating, removing, and finding a session in the database.
class Session extends Base {
 
    private $_col   = "sessions";
 
    public function create( $user ) {
        $this->_where( 'user_id', ( string ) $user->_id );
        $existing   = $this->_findOne( $this->_col );
//        if ( !empty ((array)$existing )) {
        if ( !empty ($existing )) {
            $this->_where( 'user_id', ( string ) $user->_id );
            $this->_remove( $this->_col );
        }
 
        $session            = new \stdClass();
        $session->user_id   = ( string ) $user->_id;
        $session->user_name = $user->name;
        $session            = $this->_insert( $this->_col, $session );
 
        return $session;
    }
 
    public function find( $token ) {
        $this->_where( '_id', $token );
        return $this->_findOne( $this->_col );
    }
 
    public function remove( $token ) {
        $this->_where( '_id', $token );
        return $this->_remove( $this->_col );
    }
}