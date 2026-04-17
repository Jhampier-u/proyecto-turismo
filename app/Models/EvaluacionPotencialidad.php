<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionPotencialidad extends Model
{
    protected $table = 'evaluaciones_potencialidad';

    protected $fillable = [
        'zona_id', 'user_id', 'estado',

        // ── RN — Zonas de Litoral ──────────────────────────────────────────
        'rn_litoral_playas',
        'rn_litoral_arrecifes',
        'rn_litoral_cuevas',
        'rn_litoral_flora_fauna',
        'rn_litoral_actividades_acuaticas',
        'rn_litoral_areas_deserticas',

        // ── RN — Zonas de Montaña ──────────────────────────────────────────
        'rn_montana_montanas',
        'rn_montana_sierras',
        'rn_montana_canadas',
        'rn_montana_canones',
        'rn_montana_cuevas',
        'rn_montana_geisers',
        'rn_montana_volcanes',
        'rn_montana_valles',
        'rn_montana_bosques',
        'rn_montana_flora_fauna',
        'rn_montana_areas_deserticas',

        // ── RN — Áreas Naturales Protegidas ───────────────────────────────
        'rn_anp_reservas_marinas',
        'rn_anp_reserva_geobotanica',
        'rn_anp_reserva_ecologica',
        'rn_anp_reserva_fauna',
        'rn_anp_reserva_biologica',
        'rn_anp_reserva_vida_silvestre',
        'rn_anp_parques_nacionales',
        'rn_anp_area_privada',
        'rn_anp_area_comunitaria',
        'rn_anp_area_recreacion',
        'rn_anp_area_conservacion',

        // ── RN — Cuerpos de Agua ───────────────────────────────────────────
        'rn_agua_lagos',
        'rn_agua_rios',
        'rn_agua_cascadas',
        'rn_agua_termas',
        'rn_agua_esteros',

        // ── RC — Artístico Monumental ──────────────────────────────────────
        'rc_am_zonas_arqueologicas',
        'rc_am_fosiles',
        'rc_am_pinturas_rupestres',
        'rc_am_ciudades_coloniales',
        'rc_am_pueblos_antiguos',
        'rc_am_patrimonio_humanidad',
        'rc_am_santuarios',

        // ── RC — Nacionalidades y Pueblos ──────────────────────────────────
        'rc_np_grupos_etnicos',
        'rc_np_expresiones_artisticas',
        'rc_np_ferias_mercados',
        'rc_np_eventos_folkloricos',
        'rc_np_eventos_historicos',

        // ── RC — Expresiones Contemporáneas ───────────────────────────────
        'rc_ec_obras_arte',
        'rc_ec_centros_cientificos',
        'rc_ec_explotaciones_mineras',
        'rc_ec_plantaciones',
        'rc_ec_complejos_industriales',

        // ── PT — Alojamiento ───────────────────────────────────────────────
        'pt_aloj_hoteles',
        'pt_aloj_hostales',
        'pt_aloj_hosterias',
        'pt_aloj_haciendas',
        'pt_aloj_lodges',
        'pt_aloj_resorts',
        'pt_aloj_refugios',
        'pt_aloj_campamentos',
        'pt_aloj_casa_huespedes',
        'pt_aloj_ctc',

        // ── PT — Restauración ──────────────────────────────────────────────
        'pt_rest_restaurantes',
        'pt_rest_cafeterias',
        'pt_rest_bares',
        'pt_rest_discotecas',
        'pt_rest_moviles',
        'pt_rest_plazas_comida',
        'pt_rest_catering',
        'pt_rest_ctc',

        // ── PT — Intermediación ────────────────────────────────────────────
        'pt_inter_mayoristas',
        'pt_inter_internacionales',
        'pt_inter_operadoras',
        'pt_inter_duales',
        'pt_inter_ctc',

        // ── PT — Transportación ────────────────────────────────────────────
        'pt_trans_terrestre',
        'pt_trans_ferroviaria',
        'pt_trans_aerea',
        'pt_trans_maritima',
        'pt_trans_fluvial',
        'pt_trans_ctc',

        // ── PT — Interpretación / Guianza ──────────────────────────────────
        'pt_guia_locales',
        'pt_guia_nacionales',
        'pt_guia_patrimonio',
        'pt_guia_aventura',

        // ── Tipologías de Turismo ──────────────────────────────────────────
        'tt_naturaleza',
        'tt_sol_playa',
        'tt_cultural',
        'tt_urbano',
        'tt_especializado',
        'tt_rural',
        'tt_agroturismo',
        'tt_etnoturismo',
        'tt_aventura',
        'tt_deportivo',
        'tt_ecoturismo',
        'tt_negocios',
        'tt_gastronomico',
        'tt_activo',
        'tt_vivencial',
        'tt_experiencial',
        'tt_patrimonial',
        'tt_historico',
        'tt_arqueologico',
        'tt_arquitectonico',
        'tt_literario',
        'tt_astronomico',
        'tt_espacial',
        'tt_compras',
        'tt_enoturismo',
        'tt_salud',
        'tt_artistico',
        'tt_cinematografico',
        'tt_cinegetico',
        'tt_intereses_especiales',
        'tt_idiomatico',
        'tt_cruceros',
        'tt_marino_costero',
        'tt_nautico',
        'tt_religioso',
        'tt_social',
        'tt_comunitario',

        // ── Infraestructura ────────────────────────────────────────────────
        'i_transporte',
        'i_vialidad',
        'i_comunicaciones',
        'i_salud',
        'i_energia',
        'i_agua_potable',
        'i_alcantarillado',
        'i_tratamiento_basura',
        'i_aguas_residuales',
        'i_conectividad',
        'i_senalizacion',

        // ── Afluencia Turística ────────────────────────────────────────────
        'dt_at_locales',
        'dt_at_regionales',
        'dt_at_nacionales',
        'dt_at_internacionales',
        'dt_at_estadia',

        // ── Marketing Turístico ────────────────────────────────────────────
        'dt_mk_organismo_promotor',
        'dt_mk_plan_marketing',
        'dt_mk_tendencias_mercado',
        'dt_mk_investigacion',
        'dt_mk_publicidad',
        'dt_mk_comercializacion_tradicional',
        'dt_mk_comercializacion_digital',
        'dt_mk_imagen_sitio',
        'dt_mk_presencia_digital',
        'dt_mk_innovacion',

        // ── Superestructura ────────────────────────────────────────────────
        'st_politica_publica',
        'st_modelo_gestion',
        'st_actores',
        'st_marco_normativo',
        'st_participacion_ciudadana',
        'st_planificacion_participativa',
        'st_entes_publicos',
        'st_representacion_gremial',
        'st_representatividad_comunitaria',
        'st_fomento',

        // ── Resultados calculados — Factores Endógenos ─────────────────────
        'val_rn_litoral',
        'val_rn_montana',
        'val_rn_anp',
        'val_rn_agua',
        'val_recursos_naturales',
        'val_rc_am',
        'val_rc_np',
        'val_rc_ec',
        'val_recursos_culturales',
        'val_recursos_turisticos',
        'val_pt_alojamiento',
        'val_pt_restauracion',
        'val_pt_intermediacion',
        'val_pt_transportacion',
        'val_pt_interpretacion',
        'val_planta_turistica',
        'val_tipologias',
        'val_infraestructura',
        'fn_total',

        // ── Resultados calculados — Factores Exógenos ─────────────────────
        'val_afluencia',
        'val_marketing',
        'val_superestructura',
        'fx_total',
    ];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
