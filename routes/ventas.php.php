<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\VentaTmpController;
use App\Http\Controllers\VentaHistorialController;

// ==========================================
// RUTAS DEL MÓDULO DE VENTAS
// ==========================================

// ----------- 1. VENTAS (CARRITO / ÓRDENES) -----------
Route::prefix('carrito_ventas')->name('admin.carrito_ventas.')->group(function () {
    Route::get('/', [VentaTmpController::class, 'create'])->name('create')->middleware(['auth', 'can:ver carrito']);
    Route::get('/index', [VentaTmpController::class, 'index'])->name('index')->middleware(['auth', 'can:ver carrito']);
    Route::get('/checkout', [VentaTmpController::class, 'checkout'])->name('checkout')->middleware(['auth', 'can:ver checkout']);
    Route::post('/create', [VentaTmpController::class, 'store'])->name('store')->middleware(['auth', 'can:guardar carrito']);
    Route::post('/items/add', [VentaTmpController::class, 'addItems'])->name('addItems')->middleware(['auth', 'can:agregar items carrito']);
    Route::put('/items/{itemId}', [VentaTmpController::class, 'updateItem'])->name('updateItem')->middleware(['auth', 'can:actualizar item carrito']);
    Route::delete('/items', [VentaTmpController::class, 'clearItems'])->name('clearItems')->middleware(['auth', 'can:limpiar items carrito']);
    Route::delete('/items/{itemId}', [VentaTmpController::class, 'removeItem'])->name('removeItem')->middleware(['auth', 'can:eliminar item carrito']);
    Route::get('/venta/{id}', [VentaTmpController::class, 'show'])->name('show')->middleware(['auth', 'can:ver detalle carrito']);
    Route::get('/venta/{id}/edit', [VentaTmpController::class, 'edit'])->name('edit')->middleware(['auth', 'can:ver formulario editar carrito']);
    Route::put('/{id}', [VentaTmpController::class, 'update'])->name('update')->middleware(['auth', 'can:actualizar carrito']);
    Route::delete('/{id}', [VentaTmpController::class, 'destroy'])->name('destroy')->middleware(['auth', 'can:eliminar carrito']);
    Route::post('/cliente-rapido', [VentaTmpController::class, 'clienteRapido'])->name('clienteRapido')->middleware(['auth', 'can:crear cliente rapido']);
});

// ----------- 2. VENTAS (EMITIDAS) -----------
Route::prefix('ventas')->name('admin.ventas.')->group(function () {
    Route::get('/', [VentaController::class, 'index'])->name('index')->middleware(['auth', 'can:ver ventas']);
    Route::get('/exportar', [VentaController::class, 'exportar'])->name('exportar')->middleware(['auth', 'can:exportar ventas']);
    
    // Reportes
    Route::get('/reportes', [VentaController::class, 'reportes'])->name('reportes')->middleware(['auth', 'can:ver reportes ventas']);
    Route::get('/reportes/ver', [VentaController::class, 'verReporte'])->name('reportes.ver')->middleware(['auth', 'can:ver reporte ventas']);
    
    // Acciones sobre una venta específica
    Route::get('/{venta}', [VentaController::class, 'show'])->name('show')->middleware(['auth', 'can:ver detalle venta']);
    Route::post('/{venta}/anular', [VentaController::class, 'anular'])->name('anular')->middleware(['auth', 'can:anular venta']);
    Route::get('/{venta}/imprimir', [VentaController::class, 'imprimir'])->name('imprimir')->middleware(['auth', 'can:imprimir venta']);
    Route::get('/{venta}/imprimirTicket', [VentaController::class, 'imprimirTicket'])->name('ticket')->middleware(['auth', 'can:imprimir ticket venta']);
});

// ----------- 3. HISTORIAL DE VENTAS -----------
Route::get('/admin/ventas/historia', [VentaController::class, 'historia'])->name('admin.ventas.historia')->middleware(['auth', 'can:ver historia ventas']);
Route::get('/admin/ventas-historial', [VentaHistorialController::class, 'index'])->name('admin.ventas.historial')->middleware(['auth', 'can:ver historial ventas']);