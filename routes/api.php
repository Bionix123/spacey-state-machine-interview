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
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
});

Route::get('/display-workflow', [JobOpeningController::class,'workflow']); //Displays workflow in json format

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
