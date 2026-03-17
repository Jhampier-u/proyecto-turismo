<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_potencialidad', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->constrained('zonas')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');

            // === FACTORES ENDÓGENOS (FN) ===
            // Recursos Naturales - Zonas de Litoral
            $table->tinyInteger('rn_litoral_playas')->default(0);
            $table->tinyInteger('rn_litoral_arrecifes')->default(0);
            $table->tinyInteger('rn_litoral_cuevas')->default(0);
            $table->tinyInteger('rn_litoral_flora_fauna')->default(0);

            // Recursos Naturales - Zonas de Montaña
            $table->tinyInteger('rn_montana_montanas')->default(0);
            $table->tinyInteger('rn_montana_sierras')->default(0);
            $table->tinyInteger('rn_montana_canadas')->default(0);
            $table->tinyInteger('rn_montana_canones')->default(0);
            $table->tinyInteger('rn_montana_volcanes')->default(0);
            $table->tinyInteger('rn_montana_paramos')->default(0);

            // Recursos Naturales - Áreas Naturales Protegidas
            $table->tinyInteger('rn_anp_reservas_marinas')->default(0);
            $table->tinyInteger('rn_anp_reserva_geobotanica')->default(0);
            $table->tinyInteger('rn_anp_reserva_ecologica')->default(0);
            $table->tinyInteger('rn_anp_reserva_fauna')->default(0);
            $table->tinyInteger('rn_anp_parque_nacional')->default(0);
            $table->tinyInteger('rn_anp_area_recreacion')->default(0);

            // Recursos Naturales - Cuerpos de Agua
            $table->tinyInteger('rn_agua_lagos')->default(0);
            $table->tinyInteger('rn_agua_rios')->default(0);
            $table->tinyInteger('rn_agua_cascadas')->default(0);
            $table->tinyInteger('rn_agua_termas')->default(0);

            // Recursos Culturales - Artístico Monumental
            $table->tinyInteger('rc_am_zonas_arqueologicas')->default(0);
            $table->tinyInteger('rc_am_fosiles')->default(0);
            $table->tinyInteger('rc_am_pinturas_rupestres')->default(0);
            $table->tinyInteger('rc_am_ciudades_coloniales')->default(0);
            $table->tinyInteger('rc_am_arquitectura_religiosa')->default(0);
            $table->tinyInteger('rc_am_museos')->default(0);

            // Recursos Culturales - Nacionalidades y Pueblos
            $table->tinyInteger('rc_np_grupos_etnicos')->default(0);
            $table->tinyInteger('rc_np_expresiones_artisticas')->default(0);
            $table->tinyInteger('rc_np_ferias_mercados')->default(0);
            $table->tinyInteger('rc_np_eventos_folkloricos')->default(0);

            // Recursos Culturales - Expresiones Contemporáneas
            $table->tinyInteger('rc_ec_obras_arte')->default(0);
            $table->tinyInteger('rc_ec_centros_cientificos')->default(0);
            $table->tinyInteger('rc_ec_explotaciones_mineras')->default(0);
            $table->tinyInteger('rc_ec_plantaciones')->default(0);

            // Planta Turística - Alojamiento
            $table->tinyInteger('pt_aloj_hoteles')->default(0);
            $table->tinyInteger('pt_aloj_hostales')->default(0);
            $table->tinyInteger('pt_aloj_hosterias')->default(0);
            $table->tinyInteger('pt_aloj_haciendas')->default(0);
            $table->tinyInteger('pt_aloj_cabanas')->default(0);
            $table->tinyInteger('pt_aloj_glamping')->default(0);

            // Planta Turística - Restauración
            $table->tinyInteger('pt_rest_restaurantes')->default(0);
            $table->tinyInteger('pt_rest_cafeterias')->default(0);
            $table->tinyInteger('pt_rest_bares')->default(0);
            $table->tinyInteger('pt_rest_discotecas')->default(0);

            // Planta Turística - Intermediación
            $table->tinyInteger('pt_inter_agencias_mayoristas')->default(0);
            $table->tinyInteger('pt_inter_agencias_internacionales')->default(0);
            $table->tinyInteger('pt_inter_operadoras')->default(0);
            $table->tinyInteger('pt_inter_agencias_duales')->default(0);

            // Planta Turística - Transportación
            $table->tinyInteger('pt_trans_terrestre')->default(0);
            $table->tinyInteger('pt_trans_ferroviaria')->default(0);
            $table->tinyInteger('pt_trans_aerea')->default(0);
            $table->tinyInteger('pt_trans_maritima')->default(0);

            // Planta Turística - Interpretación
            $table->tinyInteger('pt_guia_locales')->default(0);
            $table->tinyInteger('pt_guia_nacionales')->default(0);
            $table->tinyInteger('pt_guia_patrimonio')->default(0);
            $table->tinyInteger('pt_guia_aventura')->default(0);

            // Tipologías de Turismo
            $table->tinyInteger('tt_naturaleza')->default(0);
            $table->tinyInteger('tt_sol_playa')->default(0);
            $table->tinyInteger('tt_cultural')->default(0);
            $table->tinyInteger('tt_urbano')->default(0);
            $table->tinyInteger('tt_comunitario')->default(0);
            $table->tinyInteger('tt_aventura')->default(0);
            $table->tinyInteger('tt_gastronomico')->default(0);
            $table->tinyInteger('tt_salud')->default(0);
            $table->tinyInteger('tt_deportivo')->default(0);
            $table->tinyInteger('tt_cientifico')->default(0);

            // Infraestructura
            $table->tinyInteger('i_transporte')->default(0);
            $table->tinyInteger('i_vialidad')->default(0);
            $table->tinyInteger('i_comunicaciones')->default(0);
            $table->tinyInteger('i_salud')->default(0);
            $table->tinyInteger('i_energia')->default(0);
            $table->tinyInteger('i_agua_potable')->default(0);

            // === FACTORES EXÓGENOS (FX) ===
            // Demanda - Afluencia Turística
            $table->tinyInteger('dt_flujos_locales')->default(0);
            $table->tinyInteger('dt_flujos_regionales')->default(0);
            $table->tinyInteger('dt_flujos_nacionales')->default(0);
            $table->tinyInteger('dt_flujos_internacionales')->default(0);

            // Demanda - Marketing Turístico
            $table->tinyInteger('dt_mk_organismo_promotor')->default(0);
            $table->tinyInteger('dt_mk_plan_marketing')->default(0);
            $table->tinyInteger('dt_mk_tendencias_mercado')->default(0);
            $table->tinyInteger('dt_mk_investigacion')->default(0);
            $table->tinyInteger('dt_mk_redes_sociales')->default(0);

            // Superestructura
            $table->tinyInteger('st_politica_publica')->default(0);
            $table->tinyInteger('st_modelo_gestion')->default(0);
            $table->tinyInteger('st_actores')->default(0);
            $table->tinyInteger('st_marco_normativo')->default(0);
            $table->tinyInteger('st_planificacion')->default(0);

            // === RESULTADOS CALCULADOS ===
            // Factores Endógenos
            $table->decimal('val_rn_litoral', 6, 4)->nullable();
            $table->decimal('val_rn_montana', 6, 4)->nullable();
            $table->decimal('val_rn_anp', 6, 4)->nullable();
            $table->decimal('val_rn_agua', 6, 4)->nullable();
            $table->decimal('val_recursos_naturales', 6, 4)->nullable();

            $table->decimal('val_rc_am', 6, 4)->nullable();
            $table->decimal('val_rc_np', 6, 4)->nullable();
            $table->decimal('val_rc_ec', 6, 4)->nullable();
            $table->decimal('val_recursos_culturales', 6, 4)->nullable();

            $table->decimal('val_recursos_turisticos', 6, 4)->nullable();

            $table->decimal('val_pt_alojamiento', 6, 4)->nullable();
            $table->decimal('val_pt_restauracion', 6, 4)->nullable();
            $table->decimal('val_pt_intermediacion', 6, 4)->nullable();
            $table->decimal('val_pt_transportacion', 6, 4)->nullable();
            $table->decimal('val_pt_interpretacion', 6, 4)->nullable();
            $table->decimal('val_planta_turistica', 6, 4)->nullable();

            $table->decimal('val_tipologias', 6, 4)->nullable();
            $table->decimal('val_infraestructura', 6, 4)->nullable();

            $table->decimal('fn_total', 8, 4)->nullable(); // Factores Endógenos

            // Factores Exógenos
            $table->decimal('val_afluencia', 6, 4)->nullable();
            $table->decimal('val_marketing', 6, 4)->nullable();
            $table->decimal('val_superestructura', 6, 4)->nullable();

            $table->decimal('fx_total', 8, 4)->nullable(); // Factores Exógenos

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_potencialidad');
    }
};
