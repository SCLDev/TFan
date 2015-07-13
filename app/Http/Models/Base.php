<?php namespace App\Http\Models;
 
use Illuminate\Support\Facades\Config;


class Base {
    
    private $_config        = null;
    
    private $_conn          = null;
    
    private $_db            = null;
	
	//Helper methods variables
	private $_ws			= array();
	 
	private $_sls           = array();
	 
	private $_lmt           = 99999;
	 
	private $_ost           = 0;
	 
    public function __construct() {
        $this->_config        = Config::get( 'mongodb' );
        
        $this->_connect();
    }
	
	//Database Query Methods
    protected function _limit( $limit, $offset = null ) {
		//For pagination of READ  operation
		if ( $limit !== NULL && is_numeric( $limit ) && $limit >= 1 ) {
		$this->_lmt = $limit;
		}
		if ( $offset !== NULL && is_numeric( $offset ) && $offset >= 1 ) {
			$this->_ost = $offset;
		}
	}
 
	protected function _select( $select = "" ) {
		//Select operation the field for READ
		$fields = explode( ',', $select );
		foreach ( $fields as $field ) {
			$this->_sls[trim( $field )] = true;
		}		
	}
	 
	protected function _where( $key, $value = null ) {
		//Filtering the READ results
		if ( is_array( $key ) ) {
			foreach( $key as $k => $v ) {
				$this->_ws[$k] = $v;
			}
		} else {
			$this->_ws[$key] = $value;
		}	
	}
	
	//Database connection method
    private function _connect() {
		$conn = 'mongodb://'.$this->_config['host'];
		if( ! empty( $this->_config['port'] ) ) {
			$conn .= ":{$this->_config['port']}";
		}
		 
		$options = array();
		if( ! empty( $this->_config['user'] ) && ! empty( $this->_config['pass'] ) ) {
			$options['username'] = $this->_config['user'];
			$options['password'] = $this->_config['pass'];
		}
		 
		try {
			$this->_conn    = new \MongoClient( $conn, $options );
		 
			$this->_db      = $this->_conn->{$this->_config['db']};
			return true;
		} catch( \MongoConnectionException $e ) {
			$this->_conn    = null;
			return false;
		}
	}
	
	//Where checker and combiner
	private function _set_where( $where = null ) {
		if ( is_array( $where ) ) {
			$where  = array_merge( $where, $this->_ws );
			foreach ( $where as $k => $v ) {
				if ( $k == "_id" && ( gettype( $v ) == "string" ) ) {
					$this->_ws[$k]  = new \MongoId( $v );
				} else {
					$this->_ws[$k]  = $v;
				}
			}
		} else if( is_string( $where ) ) {
			$wheres = explode( ',', $where );
			foreach ( $wheres as $wr ) {
				$pair = explode( '=', trim( $wr ) );
				if ( $pair[0] == "_id" ) {
					$this->_ws[trim( $pair[0] )] = new \MongoId( trim( $pair[1] ) );
				} else {
					$this->_ws[trim( $pair[0] )] = trim( $pair[1] );
				}
			}
		}
	}
	
	//Parameter resetting function
	private function _flush() {
		$this->_ws      = array();
		$this->_sls     = array();
		$this->_lmt     = 99999;
		$this->_ost     = 0;
	}
	
	//CRUD Operations
	
	protected function _insert( $collection, $data ) {
		// Operation for creating a record in the system
		if ( is_object( $data ) ) {
			$data   = ( array ) $data;
		}
	 
		$result = false;
		try {
			if ( $this->_db->{$collection}->insert( $data ) ) {
				$data['_id']    = ( string ) $data['_id'];
				$result         = ( object ) $data;
			}
		} catch( \MongoCursorException $e ) {
			$result         = new \stdClass();
			$result->error  = $e->getMessage();
		}
		$this->_flush();
	 
		return $result;
	}
	
	
	//The return (and find) operation
	protected function _findOne( $collection, $where = array() ) {
		// Operation for finding a record in the system
		$this->_set_where( $where );
	 
		$row    = $this->_db->{$collection}->findOne( $this->_ws, $this->_sls );
		$this->_flush();
		return ( object ) $row;
	}
	
	protected function _find( $collection, $where = array() ) {
		// Operation for finding an array of records in the system
		$this->_set_where( $where );
		
		$docs = $this->_db->{$collection}
			->find( $this->_ws, $this->_sls )
			->limit( $this->_lmt )
			->skip( $this->_ost );
		$this->_flush();
	 
		$result = array();
		foreach( $docs as $row ) {
			$result[] = ( object ) $row;
		}
		return $result;
	}
	
	//Update Operation
	protected function _update( $collection, $data, $where = array() ) {
		//For updating the record on the system
		if ( is_object( $data ) ) {
			$data   = ( array ) $data;
		}
		$this->_set_where( $where );
	 
		if ( array_key_exists( '$set', $data ) ) {
			$newdoc     = $data;
		} else {
			$newdoc     = array( '$set' => $data );
		}
	 
		$result         = false;
		try {
			if( $this->_db->{$collection}->update( $this->_ws, $newdoc ) ) {
				$result = ( object ) $data;
			}
		} catch( \MongoCursorException $e ) {
			$result         = new \stdClass();
			$result->error  = $e->getMessage();
		}
		$this->_flush();
	 
		return $result;
	}
	
	//Delete Operation
	protected function _remove( $collection, $where = array() ) {
		//Delete a record and flush variables
		$this->_set_where( $where );
	 
		$result = false;
		try {
			if ( $this->_db->{$collection}->remove( $this->_ws ) ) {
				$result = true;
			}
		} catch( \MongoCursorException $e ) {
			$result         = new \stdClass();
			$result->error  = $e->getMessage();
		}
		$this->_flush();
	 
		return $result;
	}

}



