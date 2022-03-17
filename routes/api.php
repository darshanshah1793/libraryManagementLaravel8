<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\BooksController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [ApiController::class, 'authenticate']);
Route::post('register', [ApiController::class, 'register']);

Route::group(['middleware' => ['jwt.verify']], function() {
    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);
    Route::get('books', [BooksController::class, 'index']);
    Route::get('books/{id}', [BooksController::class, 'show']);
    Route::post('create', [BooksController::class, 'store']);
    Route::put('update/{book}',  [BooksController::class, 'update']);
    Route::delete('delete/{book}',  [BooksController::class, 'destroy']);
    Route::post('rent_book', [ApiController::class, 'rent_book']);
    Route::get('userRentedBooks', [ApiController::class, 'getUserRentedBooks']);
});
