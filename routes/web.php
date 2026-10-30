<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DatasetController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/limpiar-dataset', [DatasetController::class, 'clean']);