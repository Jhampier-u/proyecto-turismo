<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_percepcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado', 20)->default('borrador');

            // Dimensión Social (DS) — 3 ítems, peso 20%
            $table->unsignedTinyInteger('ds1_posicion_turistica')->default(0);
            $table->unsignedTinyInteger('ds2_interes_participar')->default(0);
            $table->unsignedTinyInteger('ds3_contribucion_social')->default(0);

            // Percepción Local (PL) — 6 ítems, peso 40%
            $table->unsignedTinyInteger('pl1_conoc_recursos')->default(0);
            $table->unsignedTinyInteger('pl2_conoc_atractivos')->default(0);
            $table->unsignedTinyInteger('pl3_conoc_motivo_visita')->default(0);
            $table->unsignedTinyInteger('pl4_conoc_flujo_visitantes')->default(0);
            $table->unsignedTinyInteger('pl5_sentimiento_visitantes')->default(0);
            $table->unsignedTinyInteger('pl6_necesidad_visitantes')->default(0);

            // Percepción Económica (PE) — 3 ítems, peso 20%
            $table->unsignedTinyInteger('pe1_incidencia_ingresos')->default(0);
            $table->unsignedTinyInteger('pe2_beneficios_esperados')->default(0);
            $table->unsignedTinyInteger('pe3_disposicion_inversion')->default(0);

            // Nivel de Organización (NO) — 4 ítems, peso 20%
            $table->unsignedTinyInteger('no1_organizacion_colectiva')->default(0);
            $table->unsignedTinyInteger('no2_lideres_sociales')->default(0);
            $table->unsignedTinyInteger('no3_opinion_lideres')->default(0);
            $table->unsignedTinyInteger('no4_conflictos_sociales')->default(0);

            // Cálculos por categoría (promedio y ponderado)
            $table->decimal('media_ds', 5, 3)->default(0);
            $table->decimal('pond_ds', 5, 3)->default(0);
            $table->decimal('media_pl', 5, 3)->default(0);
            $table->decimal('pond_pl', 5, 3)->default(0);
            $table->decimal('media_pe', 5, 3)->default(0);
            $table->decimal('pond_pe', 5, 3)->default(0);
            $table->decimal('media_no', 5, 3)->default(0);
            $table->decimal('pond_no', 5, 3)->default(0);

            // Total (0.0 – 1.0 → porcentaje)
            $table->decimal('percepcion_total', 5, 3)->default(0);

            $table->text('acciones_mejora')->nullable();

            $table->timestamps();

            $table->unique('zona_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_percepcion');
    }
};
