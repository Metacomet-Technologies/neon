<?php

// Example routes for the refactored license controllers
// These would be added to routes/api.php within the auth:sanctum middleware group

use App\Http\Controllers\Api\License\AssignLicenseController;
use App\Http\Controllers\Api\License\ParkLicenseController;
use App\Http\Controllers\Api\License\TransferLicenseController;
use App\Http\Controllers\Api\LicenseController;

Route::group(['middleware' => 'auth:sanctum'], function () {
    // Standard resource routes for licenses
    Route::apiResource('licenses', LicenseController::class)->only(['index', 'show']);

    // Invokable controllers for license operations
    Route::post('licenses/{license}/assign', AssignLicenseController::class)->name('licenses.assign');
    Route::post('licenses/{license}/park', ParkLicenseController::class)->name('licenses.park');
    Route::post('licenses/{license}/transfer', TransferLicenseController::class)->name('licenses.transfer');
});
