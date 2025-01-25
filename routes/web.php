<?php

use App\Http\Controllers\Auth\LoginCallbackController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::get('login', LoginController::class)->name('login');

Route::get('discord/callback', LoginCallbackController::class)->name('discord.callback')->name('discord.callback');

Route::group(['middleware' => 'auth'], function () {
    Route::post('logout', LogoutController::class)->name('logout');
    Route::inertia('profile', 'Profile')->name('profile');
});

Route::get('unsubscribe/{email}', [UnsubscribeController::class, 'update'])->name('unsubscribe.update');
Route::get('unsubscribe/{email}/confirm', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
