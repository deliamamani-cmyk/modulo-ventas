<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     */
    public function up(): void
    {
        
        Schema::dropIfExists('venta_historials');

        Schema::create('venta_historials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->string('cliente_nombre', 255)->nullable();
            $table->string('cliente_documento', 100)->nullable();
            $table->string('cliente_email', 255)->nullable();
            $table->string('cliente_celular', 50)->nullable();
            $table->string('sucursal_nombre', 255)->nullable();
            $table->string('usuario_nombre', 255)->nullable();
            $table->dateTime('fecha_venta')->nullable();
            $table->decimal('total_venta', 12, 2)->nullable();
            $table->string('estado', 50)->nullable();
            $table->string('tipo_pago', 50)->nullable();
            $table->text('nota')->nullable();
            $table->json('detalles')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_historials');
    }
};