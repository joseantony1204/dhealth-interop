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
        Schema::create('ihce_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint_url', 255);
            $table->string('tenant_id', 255);
            $table->string('scope', 255);
            $table->string('client_id', 255);
            $table->text('client_secret')->comment('Almacenado bajo cifrado de Laravel');
            $table->string('apim_subs_key', 255);

            $table->unsignedBigInteger('sede_id');
            /*$table->foreign('sede_id')->references('id')
                                        ->on('cfsedes')
                                        ->onDelete('restrict')
                                        ->onUpdate('cascade');*/

            $table->unsignedBigInteger('ambiente_id');
            $table->foreign('ambiente_id')->references('id')
                                        ->on('ihce_ambientes')
                                        ->onDelete('restrict')
                                        ->onUpdate('cascade');


            $table->timestamp('created_at', $precision = 0)->useCurrent();
            $table->unsignedBigInteger('created_by')->default(1);
            $table->timestamp('updated_at', $precision = 0)->useCurrentOnUpdate()->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at', $precision = 0)->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->unique(['sede_id', 'ambiente_id'], 'sede_ambiente_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ihce_configuraciones');
    }
};
