<?php

use App\Http\Controllers\Auth\LoginCallbackController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\UserIntegrationController;
use App\Http\Controllers\CommandController;
use App\Http\Controllers\JoinServerController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\TermsOfServiceController;
use App\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::get('login', LoginController::class)->name('login');

Route::get('discord/callback', LoginCallbackController::class)->name('discord.callback')->name('discord.callback');

Route::group(['middleware' => 'auth'], function () {
    Route::post('logout', LogoutController::class)->name('logout');
    Route::inertia('profile', 'Profile')->name('profile');
    Route::get('join-server', JoinServerController::class)->name('join-server');
    Route::prefix('server')->name('server.')->group(function () {
        Route::get('/', [ServerController::class, 'index'])->name('index');
        Route::get('{serverId}', [ServerController::class, 'show'])->name('show');
        Route::resource('{serverId}/command', CommandController::class)->except(['show']);
    });
    Route::get('{provider}/callback', [UserIntegrationController::class, 'store'])->name('user-integration.store');
    Route::delete('{provider}/disconnect', [UserIntegrationController::class, 'destroy'])->name('user-integration.destroy');
    Route::get('{provider}/connect', [UserIntegrationController::class, 'create'])->name('user-integration.create');
});

Route::get('unsubscribe/{email}', [UnsubscribeController::class, 'update'])->name('unsubscribe.update');
Route::get('unsubscribe/{email}/confirm', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
Route::get('terms-of-service', TermsOfServiceController::class)->name('terms-of-service');
Route::get('privacy-policy', PrivacyPolicyController::class)->name('privacy-policy');

// Route::get('test', function () {
//     $result = OpenAI::chat()->create([
//         'model' => 'gpt-3.5-turbo',
//         'messages' => [
//             ['role' => 'user', 'content' => 'Hello!'],
//         ],
//     ]);

//     return $result->choices[0]->message->content;
// });

Route::fallback(function () {
    return abort(404, 'Page not found');
});
