<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\HyperionViewController;
use App\Http\Controllers\RoutineApiController;

// Página principal apunta directamente a Hyperion Play
Route::get('/', [HyperionViewController::class, 'index'])->name('home');
Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/limpiar-dataset', [DatasetController::class, 'clean']);

// Interfaz interactiva de Hyperion Play (RPG Dashboard & Chatbot)
Route::get('/hyperion', [HyperionViewController::class, 'index'])->name('hyperion.index');

// Endpoints de API para Hyperion Play
Route::prefix('api')->group(function () {
    Route::post('/chat/message', [ChatbotController::class, 'sendMessage']);
    Route::post('/chat/reset', [ChatbotController::class, 'resetChat']);
    Route::get('/user/profile', [ChatbotController::class, 'getProfile']);
    Route::post('/user/create', [ChatbotController::class, 'createUser']);
    Route::post('/user/update', [ChatbotController::class, 'updateUser']);
    Route::post('/user/switch', [ChatbotController::class, 'switchUser']);
    Route::post('/workout/complete', [ChatbotController::class, 'completeWorkout']);
    Route::get('/workout/history', [RoutineApiController::class, 'getHistory']);
    Route::post('/workout/history', [RoutineApiController::class, 'saveHistory']);
    Route::get('/metrics/report', [ChatbotController::class, 'getMetricsReport']);
    Route::get('/exercisedb/test', [ChatbotController::class, 'testExerciseDb']);
    Route::post('/routine/generate', [RoutineApiController::class, 'generateRoutine']);
    Route::post('/routine/low-impact', [RoutineApiController::class, 'generateLowImpactRoutine']);
    Route::get('/routine/progressive-overload', [RoutineApiController::class, 'getProgressiveOverload']);
});
