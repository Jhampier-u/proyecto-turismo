<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_fet', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('zona_id')->constrained('zonas')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador'); // Para el flujo Jefe/Equipo
            
            $table->tinyInteger('demanda_flujos')->default(0);
            $table->tinyInteger('demanda_estadia')->default(0);
            
            $table->tinyInteger('super_institucionalidad')->default(0);
            $table->tinyInteger('super_organizacion')->default(0);
            $table->tinyInteger('super_planificacion')->default(0);
            
            $table->tinyInteger('imagen_apertura')->default(0);
            $table->tinyInteger('imagen_seguridad')->default(0);
            $table->tinyInteger('imagen_percibida')->default(0);
            $table->tinyInteger('imagen_marketing')->default(0);
            
            $table->decimal('media_demanda', 5, 2)->nullable();
            $table->decimal('fet_demanda', 5, 2)->nullable();
            
            $table->decimal('media_super', 5, 2)->nullable();
            $table->decimal('fet_super', 5, 2)->nullable();
            
            $table->decimal('media_imagen', 5, 2)->nullable();
            $table->decimal('fet_imagen', 5, 2)->nullable();

            $table->decimal('fet', 8, 4)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_fet');
    }
};