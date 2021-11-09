<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\JobOpeningController;
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

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login'); //Login route - can be used for both roles | Token expire: 60min
    Route::post('/register', [AuthController::class, 'register']); //Register route - role must be specified
    Route::post('/logout', [AuthController::class, 'logout']); //Logout route
});

Route::prefix('workflow', function (){
    Route::get('/display', [JobOpeningController::class,'workflow']); //Display complete workflow in json format
    Route::get('/actions', [JobOpeningController::class, 'showActions']); //Display only actions in json format
    Route::get('/states', [JobOpeningController::class, 'showStates']); //Display only states in json format
});


Route::group(['middleware' => 'auth:api'], function(){
    Route::group(['middleware' => 'role:client', 'prefix' => 'client'], function(){
        Route::get('/job-openings', [JobOpeningController::class, 'indexClient']); //Display all client job-openings (all states are visible)
        Route::post('/job-openings/create', [JobOpeningController::class, 'create']); //Create a new job-opening
    });
    Route::group(['middleware' => 'role:provider', 'prefix' => 'provider'], function(){
        Route::get('/job-openings', [JobOpeningController::class, 'indexProvider']); //
    });
    Route::post('/job-openings/action', [JobOpeningController::class, 'makeAction']); //Make an action to a specific job opening
});


//POSTMAN Collection: https://www.getpostman.com/collections/82468e0aec2f930c8d17
