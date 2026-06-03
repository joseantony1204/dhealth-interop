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
        Schema::create('ihce_transmisiones', function (Blueprint $table) {
            $table->id();

            // Data cruda pre-guardada de D-Health lista para ser procesada/enviada
            $table->json('source_snapshot_data')->nullable()->comment('Instantánea de datos del paciente y cita antes del envío');
            
            // Logs de Auditoría y Trazabilidad del Ministerio
            $table->string('vida_code', 100)->nullable()->unique()->comment('Código único retornado por el Gobierno');
            $table->json('last_payload_sent')->nullable()->comment('Último JSON FHIR estructurado transmitido');
            $table->json('response_log')->nullable()->comment('Historial completo de respuestas HTTP, códigos de error y respuestas semánticas');
            
            // Resiliencia de Envíos
            $table->unsignedTinyInteger('retry_count')->default(0)->comment('Contador de reintentos ejecutados');
            $table->timestamp('last_attempt_at')->nullable();

            // Control de Estados (🟢 Aprobado, 🔴 Rechazado, 🟡 Pendiente)
            $table->enum('estado', ['PENDING', 'APPROVED', 'REJECTED','CONNECTIVITY_ERROR'])->default('PENDING')->index();
            // Control de Tipo RDA
            $table->enum('typerda', ['RDA_PATIENT', 'RDA_CONSULTA'])->index();

            $table->unsignedBigInteger('configuracion_id');
            $table->foreign('configuracion_id')->references('id')
                                        ->on('ihce_configuraciones')
                                        ->onDelete('restrict')
                                        ->onUpdate('cascade');

            $table->unsignedBigInteger('cita_id');
            /*$table->foreign('cita_id')->references('id')
                                        ->on('adcitas')
                                        ->onDelete('restrict')
                                        ->onUpdate('cascade');*/

            $table->timestamp('created_at', $precision = 0)->useCurrent();
            $table->unsignedBigInteger('created_by')->default(1);
            $table->timestamp('updated_at', $precision = 0)->useCurrentOnUpdate()->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at', $precision = 0)->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ihce_transmisiones');
    }
};
