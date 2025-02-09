<?php

use App\Http\Controllers\Api\UpdateUserCurrentServerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('user', function (Request $request) {
        return json_encode($request->user());
    });
    Route::patch('user/{user}/current-server', UpdateUserCurrentServerController::class)->name('user.current-server');
});
