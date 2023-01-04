<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ExportCSV;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('index');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/prodotti', function () {
    return view('prodotti');
});

Route::get('/export_csv', [ExportCSV::class, "export"])->name("exportCSV");

Route::post('/login', [LoginController::class, "login"])->name("loginForm");

Route::group(['middleware' => ['auth']], function() {
    Route::get('/logout', [LogoutController::class, "perform"])->name('logout.perform');
 });
