<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\TestAttemptController;
use App\Http\Controllers\Api\Admin\TestManagementController;

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/
Route::get('/ping', function () {
    return response()->json(['status' => 'API working']);
});

/*
|--------------------------------------------------------------------------
| Auth (Public)
|--------------------------------------------------------------------------
*/
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/complete-profile', [AuthController::class, 'completeProfile']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Student Test Flow
    |--------------------------------------------------------------------------
    */
    Route::get('/tests', [TestController::class, 'index']);          // list published tests
    Route::get('/tests/{id}', [TestController::class, 'show']);      // test details

    Route::post('/tests/{id}/start', [TestAttemptController::class, 'start']);
    Route::post('/attempts/{id}/answer', [TestAttemptController::class, 'answer']);
    Route::post('/attempts/{id}/submit', [TestAttemptController::class, 'submit']);
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')
    ->prefix('admin')
    ->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Question Bank
    |--------------------------------------------------------------------------
    */
    Route::post('/questions', [TestManagementController::class, 'createQuestion']);
    Route::get('/questions', [TestManagementController::class, 'listQuestions']);
    // optional later:
    // Route::get('/questions');
    // Route::delete('/questions/{id}');

    /*
    |--------------------------------------------------------------------------
    | Question Sets
    |--------------------------------------------------------------------------
    */
    Route::post('/question-sets', [TestManagementController::class, 'createQuestionSet']);
    // optional later:
    // Route::get('/question-sets');
    // Route::get('/question-sets/{id}');
    Route::get('/question-sets', function () {
        return \App\Models\QuestionSet::select('id', 'name')
            ->orderBy('created_at', 'desc')
            ->get();
    });
    

    /*
    |--------------------------------------------------------------------------
    | Tests
    |--------------------------------------------------------------------------
    */
    Route::get('/tests', [TestManagementController::class, 'index']);
    Route::post('/tests', [TestManagementController::class, 'createTest']);
    Route::post('/tests/{id}/publish', [TestManagementController::class, 'publish']);
});
