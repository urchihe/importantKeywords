<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::group(['middleware' => []], function () {
     /**
     * search amazon auto-complete single 
     */
     Route::post('/auto-complete', 'SearchWordController@autoComplete');
    /**
     * search amazon auto-complete single 
     */
    Route::post('/auto-complete-single', 'SearchWordController@singleSearch');
    /**
     * search amazon auto-complete iterate
     */
     Route::post('/auto-complete-iterate', 'SearchWordController@iterateSearch');
     /**
     * search amazon auto-complete iterate with different Weight
     */
    Route::post('/auto-complete-weight', 'SearchWordController@iterateSearchWeight');
    /**
     * search amazon auto-complete iterate with first word and different Weight
     */
    Route::post('/auto-complete-first', 'SearchWordController@iterateSearchFirstWord');
});