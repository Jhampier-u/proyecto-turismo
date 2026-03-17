<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionPotencialidad extends Model
{
    protected $table = 'evaluaciones_potencialidad';

    protected $fillable = [
        'zona_id', 'user_id', 'estado',

        // Recursos Naturales - Litoral
        'rn_litoral_playas', 'rn_litoral_arrecifes', 'rn_litoral_cuevas', 'rn_litoral_flora_fauna',
        // Recursos Naturales - Montaña
        'rn_montana_montanas', 'rn_montana_sierras', 'rn_montana_canadas', 'rn_montana_canones',
        'rn_montana_volcanes', 'rn_montana_paramos',
        // Recursos Naturales - ANP
        'rn_anp_reservas_marinas', 'rn_anp_reserva_geobotanica', 'rn_anp_reserva_ecologica',
        'rn_anp_reserva_fauna', 'rn_anp_parque_nacional', 'rn_anp_area_recreacion',
        // Recursos Naturales - Cuerpos de Agua
        'rn_agua_lagos', 'rn_agua_rios', 'rn_agua_cascadas', 'rn_agua_termas',

        // Recursos Culturales - Artístico Monumental
        'rc_am_zonas_arqueologicas', 'rc_am_fosiles', 'rc_am_pinturas_rupestres',
        'rc_am_ciudades_coloniales', 'rc_am_arquitectura_religiosa', 'rc_am_museos',
        // Recursos Culturales - Nacionalidades y Pueblos
        'rc_np_grupos_etnicos', 'rc_np_expresiones_artisticas', 'rc_np_ferias_mercados', 'rc_np_eventos_folkloricos',
        // Recursos Culturales - Expresiones Contemporáneas
        'rc_ec_obras_arte', 'rc_ec_centros_cientificos', 'rc_ec_explotaciones_mineras', 'rc_ec_plantaciones',

        // Planta Turística
        'pt_aloj_hoteles', 'pt_aloj_hostales', 'pt_aloj_hosterias', 'pt_aloj_haciendas', 'pt_aloj_cabanas', 'pt_aloj_glamping',
        'pt_rest_restaurantes', 'pt_rest_cafeterias', 'pt_rest_bares', 'pt_rest_discotecas',
        'pt_inter_agencias_mayoristas', 'pt_inter_agencias_internacionales', 'pt_inter_operadoras', 'pt_inter_agencias_duales',
        'pt_trans_terrestre', 'pt_trans_ferroviaria', 'pt_trans_aerea', 'pt_trans_maritima',
        'pt_guia_locales', 'pt_guia_nacionales', 'pt_guia_patrimonio', 'pt_guia_aventura',

        // Tipologías
        'tt_naturaleza', 'tt_sol_playa', 'tt_cultural', 'tt_urbano', 'tt_comunitario',
        'tt_aventura', 'tt_gastronomico', 'tt_salud', 'tt_deportivo', 'tt_cientifico',

        // Infraestructura
        'i_transporte', 'i_vialidad', 'i_comunicaciones', 'i_salud', 'i_energia', 'i_agua_potable',

        // Demanda - Afluencia
        'dt_flujos_locales', 'dt_flujos_regionales', 'dt_flujos_nacionales', 'dt_flujos_internacionales',
        // Demanda - Marketing
        'dt_mk_organismo_promotor', 'dt_mk_plan_marketing', 'dt_mk_tendencias_mercado',
        'dt_mk_investigacion', 'dt_mk_redes_sociales',
        // Superestructura
        'st_politica_publica', 'st_modelo_gestion', 'st_actores', 'st_marco_normativo', 'st_planificacion',

        // Resultados FN
        'val_rn_litoral', 'val_rn_montana', 'val_rn_anp', 'val_rn_agua',
        'val_recursos_naturales', 'val_rc_am', 'val_rc_np', 'val_rc_ec',
        'val_recursos_culturales', 'val_recursos_turisticos',
        'val_pt_alojamiento', 'val_pt_restauracion', 'val_pt_intermediacion',
        'val_pt_transportacion', 'val_pt_interpretacion', 'val_planta_turistica',
        'val_tipologias', 'val_infraestructura', 'fn_total',

        // Resultados FX
        'val_afluencia', 'val_marketing', 'val_superestructura', 'fx_total',
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
