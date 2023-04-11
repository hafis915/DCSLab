<?php

use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('auth', [ApiAuthController::class, 'auth', 'middleware' => ['guest', 'throttle:3,1']])->name('api.auth');

Route::group(['prefix' => 'get', 'middleware' => ['auth', 'auth:sanctum', 'throttle:100,1'], 'as' => 'api.get'], function () {
    Route::group(['prefix' => 'dashboard', 'as' => '.db'], function () {
        Route::group(['prefix' => 'module', 'as' => '.module'], function () {
            Route::group(['prefix' => 'profile', 'as' => '.profile'], function () {
                Route::get('read', [ProfileController::class, 'readProfile'])->name('.read');
            });
        });

        Route::group(['prefix' => 'admin', 'as' => '.admin'], function () {
            Route::group(['prefix' => 'users', 'as' => '.users'], function () {
                Route::get('read', [UserController::class, 'list'])->name('.list');
                Route::get('read/{user:ulid}', [UserController::class, 'read'])->name('.read');

                Route::get('roles/read', [UserController::class, 'getAllRoles'])->name('.roles.read');
            });
        });

        Route::group(['prefix' => 'core', 'as' => '.core'], function () {
            Route::get('user/menu', [DashboardController::class, 'userMenu'])->name('.user.menu');
            Route::get('user/api', [DashboardController::class, 'userApi'])->name('.user.api');
        });
    });
});

Route::group(['prefix' => 'post', 'middleware' => ['auth', 'auth:sanctum', 'throttle:50,1'], 'as' => 'api.post'], function () {
    Route::group(['prefix' => 'dashboard', 'as' => '.db'], function () {
    });
});
