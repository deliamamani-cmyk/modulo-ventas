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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();

            //  Correlativo de venta (V-00001)
            $table->string('nro_venta', 20)->nullable();

            $table->foreignId('sucursal_id')->constrained('sucursals')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');

            $table->date('fecha_venta');
            $table->decimal('total_venta', 12, 2)->default(0);

            // num() en lugar de string() con array
            $table->enum('estado', ['pendiente', 'emitida', 'completada', 'cancelada', 'devuelta'])
                ->default('emitida');

            // enum() + typo "trnsferencia" → "transferencia"
            $table->enum('tipo_pago', ['efectivo', 'tarjeta', 'transferencia', 'mixto'])
                ->default('efectivo');

            //  Para el cálculo 
            $table->decimal('monto_recibido', 12, 2)->nullable();
            $table->decimal('cambio', 12, 2)->nullable();

            $table->text('nota')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};