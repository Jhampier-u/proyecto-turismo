<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice de Concentración Turística: 113 conteos en dos bloques —atractivos
 * (77, en dos tablas paralelas del instrumento: manifestaciones culturales y
 * atractivos naturales) y planta turística (36, en diez sectores)—. A
 * diferencia del resto de matrices del sistema, esta no puntúa criterios en
 * una escala: cuenta establecimientos y subtipos de atractivo.
 *
 * Los 113 nombres se escriben literalmente y NO se derivan en tiempo de
 * ejecución de App\Matrices\Concentracion::campos(): esa clase está GENERADA
 * desde el Excel (ver su cabecera y database/matrices/generar_concentracion.py)
 * y una migración no debe importar código de app/, y menos código generado.
 * Es la misma razón documentada en la cabecera de
 * 2026_08_06_000005_create_evaluaciones_valoracion_territorial_table.php: una
 * migración es un registro histórico congelado, y si el generador cambiara un
 * nombre el día de mañana, una base nueva y una ya migrada acabarían con
 * esquemas distintos sin que nada lo avisara. Los nombres de abajo se
 * derivaron de campos() al escribir este fichero -con un script de un solo
 * uso, no en cada ejecución- y se verificaron contra
 * count(Concentracion::campos()) === 113 antes de pegarlos aquí.
 *
 * unsignedInteger y no tinyInteger: son conteos de establecimientos y
 * subtipos, no puntuaciones de una escala corta -una zona puede tener
 * trescientos hoteles-. Nullable y sin defecto, como el resto de matrices
 * desde el guardado parcial: un subtipo sin contar no es un cero, y aquí el 0
 * significa "no hay ninguno", que es un dato real y no una ausencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_concentracion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->unique()->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');

            // ── ATRACTIVOS TURÍSTICOS (77) ──────────────────────────────────
            // Manifestaciones Culturales — 22 subtipos en 4 tipos.
            // Arquitectura (7).
            $table->unsignedInteger('at_mc_arquitectura_historia_civil_religiosa_militar_vernacula')->nullable();
            $table->unsignedInteger('at_mc_arquitectura_museos')->nullable();
            $table->unsignedInteger('at_mc_arquitectura_ciudad_historica_y_o_patrimonial')->nullable();
            $table->unsignedInteger('at_mc_arquitectura_area_historica')->nullable();
            $table->unsignedInteger('at_mc_arquitectura_area_patrimonial_y_o_arqueologica')->nullable();
            $table->unsignedInteger('at_mc_arquitectura_monumentos')->nullable();
            $table->unsignedInteger('at_mc_arquitectura_espacio_publico')->nullable();

            // Folklore (7).
            $table->unsignedInteger('at_mc_folklore_pueblo_y_o_nacionalidad_etnografia')->nullable();
            $table->unsignedInteger('at_mc_folklore_fiestas_relig_tradic_y_creenc_popul')->nullable();
            $table->unsignedInteger('at_mc_folklore_artesanias_y_artes')->nullable();
            $table->unsignedInteger('at_mc_folklore_medicina_ancestral')->nullable();
            $table->unsignedInteger('at_mc_folklore_ferias_y_mercados')->nullable();
            $table->unsignedInteger('at_mc_folklore_musica_y_danza')->nullable();
            $table->unsignedInteger('at_mc_folklore_gastronomia')->nullable();

            // Realizaciones Técnicas y Científicas (4).
            $table->unsignedInteger('at_mc_realizac_tec_y_cientif_obras_de_ingenieria')->nullable();
            $table->unsignedInteger('at_mc_realizac_tec_y_cientif_centros_de_exhib_de_flora_y_fauna')->nullable();
            $table->unsignedInteger('at_mc_realizac_tec_y_cientif_explotac_agropec_y_pesq')->nullable();
            $table->unsignedInteger('at_mc_realizac_tec_y_cientif_explotac_industr')->nullable();

            // Acontecimientos Programados (4).
            $table->unsignedInteger('at_mc_aconteci_program_eventos_artisticos')->nullable();
            $table->unsignedInteger('at_mc_aconteci_program_convenc_ferias_no_artesan_y_congr')->nullable();
            $table->unsignedInteger('at_mc_aconteci_program_eventos_deportivos')->nullable();
            $table->unsignedInteger('at_mc_aconteci_program_eventos_gastronomicos')->nullable();

            // Atractivos Naturales — 55 subtipos en 11 tipos.
            // Montaña (3).
            $table->unsignedInteger('at_nat_montana_alta_montana')->nullable();
            $table->unsignedInteger('at_nat_montana_media_montana')->nullable();
            $table->unsignedInteger('at_nat_montana_baja_montana')->nullable();

            // Planicies (4).
            $table->unsignedInteger('at_nat_planicies_llanura')->nullable();
            $table->unsignedInteger('at_nat_planicies_salitre')->nullable();
            $table->unsignedInteger('at_nat_planicies_valle')->nullable();
            $table->unsignedInteger('at_nat_planicies_meseta')->nullable();

            // Desiertos (2).
            $table->unsignedInteger('at_nat_desiertos_costero')->nullable();
            $table->unsignedInteger('at_nat_desiertos_del_interior')->nullable();

            // Ambientes Lacústres (7).
            $table->unsignedInteger('at_nat_ambientes_lacustres_lago')->nullable();
            $table->unsignedInteger('at_nat_ambientes_lacustres_laguna')->nullable();
            $table->unsignedInteger('at_nat_ambientes_lacustres_pantano')->nullable();
            $table->unsignedInteger('at_nat_ambientes_lacustres_poza')->nullable();
            $table->unsignedInteger('at_nat_ambientes_lacustres_humedal')->nullable();
            $table->unsignedInteger('at_nat_ambientes_lacustres_vado')->nullable();
            $table->unsignedInteger('at_nat_ambientes_lacustres_playa_de_laguna')->nullable();

            // Ríos (7).
            $table->unsignedInteger('at_nat_rios_rio')->nullable();
            $table->unsignedInteger('at_nat_rios_riachuelo')->nullable();
            $table->unsignedInteger('at_nat_rios_rapido')->nullable();
            $table->unsignedInteger('at_nat_rios_cascada')->nullable();
            $table->unsignedInteger('at_nat_rios_ribera')->nullable();
            $table->unsignedInteger('at_nat_rios_playa_de_rio')->nullable();
            $table->unsignedInteger('at_nat_rios_delta')->nullable();

            // Bósques (8).
            $table->unsignedInteger('at_nat_bosques_paramo')->nullable();
            $table->unsignedInteger('at_nat_bosques_ceja_de_selva')->nullable();
            $table->unsignedInteger('at_nat_bosques_nublado')->nullable();
            $table->unsignedInteger('at_nat_bosques_montano_bajo')->nullable();
            $table->unsignedInteger('at_nat_bosques_humedo')->nullable();
            $table->unsignedInteger('at_nat_bosques_manglar')->nullable();
            $table->unsignedInteger('at_nat_bosques_seco')->nullable();
            $table->unsignedInteger('at_nat_bosques_petrificado')->nullable();

            // Aguas Subterráneas (2).
            $table->unsignedInteger('at_nat_aguas_subterraneas_manantial_agua_mineral')->nullable();
            $table->unsignedInteger('at_nat_aguas_subterraneas_manantial_agua_termal')->nullable();

            // Fenómenos Espeleológicos (7).
            $table->unsignedInteger('at_nat_fenomenos_espeleologicos_cueva_o_caverna')->nullable();
            $table->unsignedInteger('at_nat_fenomenos_espeleologicos_rio_subterraneo')->nullable();
            $table->unsignedInteger('at_nat_fenomenos_espeleologicos_flujo_de_lava')->nullable();
            $table->unsignedInteger('at_nat_fenomenos_espeleologicos_tubo_de_lava')->nullable();
            $table->unsignedInteger('at_nat_fenomenos_espeleologicos_escarpa_de_falla')->nullable();
            $table->unsignedInteger('at_nat_fenomenos_espeleologicos_canon')->nullable();
            $table->unsignedInteger('at_nat_fenomenos_espeleologicos_quebrada')->nullable();

            // Costas o Litorales (8).
            $table->unsignedInteger('at_nat_costas_o_litorales_playa')->nullable();
            $table->unsignedInteger('at_nat_costas_o_litorales_acantilado')->nullable();
            $table->unsignedInteger('at_nat_costas_o_litorales_golfo')->nullable();
            $table->unsignedInteger('at_nat_costas_o_litorales_bahia')->nullable();
            $table->unsignedInteger('at_nat_costas_o_litorales_ensenada')->nullable();
            $table->unsignedInteger('at_nat_costas_o_litorales_canal')->nullable();
            $table->unsignedInteger('at_nat_costas_o_litorales_estuario')->nullable();
            $table->unsignedInteger('at_nat_costas_o_litorales_estero')->nullable();

            // Ambientes Marinos (3).
            $table->unsignedInteger('at_nat_ambientes_marinos_arrecife_de_coral')->nullable();
            $table->unsignedInteger('at_nat_ambientes_marinos_cueva_o_caverna')->nullable();
            $table->unsignedInteger('at_nat_ambientes_marinos_crater')->nullable();

            // Tierras Insulares (4).
            $table->unsignedInteger('at_nat_tierras_insulares_isla_continental')->nullable();
            $table->unsignedInteger('at_nat_tierras_insulares_isla_oceanica')->nullable();
            $table->unsignedInteger('at_nat_tierras_insulares_islote')->nullable();
            $table->unsignedInteger('at_nat_tierras_insulares_roca')->nullable();

            // ── PLANTA TURÍSTICA (36) ───────────────────────────────────────
            // Alojamiento (AL) — 9.
            $table->unsignedInteger('pt_al_hotel')->nullable();
            $table->unsignedInteger('pt_al_hostal')->nullable();
            $table->unsignedInteger('pt_al_hosteria')->nullable();
            $table->unsignedInteger('pt_al_hacienda_turistica')->nullable();
            $table->unsignedInteger('pt_al_lodge')->nullable();
            $table->unsignedInteger('pt_al_resort')->nullable();
            $table->unsignedInteger('pt_al_refugio')->nullable();
            $table->unsignedInteger('pt_al_campamento_turistico')->nullable();
            $table->unsignedInteger('pt_al_casa_de_huespedes')->nullable();

            // Restauración (RS) — 7.
            $table->unsignedInteger('pt_rs_restaurante')->nullable();
            $table->unsignedInteger('pt_rs_cafeteria')->nullable();
            $table->unsignedInteger('pt_rs_bar')->nullable();
            $table->unsignedInteger('pt_rs_discoteca')->nullable();
            $table->unsignedInteger('pt_rs_establecimientos_moviles')->nullable();
            $table->unsignedInteger('pt_rs_plazas_de_comida')->nullable();
            $table->unsignedInteger('pt_rs_servicios_de_catering')->nullable();

            // Intermediación Turística (IT) — 4.
            $table->unsignedInteger('pt_it_agencias_de_viajes_mayoristas')->nullable();
            $table->unsignedInteger('pt_it_agencias_de_viajes_internacionales')->nullable();
            $table->unsignedInteger('pt_it_operadores_turisticos')->nullable();
            $table->unsignedInteger('pt_it_agencias_de_viajes_duales')->nullable();

            // Transportación Turística (TT) — 2.
            $table->unsignedInteger('pt_tt_transportacion_turistica_terrestre')->nullable();
            $table->unsignedInteger('pt_tt_transportacion_turistica_fluvial')->nullable();

            // Guianza Turística (GT) — 4.
            $table->unsignedInteger('pt_gt_guias_locales')->nullable();
            $table->unsignedInteger('pt_gt_guias_nacionales')->nullable();
            $table->unsignedInteger('pt_gt_guias_nacionales_especializados_en_patrimonio_turistico')->nullable();
            $table->unsignedInteger('pt_gt_guias_nacionales_especializados_en_aventura')->nullable();

            // Organizadores de Eventos, congresos y convenciones, reuniones,
            // incentivos, conferencias, ferias y exhibiciones (OE) — 1.
            $table->unsignedInteger('pt_oe_organizadores_de_eventos_registrados')->nullable();

            // Centros de convenciones, salas de recepciones y salas de
            // banquetes (CC) — 3.
            $table->unsignedInteger('pt_cc_centros_de_convenc')->nullable();
            $table->unsignedInteger('pt_cc_sala_de_recepciones')->nullable();
            $table->unsignedInteger('pt_cc_salas_de_banquetes')->nullable();

            // Centros de Turismo Comunitario (CTC) — 1.
            $table->unsignedInteger('pt_ctc_centros_de_turismo_comunitario')->nullable();

            // Parques Temáticos y Atracciones (PA) — 2.
            $table->unsignedInteger('pt_pa_parques_tematicos')->nullable();
            $table->unsignedInteger('pt_pa_atracciones_permanentes')->nullable();

            // Balnearios, termas y centros de recreación turística (BTC) — 3.
            $table->unsignedInteger('pt_btc_balnearios')->nullable();
            $table->unsignedInteger('pt_btc_termas')->nullable();
            $table->unsignedInteger('pt_btc_centros_de_recreacion_turistica')->nullable();

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_concentracion');
    }
};
