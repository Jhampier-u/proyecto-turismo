<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Columnas obsoletas que existen en la BD pero no en el controlador
    private array $toDrop = [
        'rn_montana_paramos',
        'rn_anp_parque_nacional',
        'rc_am_arquitectura_religiosa',
        'rc_am_museos',
        'pt_aloj_cabanas',
        'pt_aloj_glamping',
        'pt_inter_agencias_mayoristas',
        'pt_inter_agencias_internacionales',
        'pt_inter_agencias_duales',
        'tt_cientifico',
        'dt_flujos_locales',
        'dt_flujos_regionales',
        'dt_flujos_nacionales',
        'dt_flujos_internacionales',
        'dt_mk_redes_sociales',
        'st_planificacion',
    ];

    public function up(): void
    {
        // 1. Agregar columnas faltantes
        Schema::table('evaluaciones_potencialidad', function (Blueprint $table) {

            // RN — Zonas de Litoral (faltaban 2)
            $table->tinyInteger('rn_litoral_actividades_acuaticas')->default(0)->after('rn_litoral_flora_fauna');
            $table->tinyInteger('rn_litoral_areas_deserticas')->default(0)->after('rn_litoral_actividades_acuaticas');

            // RN — Zonas de Montaña (faltaban 6)
            $table->tinyInteger('rn_montana_cuevas')->default(0)->after('rn_montana_canones');
            $table->tinyInteger('rn_montana_geisers')->default(0)->after('rn_montana_cuevas');
            $table->tinyInteger('rn_montana_valles')->default(0)->after('rn_montana_volcanes');
            $table->tinyInteger('rn_montana_bosques')->default(0)->after('rn_montana_valles');
            $table->tinyInteger('rn_montana_flora_fauna')->default(0)->after('rn_montana_bosques');
            $table->tinyInteger('rn_montana_areas_deserticas')->default(0)->after('rn_montana_flora_fauna');

            // RN — Áreas Naturales Protegidas (faltaban 6)
            $table->tinyInteger('rn_anp_reserva_biologica')->default(0)->after('rn_anp_reserva_fauna');
            $table->tinyInteger('rn_anp_reserva_vida_silvestre')->default(0)->after('rn_anp_reserva_biologica');
            $table->tinyInteger('rn_anp_parques_nacionales')->default(0)->after('rn_anp_reserva_vida_silvestre');
            $table->tinyInteger('rn_anp_area_privada')->default(0)->after('rn_anp_parques_nacionales');
            $table->tinyInteger('rn_anp_area_comunitaria')->default(0)->after('rn_anp_area_privada');
            $table->tinyInteger('rn_anp_area_conservacion')->default(0)->after('rn_anp_area_recreacion');

            // RN — Cuerpos de Agua (faltaba 1)
            $table->tinyInteger('rn_agua_esteros')->default(0)->after('rn_agua_termas');

            // RC — Artístico Monumental (faltaban 3)
            $table->tinyInteger('rc_am_pueblos_antiguos')->default(0)->after('rc_am_ciudades_coloniales');
            $table->tinyInteger('rc_am_patrimonio_humanidad')->default(0)->after('rc_am_pueblos_antiguos');
            $table->tinyInteger('rc_am_santuarios')->default(0)->after('rc_am_patrimonio_humanidad');

            // RC — Nacionalidades y Pueblos (faltaba 1)
            $table->tinyInteger('rc_np_eventos_historicos')->default(0)->after('rc_np_eventos_folkloricos');

            // RC — Expresiones Contemporáneas (faltaba 1)
            $table->tinyInteger('rc_ec_complejos_industriales')->default(0)->after('rc_ec_plantaciones');

            // PT — Alojamiento (faltaban 6)
            $table->tinyInteger('pt_aloj_lodges')->default(0)->after('pt_aloj_haciendas');
            $table->tinyInteger('pt_aloj_resorts')->default(0)->after('pt_aloj_lodges');
            $table->tinyInteger('pt_aloj_refugios')->default(0)->after('pt_aloj_resorts');
            $table->tinyInteger('pt_aloj_campamentos')->default(0)->after('pt_aloj_refugios');
            $table->tinyInteger('pt_aloj_casa_huespedes')->default(0)->after('pt_aloj_campamentos');
            $table->tinyInteger('pt_aloj_ctc')->default(0)->after('pt_aloj_casa_huespedes');

            // PT — Restauración (faltaban 4)
            $table->tinyInteger('pt_rest_moviles')->default(0)->after('pt_rest_discotecas');
            $table->tinyInteger('pt_rest_plazas_comida')->default(0)->after('pt_rest_moviles');
            $table->tinyInteger('pt_rest_catering')->default(0)->after('pt_rest_plazas_comida');
            $table->tinyInteger('pt_rest_ctc')->default(0)->after('pt_rest_catering');

            // PT — Intermediación (faltaban 4, pt_inter_operadoras ya existe)
            $table->tinyInteger('pt_inter_mayoristas')->default(0)->after('pt_rest_ctc');
            $table->tinyInteger('pt_inter_internacionales')->default(0)->after('pt_inter_mayoristas');
            $table->tinyInteger('pt_inter_duales')->default(0)->after('pt_inter_operadoras');
            $table->tinyInteger('pt_inter_ctc')->default(0)->after('pt_inter_duales');

            // PT — Transportación (faltaban 2)
            $table->tinyInteger('pt_trans_fluvial')->default(0)->after('pt_trans_maritima');
            $table->tinyInteger('pt_trans_ctc')->default(0)->after('pt_trans_fluvial');

            // Tipologías de Turismo (faltaban 28)
            $table->tinyInteger('tt_especializado')->default(0)->after('tt_urbano');
            $table->tinyInteger('tt_rural')->default(0)->after('tt_especializado');
            $table->tinyInteger('tt_agroturismo')->default(0)->after('tt_rural');
            $table->tinyInteger('tt_etnoturismo')->default(0)->after('tt_agroturismo');
            $table->tinyInteger('tt_ecoturismo')->default(0)->after('tt_aventura');
            $table->tinyInteger('tt_negocios')->default(0)->after('tt_ecoturismo');
            $table->tinyInteger('tt_activo')->default(0)->after('tt_gastronomico');
            $table->tinyInteger('tt_vivencial')->default(0)->after('tt_activo');
            $table->tinyInteger('tt_experiencial')->default(0)->after('tt_vivencial');
            $table->tinyInteger('tt_patrimonial')->default(0)->after('tt_experiencial');
            $table->tinyInteger('tt_historico')->default(0)->after('tt_patrimonial');
            $table->tinyInteger('tt_arqueologico')->default(0)->after('tt_historico');
            $table->tinyInteger('tt_arquitectonico')->default(0)->after('tt_arqueologico');
            $table->tinyInteger('tt_literario')->default(0)->after('tt_arquitectonico');
            $table->tinyInteger('tt_astronomico')->default(0)->after('tt_literario');
            $table->tinyInteger('tt_espacial')->default(0)->after('tt_astronomico');
            $table->tinyInteger('tt_compras')->default(0)->after('tt_espacial');
            $table->tinyInteger('tt_enoturismo')->default(0)->after('tt_compras');
            $table->tinyInteger('tt_artistico')->default(0)->after('tt_salud');
            $table->tinyInteger('tt_cinematografico')->default(0)->after('tt_artistico');
            $table->tinyInteger('tt_cinegetico')->default(0)->after('tt_cinematografico');
            $table->tinyInteger('tt_intereses_especiales')->default(0)->after('tt_cinegetico');
            $table->tinyInteger('tt_idiomatico')->default(0)->after('tt_intereses_especiales');
            $table->tinyInteger('tt_cruceros')->default(0)->after('tt_idiomatico');
            $table->tinyInteger('tt_marino_costero')->default(0)->after('tt_cruceros');
            $table->tinyInteger('tt_nautico')->default(0)->after('tt_marino_costero');
            $table->tinyInteger('tt_religioso')->default(0)->after('tt_nautico');
            $table->tinyInteger('tt_social')->default(0)->after('tt_religioso');

            // Infraestructura (faltaban 5)
            $table->tinyInteger('i_alcantarillado')->default(0)->after('i_agua_potable');
            $table->tinyInteger('i_tratamiento_basura')->default(0)->after('i_alcantarillado');
            $table->tinyInteger('i_aguas_residuales')->default(0)->after('i_tratamiento_basura');
            $table->tinyInteger('i_conectividad')->default(0)->after('i_aguas_residuales');
            $table->tinyInteger('i_senalizacion')->default(0)->after('i_conectividad');

            // Afluencia Turística — nuevos nombres (dt_at_* en lugar de dt_flujos_*)
            $table->tinyInteger('dt_at_locales')->default(0);
            $table->tinyInteger('dt_at_regionales')->default(0);
            $table->tinyInteger('dt_at_nacionales')->default(0);
            $table->tinyInteger('dt_at_internacionales')->default(0);
            $table->tinyInteger('dt_at_estadia')->default(0);

            // Marketing Turístico (faltaban 6)
            $table->tinyInteger('dt_mk_publicidad')->default(0)->after('dt_mk_investigacion');
            $table->tinyInteger('dt_mk_comercializacion_tradicional')->default(0)->after('dt_mk_publicidad');
            $table->tinyInteger('dt_mk_comercializacion_digital')->default(0)->after('dt_mk_comercializacion_tradicional');
            $table->tinyInteger('dt_mk_imagen_sitio')->default(0)->after('dt_mk_comercializacion_digital');
            $table->tinyInteger('dt_mk_presencia_digital')->default(0)->after('dt_mk_imagen_sitio');
            $table->tinyInteger('dt_mk_innovacion')->default(0)->after('dt_mk_presencia_digital');

            // Superestructura (faltaban 6)
            $table->tinyInteger('st_participacion_ciudadana')->default(0)->after('st_marco_normativo');
            $table->tinyInteger('st_planificacion_participativa')->default(0)->after('st_participacion_ciudadana');
            $table->tinyInteger('st_entes_publicos')->default(0)->after('st_planificacion_participativa');
            $table->tinyInteger('st_representacion_gremial')->default(0)->after('st_entes_publicos');
            $table->tinyInteger('st_representatividad_comunitaria')->default(0)->after('st_representacion_gremial');
            $table->tinyInteger('st_fomento')->default(0)->after('st_representatividad_comunitaria');
        });

        // 2. Eliminar columnas obsoletas (SQLite >= 3.35 las soporta)
        Schema::table('evaluaciones_potencialidad', function (Blueprint $table) {
            $table->dropColumn($this->toDrop);
        });
    }

    public function down(): void
    {
        // Revertir: volver a agregar las columnas obsoletas y eliminar las nuevas
        Schema::table('evaluaciones_potencialidad', function (Blueprint $table) {
            foreach ($this->toDrop as $col) {
                $table->tinyInteger($col)->default(0);
            }
        });

        Schema::table('evaluaciones_potencialidad', function (Blueprint $table) {
            $table->dropColumn([
                'rn_litoral_actividades_acuaticas','rn_litoral_areas_deserticas',
                'rn_montana_cuevas','rn_montana_geisers','rn_montana_valles','rn_montana_bosques','rn_montana_flora_fauna','rn_montana_areas_deserticas',
                'rn_anp_reserva_biologica','rn_anp_reserva_vida_silvestre','rn_anp_parques_nacionales','rn_anp_area_privada','rn_anp_area_comunitaria','rn_anp_area_conservacion',
                'rn_agua_esteros',
                'rc_am_pueblos_antiguos','rc_am_patrimonio_humanidad','rc_am_santuarios',
                'rc_np_eventos_historicos',
                'rc_ec_complejos_industriales',
                'pt_aloj_lodges','pt_aloj_resorts','pt_aloj_refugios','pt_aloj_campamentos','pt_aloj_casa_huespedes','pt_aloj_ctc',
                'pt_rest_moviles','pt_rest_plazas_comida','pt_rest_catering','pt_rest_ctc',
                'pt_inter_mayoristas','pt_inter_internacionales','pt_inter_duales','pt_inter_ctc',
                'pt_trans_fluvial','pt_trans_ctc',
                'tt_especializado','tt_rural','tt_agroturismo','tt_etnoturismo','tt_ecoturismo','tt_negocios',
                'tt_activo','tt_vivencial','tt_experiencial','tt_patrimonial','tt_historico','tt_arqueologico',
                'tt_arquitectonico','tt_literario','tt_astronomico','tt_espacial','tt_compras','tt_enoturismo',
                'tt_artistico','tt_cinematografico','tt_cinegetico','tt_intereses_especiales','tt_idiomatico',
                'tt_cruceros','tt_marino_costero','tt_nautico','tt_religioso','tt_social',
                'i_alcantarillado','i_tratamiento_basura','i_aguas_residuales','i_conectividad','i_senalizacion',
                'dt_at_locales','dt_at_regionales','dt_at_nacionales','dt_at_internacionales','dt_at_estadia',
                'dt_mk_publicidad','dt_mk_comercializacion_tradicional','dt_mk_comercializacion_digital',
                'dt_mk_imagen_sitio','dt_mk_presencia_digital','dt_mk_innovacion',
                'st_participacion_ciudadana','st_planificacion_participativa','st_entes_publicos',
                'st_representacion_gremial','st_representatividad_comunitaria','st_fomento',
            ]);
        });
    }
};
