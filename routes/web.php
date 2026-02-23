<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\InformeClaseController;
use App\Http\Controllers\LicenciaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\SesionProgramadaController;
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

  if (auth()->user()->is_alumno) {
        return redirect()->route('catalogo.index');
    } elseif (auth()->user()->is_tutor) {
        return redirect()->route('informes-clase.index');
    } elseif (auth()->user()->is_propietario) {
        return Inertia::render('Dashboard');
    }
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';


Route::resource('users', UserController::class);
Route::resource('categorias', CategoriaController::class);
Route::resource('servicios', ServicioController::class);
Route::resource('calendarios', CalendarioController::class);
Route::resource('licencias', LicenciaController::class);
Route::resource('informes-clase', InformeClaseController::class);


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('pagos', [PagoController::class, 'index'])->name('pagos.index');
    Route::post('pagos/cuotas/{cuota}/pagar', [PagoController::class, 'pagarCuota'])->name('pagos.pagar-cuota');
    
    Route::get('sesiones', [SesionProgramadaController::class, 'index'])->name('sesiones.index');
    Route::get('sesiones/{sesion}', [SesionProgramadaController::class, 'show'])->name('sesiones.show');
    Route::patch('sesiones/{sesion}/link', [SesionProgramadaController::class, 'updateLink'])
        ->name('sesiones.link.update');
    Route::patch('sesiones/{sesion}/asistencias/{asistencia}', [SesionProgramadaController::class, 'updateAsistencia'])
        ->name('sesiones.asistencias.update');
});

Route::prefix('catalogo')->name('catalogo.')->group(function () {
    Route::get('/', [CatalogoController::class, 'index'])->name('index');
    Route::get('/servicios/{servicio}', [CatalogoController::class, 'show'])->name('servicio.show');
    Route::get('/calendarios/{calendario}/preinscripcion', [CatalogoController::class, 'previewInscripcion'])->name('inscripcion.preview');
    Route::get('/calendarios/{calendario}/pago', [CatalogoController::class, 'pago'])->name('inscripcion.pago');
    Route::post('/calendarios/{calendario}/confirmar-pago', [CatalogoController::class, 'confirmarPago'])->name('inscripcion.confirmar-pago');
});



