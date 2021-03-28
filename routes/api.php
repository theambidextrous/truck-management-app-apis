<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\SetupController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\TruckController;
use App\Http\Controllers\Api\LoadController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\EarningController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StatController;
use App\Http\Controllers\Api\AdvanceController;
use App\Http\Controllers\Api\DeductionController;

/** stream */
Route::prefix('/downloads')->group( function() {
    Route::get('/get/rpt/file/{file}', [ReportController::class, 'stream'])->name('stream');
});
/** users */
Route::prefix('/users')->group( function() {
    Route::post('/signup', [AdminController::class, 'signup']);
    Route::post('/signin', [AdminController::class, 'signin']);
    Route::post('/request/reset/{email}', [AdminController::class, 'reqreset']);
    Route::post('/verify/{code}/reset/{email}', [AdminController::class, 'verifyreset']);
    Route::post('/finish/reset', [AdminController::class, 'finishreset']);
    Route::middleware('auth:api')->group( function(){
        Route::post('/update/info', [AdminController::class, 'update_info']);
        Route::post('/update/pwd', [AdminController::class, 'update_pwd']);
        Route::post('/update/pic', [AdminController::class, 'update_pic']);
    });
});
/** Setup */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/setups')->group( function() {
        Route::post('/set', [SetupController::class, 'set']);
        Route::get('/find', [SetupController::class, 'find']);
        Route::get('/refresh', [SetupController::class, 'refresh']);
    });
});
/** Staffs */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/staffs')->group( function() {
        Route::post('/add', [StaffController::class, 'add']);
        Route::post('/edit/{id}', [StaffController::class, 'edit']);
        Route::get('/find/{id}', [StaffController::class, 'find']);
        Route::get('/findall', [StaffController::class, 'findall']);
        Route::put('/drop/{id}', [StaffController::class, 'drop']);
    });
});
/** Drivers */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/drivers')->group( function() {
        Route::post('/add', [DriverController::class, 'add']);
        Route::post('/edit/{id}', [DriverController::class, 'edit']);
        Route::get('/find/{id}', [DriverController::class, 'find']);
        Route::get('/findall', [DriverController::class, 'findall']);
        Route::put('/drop/{id}', [DriverController::class, 'drop']);
    });
});
/** Trucks */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/trucks')->group( function() {
        Route::post('/add', [TruckController::class, 'add']);
        Route::post('/import', [TruckController::class, 'import']);
        Route::post('/edit/{id}', [TruckController::class, 'edit']);
        Route::get('/find/{id}', [TruckController::class, 'find']);
        Route::get('/findall', [TruckController::class, 'findall']);
        Route::put('/drop/{id}', [TruckController::class, 'drop']);
    });
});
/** TruckOwners */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/owners')->group( function() {
        Route::post('/add', [OwnerController::class, 'add']);
        Route::post('/edit/{id}', [OwnerController::class, 'edit']);
        Route::get('/find/{id}', [OwnerController::class, 'find']);
        Route::get('/findall', [OwnerController::class, 'findall']);
        Route::put('/drop/{id}', [OwnerController::class, 'drop']);
    });
});
/** loads */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/loads')->group( function() {
        Route::post('/add', [LoadController::class, 'add']);
        Route::post('/edit/{id}', [LoadController::class, 'edit']);
        Route::get('/find/{id}', [LoadController::class, 'find']);
        Route::get('/findall', [LoadController::class, 'findall']);
        Route::put('/drop/{id}', [LoadController::class, 'drop']);

        Route::get('/distance/{from}/and/{to}', [LoadController::class, 'getDistance']);
        Route::get('/brokers', [LoadController::class, 'brokers']);
        Route::post('/upload/{id}', [LoadController::class, 'loadUpload']);
        Route::post('/paid/{id}', [LoadController::class, 'loadPaid']);
    });
});
/** expenses */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/expenses')->group( function() {
        /** exp groups */
        Route::post('/group/add', [ExpenseController::class, 'g_add']);
        Route::post('/group/edit/{id}', [ExpenseController::class, 'g_edit']);
        Route::get('/group/find/{id}', [ExpenseController::class, 'g_find']);
        Route::get('/group/findall', [ExpenseController::class, 'g_findall']);
        Route::put('/group/drop/{id}', [ExpenseController::class, 'g_drop']);
        /** expenses */
        Route::post('/add', [ExpenseController::class, 'add']);
        Route::post('/edit/{id}', [ExpenseController::class, 'edit']);
        Route::get('/find/{id}', [ExpenseController::class, 'find']);
        Route::get('/findall', [ExpenseController::class, 'findall']);
        Route::post('/by/truck/{id}', [ExpenseController::class, 'by_truck']);
        Route::put('/drop/{id}', [ExpenseController::class, 'drop']);
    });
});
/** deductions */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/deductions')->group( function() {
        Route::post('/add', [DeductionController::class, 'add']);
        Route::post('/edit/{id}', [DeductionController::class, 'edit']);
        Route::get('/find/{id}', [DeductionController::class, 'find']);
        Route::get('/findall', [DeductionController::class, 'findall']);
        Route::put('/drop/{id}', [DeductionController::class, 'drop']);
        Route::get('/find-scheduled', [DeductionController::class, 'find_scheduled']);
    });
});
/** earnings */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/earnings')->group( function() {
        /** driver */
        Route::post('/driver/findall', [EarningController::class, 'driver_e']);
        Route::post('/driver/download', [EarningController::class, 'driver_d']);
        /** dispatcher */
        Route::post('/dispatcher/findall', [EarningController::class, 'dispatcher_e']);
        Route::post('/dispatcher/download', [EarningController::class, 'dispatcher_d']);
    });
});
/** reports */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/reports')->group( function() {
        /** weekly */
        Route::post('/weekly', [ReportController::class, 'weekly']);
        Route::post('/download/weekly', [ReportController::class, 'download_weekly']);
        /** factoring */
        Route::post('/factoring/loads/d', [ReportController::class, 'find_loads_delivered']);
        Route::post('/factoring', [ReportController::class, 'factoring']);
        Route::post('/export/invoices', [ReportController::class, 'export_invoices']);
        Route::post('/export/invoices/paperwork', [ReportController::class, 'export_invoices_paperwork']);
        Route::post('/export/paperwork', [ReportController::class, 'export_paperwork']);
    });
});
/** statistics */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/statistics')->group( function() {
        /** weekly */
        Route::get('/dashboard', [StatController::class, 'dashboard']);
    });
});

/** advances */
Route::middleware(['auth:api'])->group( function(){
    Route::prefix('/advances')->group( function() {
        Route::post('/add', [AdvanceController::class, 'add']);
        Route::post('/users/{t}', [AdvanceController::class, 'users']);
        Route::post('/edit/{id}', [AdvanceController::class, 'edit']);
        Route::get('/find/{id}', [AdvanceController::class, 'find']);
        Route::get('/findall', [AdvanceController::class, 'findall']);
        Route::put('/drop/{id}', [AdvanceController::class, 'drop']);
    });
});

/** fallback */
Route::fallback(function () {
    return response()->json(['status' => 404,'softbct_error' => 'Not Found!'], 404);
});
Route::get('/', function (Request $request) {
    return response(['status' => 499, 'message' => 'point of no return']);
});
Route::fallback(function () {
    return response(['status'=> 499, 'message' => 'oops! Congrats! you\'ve reached point of no return']);
});