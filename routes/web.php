<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\HorarioController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {   
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';


Route::resource('users', UserController::class);
Route::resource('categorias', CategoriaController::class);
Route::resource('servicios', ServicioController::class);
Route::resource('calendarios', CalendarioController::class);
Route::post('horarios', [HorarioController::class, 'store'])->name('horarios.store');



