<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_valoracion_territorial', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->unique()->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');

            // Los 21 nombres de columna se escriben literalmente y no se derivan de
            // App\Matrices\ValoracionTerritorial::todos(): esa clase está generada
            // desde el Excel (ver su cabecera) y una migración no debe depender de
            // código de app/, porque una base nueva y una ya migrada podrían
            // terminar con esquemas distintos si el generador cambia un nombre.

            // Contenido Territorial (CT) — 12 criterios.
            $table->tinyInteger('ct_energia_electrica')->default(0);
            $table->tinyInteger('ct_agua_potable')->default(0);
            $table->tinyInteger('ct_comunicacion')->default(0);
            $table->tinyInteger('ct_recoleccion_basura')->default(0);
            $table->tinyInteger('ct_problemas_sociales')->default(0);
            $table->tinyInteger('ct_salud')->default(0);
            $table->tinyInteger('ct_seguridad')->default(0);
            $table->tinyInteger('ct_conservacion_recursos')->default(0);
            $table->tinyInteger('ct_actividad_economica')->default(0);
            $table->tinyInteger('ct_organizacion_social')->default(0);
            $table->tinyInteger('ct_elementos_culturales')->default(0);
            $table->tinyInteger('ct_espacios_naturales')->default(0);

            // Ubicación y Conectividad (UC) — 9 criterios.
            $table->tinyInteger('uc_vialidad')->default(0);
            $table->tinyInteger('uc_infraestructura_conectividad')->default(0);
            $table->tinyInteger('uc_frecuencia_conectividad')->default(0);
            $table->tinyInteger('uc_distancia_atractivo')->default(0);
            $table->tinyInteger('uc_distancia_sitio_visita')->default(0);
            $table->tinyInteger('uc_distancia_destino')->default(0);
            $table->tinyInteger('uc_distancia_mercado_emisor')->default(0);
            $table->tinyInteger('uc_conglomeracion_oferta')->default(0);
            $table->tinyInteger('uc_senalizacion')->default(0);

            $table->decimal('ct_total', 5, 3)->default(0);
            $table->decimal('uc_total', 5, 3)->default(0);

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_valoracion_territorial');
    }
};
