<?php

use App\Http\Controllers\WeiXinController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    dump('会员的世界');
});

Route::get('/user', [\App\Http\Controllers\Controller::class, 'test']);
Route::post('/first_valid', [WeiXinController::class,'firstValid']);
Route::match(['get', 'post'],'/', [WeiXinController::class,'index']);

Route::match(['get', 'post'],'/first_valid', [WeiXinController::class,'firstValid']);
//Route::post('/first_valid', [WeiXinController::class,'firstValid']);
//Route::get('/first_valid', [WeiXinController::class,'firstValid']);

