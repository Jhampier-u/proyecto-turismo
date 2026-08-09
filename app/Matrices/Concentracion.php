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
     * manifestaciones culturales y atractivos naturales, cada una agrupada
     * por su categoría. Dentro de cada categoría, campo => etiqueta con el
     * tipo y el subtipo del instrumento ('Arquitectura · Museos').
     *
     * @var array<string, array<string, string>>
     */
    public const ATRACTIVOS = [
        'Manifestaciones Culturales' => [
            'at_mc_arquitectura_historia_civil_religiosa_militar_vernacula' => 'Arquitectura · Historia (civíl, religiosa, militar, vernácula)',
            'at_mc_arquitectura_museos' => 'Arquitectura · Museos',
            'at_mc_arquitectura_ciudad_historica_y_o_patrimonial' => 'Arquitectura · Ciudad Histórica y/o Patrimonial',
            'at_mc_arquitectura_area_historica' => 'Arquitectura · Área Histórica',
            'at_mc_arquitectura_area_patrimonial_y_o_arqueologica' => 'Arquitectura · Área Patrimonial y/o Arqueológica',
            'at_mc_arquitectura_monumentos' => 'Arquitectura · Monumentos',
            'at_mc_arquitectura_espacio_publico' => 'Arquitectura · Espacio Público',
            'at_mc_folklore_pueblo_y_o_nacionalidad_etnografia' => 'Folklore · Pueblo y/o nacionalidad (etnografía)',
            'at_mc_folklore_fiestas_religiosas_tradicionales_y_creencias_populares' => 'Folklore · Fiestas religiosas, tradicionales y creencias populares',
            'at_mc_folklore_artesanias_y_artes' => 'Folklore · Artesanías y artes',
            'at_mc_folklore_medicina_ancestral' => 'Folklore · Medicina Ancestral',
            'at_mc_folklore_ferias_y_mercados' => 'Folklore · Ferias y mercados',
            'at_mc_folklore_musica_y_danza' => 'Folklore · Música y danza',
            'at_mc_folklore_gastronomia' => 'Folklore · Gastronomía',
            'at_mc_realizaciones_tecnicas_y_cientificas_obras_de_ingenieria' => 'Realizaciones Técnicas y Científicas · Obras de Ingeniería',
            'at_mc_realizaciones_tecnicas_y_cientificas_centros_de_exhibicion_de_flora_y_fauna' => 'Realizaciones Técnicas y Científicas · Centros de exhibición de flora y fauna',
            'at_mc_realizaciones_tecnicas_y_cientificas_explotaciones_agropecuarias_y_pesqueras' => 'Realizaciones Técnicas y Científicas · Explotaciones agropecuarias y pesqueras',
            'at_mc_realizaciones_tecnicas_y_cientificas_explotaciones_industriales' => 'Realizaciones Técnicas y Científicas · Explotaciones Industriales',
            'at_mc_acontecimientos_programados_eventos_artisticos' => 'Acontecimientos Programados · Eventos Artísticos',
            'at_mc_acontecimientos_programados_convenciones_ferias_no_artesanales_y_congresos' => 'Acontecimientos Programados · Convenciones, ferias (no artesanales) y congresos',
            'at_mc_acontecimientos_programados_eventos_deportivos' => 'Acontecimientos Programados · Eventos deportivos',
            'at_mc_acontecimientos_programados_eventos_gastronomicos' => 'Acontecimientos Programados · Eventos Gastronómicos',
        ],
        'Atractivos Naturales' => [
            'at_nat_montana_alta_montana' => 'Montaña · Alta Montaña',
            'at_nat_montana_media_montana' => 'Montaña · Media Montaña',
            'at_nat_montana_baja_montana' => 'Montaña · Baja Montaña',
            'at_nat_planicies_llanura' => 'Planicies · Llanura',
            'at_nat_planicies_salitre' => 'Planicies · Salitre',
            'at_nat_planicies_valle' => 'Planicies · Valle',
            'at_nat_planicies_meseta' => 'Planicies · Meseta',
            'at_nat_desiertos_costero' => 'Desiertos · Costero',
            'at_nat_desiertos_del_interior' => 'Desiertos · Del Interior',
            'at_nat_ambientes_lacustres_lago' => 'Ambientes Lacústres · Lago',
            'at_nat_ambientes_lacustres_laguna' => 'Ambientes Lacústres · Laguna',
            'at_nat_ambientes_lacustres_pantano' => 'Ambientes Lacústres · Pantano',
            'at_nat_ambientes_lacustres_poza' => 'Ambientes Lacústres · Poza',
            'at_nat_ambientes_lacustres_humedal' => 'Ambientes Lacústres · Humedal',
            'at_nat_ambientes_lacustres_vado' => 'Ambientes Lacústres · Vado',
            'at_nat_ambientes_lacustres_playa_de_laguna' => 'Ambientes Lacústres · Playa de Laguna',
            'at_nat_rios_rio' => 'Ríos · Río',
            'at_nat_rios_riachuelo' => 'Ríos · Riachuelo',
            'at_nat_rios_rapido' => 'Ríos · Rápido',
            'at_nat_rios_cascada' => 'Ríos · Cascada',
            'at_nat_rios_ribera' => 'Ríos · Ribera',
            'at_nat_rios_playa_de_rio' => 'Ríos · Playa de Río',
            'at_nat_rios_delta' => 'Ríos · Delta',
            'at_nat_bosques_paramo' => 'Bósques · Páramo',
            'at_nat_bosques_ceja_de_selva' => 'Bósques · Ceja de Selva',
            'at_nat_bosques_nublado' => 'Bósques · Nublado',
            'at_nat_bosques_montano_bajo' => 'Bósques · Montano Bajo',
            'at_nat_bosques_humedo' => 'Bósques · Húmedo',
            'at_nat_bosques_manglar' => 'Bósques · Manglar',
            'at_nat_bosques_seco' => 'Bósques · Seco',
            'at_nat_bosques_petrificado' => 'Bósques · Petrificado',
            'at_nat_aguas_subterraneas_manantial_agua_mineral' => 'Aguas Subterráneas · Manantial Agua Mineral',
            'at_nat_aguas_subterraneas_manantial_agua_termal' => 'Aguas Subterráneas · Manantial Agua Termal',
            'at_nat_fenomenos_espeleologicos_cueva_o_caverna' => 'Fenómenos Espeleológicos · Cueva o Caverna',
            'at_nat_fenomenos_espeleologicos_rio_subterraneo' => 'Fenómenos Espeleológicos · Río Subterráneo',
            'at_nat_fenomenos_espeleologicos_flujo_de_lava' => 'Fenómenos Espeleológicos · Flujo de Lava',
            'at_nat_fenomenos_espeleologicos_tubo_de_lava' => 'Fenómenos Espeleológicos · Tubo de Lava',
            'at_nat_fenomenos_espeleologicos_escarpa_de_falla' => 'Fenómenos Espeleológicos · Escarpa de Falla',
            'at_nat_fenomenos_espeleologicos_canon' => 'Fenómenos Espeleológicos · Cañón',
            'at_nat_fenomenos_espeleologicos_quebrada' => 'Fenómenos Espeleológicos · Quebrada',
            'at_nat_costas_o_litorales_playa' => 'Costas o Litorales · Playa',
            'at_nat_costas_o_litorales_acantilado' => 'Costas o Litorales · Acantilado',
            'at_nat_costas_o_litorales_golfo' => 'Costas o Litorales · Golfo',
            'at_nat_costas_o_litorales_bahia' => 'Costas o Litorales · Bahía',
            'at_nat_costas_o_litorales_ensenada' => 'Costas o Litorales · Ensenada',
            'at_nat_costas_o_litorales_canal' => 'Costas o Litorales · Canal',
            'at_nat_costas_o_litorales_estuario' => 'Costas o Litorales · Estuario',
            'at_nat_costas_o_litorales_estero' => 'Costas o Litorales · Estero',
            'at_nat_ambientes_marinos_arrecife_de_coral' => 'Ambientes Marinos · Arrecife de Coral',
            'at_nat_ambientes_marinos_cueva_o_caverna' => 'Ambientes Marinos · Cueva o Caverna',
            'at_nat_ambientes_marinos_crater' => 'Ambientes Marinos · Cráter',
            'at_nat_tierras_insulares_isla_continental' => 'Tierras Insulares · Isla Continental',
            'at_nat_tierras_insulares_isla_oceanica' => 'Tierras Insulares · Isla Oceánica',
            'at_nat_tierras_insulares_islote' => 'Tierras Insulares · Islote',
            'at_nat_tierras_insulares_roca' => 'Tierras Insulares · Roca',
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
            'pt_cc_centros_de_convenciones' => 'Centros de Convenciones',
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
        foreach (self::ATRACTIVOS as $mapa) {
            $campos = array_merge($campos, array_keys($mapa));
        }
        foreach (self::PLANTA as $mapa) {
            $campos = array_merge($campos, array_keys($mapa));
        }

        return $campos;
    }
}
