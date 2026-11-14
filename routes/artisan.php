<?php

use App\Http\Controllers\SystemUtilityController;
use Illuminate\Support\Facades\Route;

// Bare artisan routes without session middleware (safe for unmigrated database)
Route::get('/migrate', [SystemUtilityController::class, 'migrate'])->name('migrate');
Route::get('/routes/{action?}', [SystemUtilityController::class, 'routes'])->name('routes');
Route::get('/run/{command}', [SystemUtilityController::class, 'run'])->name('run');
