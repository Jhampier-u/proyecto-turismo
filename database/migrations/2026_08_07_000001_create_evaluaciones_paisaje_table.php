<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matriz de Análisis y Valoración del Paisaje.
 *
 * Los 34 criterios se escriben literalmente en vez de derivarlos de
 * App\Matrices\Paisaje: esa clase está generada y puede regenerarse, y una
 * migración tiene que ser un registro histórico congelado. Si dependiera de
 * ella, regenerar con un nombre distinto produciría un esquema en una base
 * nueva y otro en una ya migrada, sin que ningún test lo detectase.
 *
 * Cada criterio guarda el valor crudo del instrumento: 0, 3 o 5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_paisaje', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->unique()->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');

            // Ep — Evolución del Paisaje
            $table->tinyInteger('ep_cambios_tiempo')->default(0);
            $table->tinyInteger('ep_imagen_pasada')->default(0);
            $table->tinyInteger('ep_imagen_actual')->default(0);
            $table->tinyInteger('ep_imagen_futura')->default(0);
            $table->tinyInteger('ep_conservacion')->default(0);
            $table->tinyInteger('ep_sostenibilidad')->default(0);

            // Pn — Recursos Paisajísticos de Interés Natural
            $table->tinyInteger('pn_areas_verdes')->default(0);
            $table->tinyInteger('pn_areas_protegidas')->default(0);
            $table->tinyInteger('pn_geomorfologia')->default(0);
            $table->tinyInteger('pn_topografia')->default(0);
            $table->tinyInteger('pn_hidrografia')->default(0);
            $table->tinyInteger('pn_geologia')->default(0);
            $table->tinyInteger('pn_flora')->default(0);
            $table->tinyInteger('pn_fauna')->default(0);
            $table->tinyInteger('pn_cobertura_suelo')->default(0);

            // Pc — Recursos Paisajísticos de Interés Cultural
            $table->tinyInteger('pc_arquitectonico_moderno')->default(0);
            $table->tinyInteger('pc_vernacular')->default(0);
            $table->tinyInteger('pc_edificado_historico')->default(0);
            $table->tinyInteger('pc_religioso')->default(0);
            $table->tinyInteger('pc_vivo')->default(0);

            // Iv — Recursos Paisajísticos de Interés Visual
            $table->tinyInteger('iv_referentes_naturales')->default(0);
            $table->tinyInteger('iv_referentes_antropicos')->default(0);
            $table->tinyInteger('iv_puntos_observacion')->default(0);
            $table->tinyInteger('iv_singularidad')->default(0);
            $table->tinyInteger('iv_representatividad')->default(0);
            $table->tinyInteger('iv_visibilidad')->default(0);
            $table->tinyInteger('iv_calidad_escenica')->default(0);

            // Cp — Conflictos Paisajísticos
            $table->tinyInteger('cp_degradacion_natural')->default(0);
            $table->tinyInteger('cp_degradacion_antropica')->default(0);
            $table->tinyInteger('cp_dinamicas_territoriales')->default(0);
            $table->tinyInteger('cp_fragmentacion')->default(0);
            $table->tinyInteger('cp_infraestructura')->default(0);
            $table->tinyInteger('cp_actividades_diversas')->default(0);
            $table->tinyInteger('cp_conurbaciones')->default(0);

            // Promedio de cada categoría, en la escala 0-5 del instrumento.
            $table->decimal('ep_promedio', 5, 3)->default(0);
            $table->decimal('pn_promedio', 5, 3)->default(0);
            $table->decimal('pc_promedio', 5, 3)->default(0);
            $table->decimal('iv_promedio', 5, 3)->default(0);
            $table->decimal('cp_promedio', 5, 3)->default(0);

            // Suma ponderada de los promedios. Los pesos suman 1, así que va de 0 a 5.
            $table->decimal('paisaje_total', 5, 3)->default(0);

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_paisaje');
    }
};
