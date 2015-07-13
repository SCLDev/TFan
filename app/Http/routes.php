<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::post( 'sessions', 'SessionController@create' );
 
Route::delete( 'sessions/{token}', 'SessionController@destroy' );
 
Route::delete( 'users/{id}', 'UserController@remove' );
 
Route::get( 'users', 'UserController@retrieve' );
 
Route::get( 'users/{id}', 'UserController@get' );
 
Route::post( 'users', 'UserController@create' );
 
Route::put( 'users/{id}', 'UserController@update' );
