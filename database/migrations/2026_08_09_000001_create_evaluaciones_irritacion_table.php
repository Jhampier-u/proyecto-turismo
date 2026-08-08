<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice de Irritación Turística: dos bloques de seis atributos, escala 0-10.
 *
 * Los criterios nacen nullable y sin defecto, a diferencia de las cinco
 * matrices anteriores, que necesitaron una migración posterior para llegar
 * aquí: un criterio sin responder no es un 0, y aquí el 0 significa además el
 * mejor resultado posible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_irritacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->unique()->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');

            // Bloque de visitantes — 6 atributos.
            $table->tinyInteger('vis_congestion')->nullable();
            $table->tinyInteger('vis_calidad_servicios')->nullable();
            $table->tinyInteger('vis_calidad_actividades')->nullable();
            $table->tinyInteger('vis_calidad_vida')->nullable();
            $table->tinyInteger('vis_apertura')->nullable();
            $table->tinyInteger('vis_seguridad')->nullable();

            // Bloque de la localidad receptora — 6 atributos.
            $table->tinyInteger('res_congestion')->nullable();
            $table->tinyInteger('res_impacto_social')->nullable();
            $table->tinyInteger('res_impacto_economico')->nullable();
            $table->tinyInteger('res_impacto_ambiental')->nullable();
            $table->tinyInteger('res_calidad_vida')->nullable();
            $table->tinyInteger('res_seguridad')->nullable();

            // El sufijo _promedio no es cosmético: EstadoZona::esColumnaDeCriterio()
            // separa criterios de columnas calculadas por él, y un nombre distinto
            // haría que estas dos contaran como criterios en el «8 de 12».
            $table->decimal('visitantes_promedio', 5, 3)->nullable();
            $table->decimal('residentes_promedio', 5, 3)->nullable();

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_irritacion');
    }
};
