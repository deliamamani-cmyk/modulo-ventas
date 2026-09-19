<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');
            $table->foreignId('inventario_id')->constrained('inventarios')->onDelete('cascade');
            $table->integer('cantidad');
            $table->decimal('precio_venta_unidad', 12, 2);

            //  AGREGADA: el sistema guarda el subtotal por línea
            $table->decimal('subtotal', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
    }
};