<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ChatbotTrainingController;
use App\Http\Controllers\HyperionViewController;
use App\Http\Controllers\RoutineApiController;
use App\Http\Controllers\AuthController;

// Autenticación: Login, Registro, Logout y Google OAuth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Google OAuth & Acceso Directo
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect'])->name('auth.google.redirect');
Route::get('/auth/google/direct', [AuthController::class, 'showGoogleDirect'])->name('auth.google.direct');
Route::post('/auth/google/direct', [AuthController::class, 'processGoogleDirect'])->name('auth.google.direct.post');
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])->name('auth.google.callback');
Route::post('/auth/google/one-click', [AuthController::class, 'googleOneClick'])->name('auth.google.one-click');

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
    Route::get('/user/workout-history', [ChatbotController::class, 'getHistory']);
    Route::get('/workout/history', [RoutineApiController::class, 'getHistory']);
    Route::post('/workout/history', [RoutineApiController::class, 'saveHistory']);
    Route::get('/metrics/report', [ChatbotController::class, 'getMetricsReport']);
    Route::get('/exercisedb/test', [ChatbotController::class, 'testExerciseDb']);
    Route::post('/routine/generate', [RoutineApiController::class, 'generateRoutine']);
    Route::post('/routine/low-impact', [RoutineApiController::class, 'generateLowImpactRoutine']);
    Route::get('/routine/progressive-overload', [RoutineApiController::class, 'getProgressiveOverload']);

    // Laboratorio de Entrenamiento Manual del Chatbot (Q&A, NLP, RAG y Reglas Clínicas)
    Route::get('/training', [ChatbotTrainingController::class, 'index']);
    Route::post('/training/entries', [ChatbotTrainingController::class, 'store']);
    Route::patch('/training/entries/{id}/toggle', [ChatbotTrainingController::class, 'toggle']);
    Route::delete('/training/entries/{id}', [ChatbotTrainingController::class, 'destroy']);
    Route::post('/training/rules', [ChatbotTrainingController::class, 'saveRules']);
    Route::post('/training/simulate', [ChatbotTrainingController::class, 'simulate']);
    Route::get('/training/export', [ChatbotTrainingController::class, 'export']);
    Route::post('/training/import', [ChatbotTrainingController::class, 'import']);
});
