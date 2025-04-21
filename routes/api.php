<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentHistoryController;
use App\Http\Controllers\RoutesController;
use App\Http\Controllers\VehiclesTypeController;
use App\Http\Controllers\WebAuthController;
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

Route::post('/members', [MemberController::class, 'store']);
Route::get('/getBalance', [MemberController::class, 'getBalance']);
Route::get('/getGameList', [MemberController::class, 'getGameList']);
Route::get('/launchGame', [MemberController::class, 'launchGame']);

Route::post('/forget-password', [PasswordResetController::class, 'forgetPassword'])->middleware('guest');
Route::get('/reset-password', [PasswordResetController::class, 'resetPasswordPage'])->middleware('guest');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('guest');

Route::get('/profiles', [WebAuthController::class, 'userProfile']);
Route::post('/getUserInfo', [UserController::class, 'getUserInfo']);

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/loginnn', [WebAuthController::class, 'userProfile']);
    Route::post('/loginn', [WebAuthController::class, 'login']);
    Route::post('/register', [MemberController::class, 'store']);
});

Route::middleware('jwt')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/profile', [AuthController::class, 'userProfile']);
    Route::post('/change-password/{id}', [AuthController::class, 'changePassword']);

    Route::group(['prefix' => 'role'], function () {
        Route::get('/', [RoleController::class, 'index'])->permission(PermissionEnum::ROLE_INDEX->value);
        Route::post('/', [RoleController::class, 'store'])->permission(PermissionEnum::ROLE_STORE->value);
        Route::get('/{id}', [RoleController::class, 'show'])->permission(PermissionEnum::ROLE_SHOW->value);
        Route::post('/{id}', [RoleController::class, 'update'])->permission(PermissionEnum::ROLE_UPDATE->value);
        Route::delete('/{id}', [RoleController::class, 'destroy'])->permission(PermissionEnum::ROLE_DESTROY->value);
    });

    Route::group(['prefix' => 'permission'], function () {
        Route::get('/', [PermissionController::class, 'index'])->permission(PermissionEnum::PERMISSION_INDEX->value);
        Route::get('/{id}', [PermissionController::class, 'show'])->permission(PermissionEnum::PERMISSION_SHOW->value);

    });
    
    Route::group(['prefix' => 'user'], function () {
        Route::post('/assign-role', [UserController::class, 'assignRole'])->permission(PermissionEnum::USER_STORE->value);
        Route::post('/remove-role', [UserController::class, 'removeRole'])->permission(PermissionEnum::USER_UPDATE->value);
        Route::get('/', [UserController::class, 'index'])->permission(PermissionEnum::USER_INDEX->value);
        Route::post('/', [UserController::class, 'store'])->permission(PermissionEnum::USER_STORE->value);
        Route::get('/{id}', [UserController::class, 'show'])->permission(PermissionEnum::USER_SHOW->value);
        Route::post('/{id}', [UserController::class, 'update'])->permission(PermissionEnum::USER_UPDATE->value);
        Route::delete('/{id}', [UserController::class, 'destroy'])->permission(PermissionEnum::USER_DESTROY->value);
    });

    Route::group(['prefix' => 'member'], function () {
        Route::get('/', [MemberController::class, 'index'])->permission(PermissionEnum::MEMBER_INDEX->value);
        Route::post('/', [MemberController::class, 'store'])->permission(PermissionEnum::MEMBER_STORE->value);
        Route::get('/{id}', [MemberController::class, 'show'])->permission(PermissionEnum::MEMBER_SHOW->value);
        Route::post('/{id}', [MemberController::class, 'update'])->permission(PermissionEnum::MEMBER_UPDATE->value);
        Route::delete('/{id}', [MemberController::class, 'destroy'])->permission(PermissionEnum::MEMBER_DESTROY->value);        
    });

    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', [DashboardController::class, 'getDashboardData']);
    });
    

    // Route::group(['prefix' => 'dashboard'], function () {
    //     Route::get('/', [DashboardController::class, 'getDashboardData'])->permission(PermissionEnum::DASHBOARD_INDEX->value);
    // });

});

// Route::get('/image/{path}', [ItemController::class, 'getImage'])->where('path', '.*');
