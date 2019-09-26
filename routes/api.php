<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([

    'middleware' => 'api'

], function ($router) {

    Route::post('login', 'AuthController@login');
    Route::post('signin', 'AuthController@signin');
    Route::post('update', 'AuthController@update');
    Route::post('project', 'AuthController@project');
    Route::get('projects', 'AuthController@projects');
    Route::get('users', 'AuthController@users');
    Route::get('user/{id}', 'AuthController@user');
    Route::get('roles', 'AuthController@roles');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
});
