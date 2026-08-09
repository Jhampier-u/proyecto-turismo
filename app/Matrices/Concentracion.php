<?php

namespace App\Matrices;

/**
 * Índice de Concentración Turística.
 *
 * GENERADO por database/matrices/generar_concentracion.py
 * desde Documentación/Índice de Concentración Turística.xlsx.
 * No editar a mano: vuelve a ejecutar el generador.
 *
 * Instrumento de Calle Lituma y Chaca Espinoza, sobre Illingworth 2011. A
 * diferencia del resto de matrices del sistema, esta no puntúa criterios en
 * una escala: cuenta establecimientos y subtipos de atractivo por categoría.
 * No tiene escala acotada ni umbral: son conteos, no valoraciones.
 */
final class Concentracion
{
    /**
     * Atractivos turísticos: dos tablas paralelas del instrumento,
     * manifestaciones culturales y atractivos naturales. El bloque de
     * primer nivel es la categoría -la que separa las dos tablas y sus dos
     * porcentajes-; dentro, agrupado por tipo (Arquitectura, Montaña,
     * Folklore...), campo => etiqueta con el subtipo del instrumento.
     *
     * Algunos campos abrevian una palabra larga y recurrente del
     * instrumento ('realizac' por 'realizaciones', 'aconteci' por
     * 'acontecimientos'...) para no pasar el límite de 63 caracteres de un
     * identificador de PostgreSQL -ver ABREVIATURAS en el generador-. Las
     * etiquetas siempre llevan la palabra completa: la abreviatura es del
     * nombre de columna, no del texto que ve el evaluador.
     *
     * @var array<string, array<string, array<string, string>>>
     */
    public const ATRACTIVOS = [
        'Manifestaciones Culturales' => [
            'Arquitectura' => [
                'at_mc_arquitectura_historia_civil_religiosa_militar_vernacula' => 'Historia (civíl, religiosa, militar, vernácula)',
                'at_mc_arquitectura_museos' => 'Museos',
                'at_mc_arquitectura_ciudad_historica_y_o_patrimonial' => 'Ciudad Histórica y/o Patrimonial',
                'at_mc_arquitectura_area_historica' => 'Área Histórica',
                'at_mc_arquitectura_area_patrimonial_y_o_arqueologica' => 'Área Patrimonial y/o Arqueológica',
                'at_mc_arquitectura_monumentos' => 'Monumentos',
                'at_mc_arquitectura_espacio_publico' => 'Espacio Público',
            ],
            'Folklore' => [
                'at_mc_folklore_pueblo_y_o_nacionalidad_etnografia' => 'Pueblo y/o nacionalidad (etnografía)',
                'at_mc_folklore_fiestas_relig_tradic_y_creenc_popul' => 'Fiestas religiosas, tradicionales y creencias populares',
                'at_mc_folklore_artesanias_y_artes' => 'Artesanías y artes',
                'at_mc_folklore_medicina_ancestral' => 'Medicina Ancestral',
                'at_mc_folklore_ferias_y_mercados' => 'Ferias y mercados',
                'at_mc_folklore_musica_y_danza' => 'Música y danza',
                'at_mc_folklore_gastronomia' => 'Gastronomía',
            ],
            'Realizaciones Técnicas y Científicas' => [
                'at_mc_realizac_tec_y_cientif_obras_de_ingenieria' => 'Obras de Ingeniería',
                'at_mc_realizac_tec_y_cientif_centros_de_exhib_de_flora_y_fauna' => 'Centros de exhibición de flora y fauna',
                'at_mc_realizac_tec_y_cientif_explotac_agropec_y_pesq' => 'Explotaciones agropecuarias y pesqueras',
                'at_mc_realizac_tec_y_cientif_explotac_industr' => 'Explotaciones Industriales',
            ],
            'Acontecimientos Programados' => [
                'at_mc_aconteci_program_eventos_artisticos' => 'Eventos Artísticos',
                'at_mc_aconteci_program_convenc_ferias_no_artesan_y_congr' => 'Convenciones, ferias (no artesanales) y congresos',
                'at_mc_aconteci_program_eventos_deportivos' => 'Eventos deportivos',
                'at_mc_aconteci_program_eventos_gastronomicos' => 'Eventos Gastronómicos',
            ],
        ],
        'Atractivos Naturales' => [
            'Montaña' => [
                'at_nat_montana_alta_montana' => 'Alta Montaña',
                'at_nat_montana_media_montana' => 'Media Montaña',
                'at_nat_montana_baja_montana' => 'Baja Montaña',
            ],
            'Planicies' => [
                'at_nat_planicies_llanura' => 'Llanura',
                'at_nat_planicies_salitre' => 'Salitre',
                'at_nat_planicies_valle' => 'Valle',
                'at_nat_planicies_meseta' => 'Meseta',
            ],
            'Desiertos' => [
                'at_nat_desiertos_costero' => 'Costero',
                'at_nat_desiertos_del_interior' => 'Del Interior',
            ],
            'Ambientes Lacústres' => [
                'at_nat_ambientes_lacustres_lago' => 'Lago',
                'at_nat_ambientes_lacustres_laguna' => 'Laguna',
                'at_nat_ambientes_lacustres_pantano' => 'Pantano',
                'at_nat_ambientes_lacustres_poza' => 'Poza',
                'at_nat_ambientes_lacustres_humedal' => 'Humedal',
                'at_nat_ambientes_lacustres_vado' => 'Vado',
                'at_nat_ambientes_lacustres_playa_de_laguna' => 'Playa de Laguna',
            ],
            'Ríos' => [
                'at_nat_rios_rio' => 'Río',
                'at_nat_rios_riachuelo' => 'Riachuelo',
                'at_nat_rios_rapido' => 'Rápido',
                'at_nat_rios_cascada' => 'Cascada',
                'at_nat_rios_ribera' => 'Ribera',
                'at_nat_rios_playa_de_rio' => 'Playa de Río',
                'at_nat_rios_delta' => 'Delta',
            ],
            'Bósques' => [
                'at_nat_bosques_paramo' => 'Páramo',
                'at_nat_bosques_ceja_de_selva' => 'Ceja de Selva',
                'at_nat_bosques_nublado' => 'Nublado',
                'at_nat_bosques_montano_bajo' => 'Montano Bajo',
                'at_nat_bosques_humedo' => 'Húmedo',
                'at_nat_bosques_manglar' => 'Manglar',
                'at_nat_bosques_seco' => 'Seco',
                'at_nat_bosques_petrificado' => 'Petrificado',
            ],
            'Aguas Subterráneas' => [
                'at_nat_aguas_subterraneas_manantial_agua_mineral' => 'Manantial Agua Mineral',
                'at_nat_aguas_subterraneas_manantial_agua_termal' => 'Manantial Agua Termal',
            ],
            'Fenómenos Espeleológicos' => [
                'at_nat_fenomenos_espeleologicos_cueva_o_caverna' => 'Cueva o Caverna',
                'at_nat_fenomenos_espeleologicos_rio_subterraneo' => 'Río Subterráneo',
                'at_nat_fenomenos_espeleologicos_flujo_de_lava' => 'Flujo de Lava',
                'at_nat_fenomenos_espeleologicos_tubo_de_lava' => 'Tubo de Lava',
                'at_nat_fenomenos_espeleologicos_escarpa_de_falla' => 'Escarpa de Falla',
                'at_nat_fenomenos_espeleologicos_canon' => 'Cañón',
                'at_nat_fenomenos_espeleologicos_quebrada' => 'Quebrada',
            ],
            'Costas o Litorales' => [
                'at_nat_costas_o_litorales_playa' => 'Playa',
                'at_nat_costas_o_litorales_acantilado' => 'Acantilado',
                'at_nat_costas_o_litorales_golfo' => 'Golfo',
                'at_nat_costas_o_litorales_bahia' => 'Bahía',
                'at_nat_costas_o_litorales_ensenada' => 'Ensenada',
                'at_nat_costas_o_litorales_canal' => 'Canal',
                'at_nat_costas_o_litorales_estuario' => 'Estuario',
                'at_nat_costas_o_litorales_estero' => 'Estero',
            ],
            'Ambientes Marinos' => [
                'at_nat_ambientes_marinos_arrecife_de_coral' => 'Arrecife de Coral',
                'at_nat_ambientes_marinos_cueva_o_caverna' => 'Cueva o Caverna',
                'at_nat_ambientes_marinos_crater' => 'Cráter',
            ],
            'Tierras Insulares' => [
                'at_nat_tierras_insulares_isla_continental' => 'Isla Continental',
                'at_nat_tierras_insulares_isla_oceanica' => 'Isla Oceánica',
                'at_nat_tierras_insulares_islote' => 'Islote',
                'at_nat_tierras_insulares_roca' => 'Roca',
            ],
        ],
    ];

    /**
     * Planta turística: diez sectores del instrumento, cada uno con su
     * sigla entre paréntesis en el nombre -es la misma que usan sus filas
     * de subtotal en el instrumento y la que prefija sus campos-. Dentro de
     * cada sector, campo => etiqueta con la subcategoría de establecimiento.
     *
     * @var array<string, array<string, string>>
     */
    public const PLANTA = [
        'Alojamiento (AL)' => [
            'pt_al_hotel' => 'Hotel',
            'pt_al_hostal' => 'Hostal',
            'pt_al_hosteria' => 'Hostería',
            'pt_al_hacienda_turistica' => 'Hacienda Turística',
            'pt_al_lodge' => 'Lodge',
            'pt_al_resort' => 'Resort',
            'pt_al_refugio' => 'Refugio',
            'pt_al_campamento_turistico' => 'Campamento Turístico',
            'pt_al_casa_de_huespedes' => 'Casa de Huéspedes',
        ],
        'Restauración (RS)' => [
            'pt_rs_restaurante' => 'Restaurante',
            'pt_rs_cafeteria' => 'Cafetería',
            'pt_rs_bar' => 'Bar',
            'pt_rs_discoteca' => 'Discoteca',
            'pt_rs_establecimientos_moviles' => 'Establecimientos Móviles',
            'pt_rs_plazas_de_comida' => 'Plazas de Comida',
            'pt_rs_servicios_de_catering' => 'Servicios de Catering',
        ],
        'Intermediación Turística (IT)' => [
            'pt_it_agencias_de_viajes_mayoristas' => 'Agencias de Viajes Mayoristas',
            'pt_it_agencias_de_viajes_internacionales' => 'Agencias de Viajes Internacionales',
            'pt_it_operadores_turisticos' => 'Operadores Turísticos',
            'pt_it_agencias_de_viajes_duales' => 'Agencias de Viajes Duales',
        ],
        'Transportación Turística (TT)' => [
            'pt_tt_transportacion_turistica_terrestre' => 'Transportación Turística Terrestre',
            'pt_tt_transportacion_turistica_fluvial' => 'Transportación Turística Fluvial',
        ],
        'Guianza Turística (GT)' => [
            'pt_gt_guias_locales' => 'Guías Locales',
            'pt_gt_guias_nacionales' => 'Guías Nacionales',
            'pt_gt_guias_nacionales_especializados_en_patrimonio_turistico' => 'Guías Nacionales Especializados en Patrimonio Turístico',
            'pt_gt_guias_nacionales_especializados_en_aventura' => 'Guías Nacionales Especializados en Aventura',
        ],
        'Organizadores de Eventos, congresos y convenciones, reuniones, incentivos, conferencias, ferias y exhibiciones. (OE)' => [
            'pt_oe_organizadores_de_eventos_registrados' => 'Organizadores de eventos registrados',
        ],
        'Centros de convenciones, salas de recepciones y salas de banquetes (CC).' => [
            'pt_cc_centros_de_convenc' => 'Centros de Convenciones',
            'pt_cc_sala_de_recepciones' => 'Sala de Recepciones',
            'pt_cc_salas_de_banquetes' => 'Salas de Banquetes',
        ],
        'Centros de Turismo Comunitario (CTC)' => [
            'pt_ctc_centros_de_turismo_comunitario' => 'Centros de Turismo Comunitario',
        ],
        'Parques Temáticos y Atracciones (PA)' => [
            'pt_pa_parques_tematicos' => 'Parques Temáticos',
            'pt_pa_atracciones_permanentes' => 'Atracciones Permanentes',
        ],
        'Balnearios, termas y centros de recreación turística. (BTC)' => [
            'pt_btc_balnearios' => 'Balnearios',
            'pt_btc_termas' => 'Termas',
            'pt_btc_centros_de_recreacion_turistica' => 'Centros de Recreación Turística',
        ],
    ];

    /** @return array<int, string> los 113 nombres de campo, en el orden del instrumento */
    public static function campos(): array
    {
        $campos = [];
        foreach (self::ATRACTIVOS as $tipos) {
            foreach ($tipos as $mapa) {
                $campos = array_merge($campos, array_keys($mapa));
            }
        }
        foreach (self::PLANTA as $mapa) {
            $campos = array_merge($campos, array_keys($mapa));
        }

        return $campos;
    }

    /**
     * campo => etiqueta de los 113 campos, aplanando ATRACTIVOS y PLANTA.
     *
     * Es lo que EvaluacionConcentracionController expone como etiquetas() de
     * validación (ver MatrizPonderadaController::etiquetas()): así un mensaje
     * de "campo obligatorio" nombra "Cascada", no `at_nat_rios_cascada`, sin
     * copiar el texto a ningún otro sitio -se deriva de ATRACTIVOS y PLANTA
     * en cada llamada, igual que campos()-.
     *
     * @return array<string, string>
     */
    public static function etiquetas(): array
    {
        $etiquetas = [];
        foreach (self::ATRACTIVOS as $tipos) {
            foreach ($tipos as $mapa) {
                $etiquetas += $mapa;
            }
        }
        foreach (self::PLANTA as $mapa) {
            $etiquetas += $mapa;
        }

        return $etiquetas;
    }
}
