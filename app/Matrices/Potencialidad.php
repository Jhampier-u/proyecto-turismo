<?php

namespace App\Matrices;

/**
 * Criterios de la Matriz de Potencialidad Turística.
 *
 * Antes de esta migración, los 156 criterios vivían en
 * EvaluacionPotencialidadController::$secciones -el mismo punto de partida que
 * tenían Fit, Fet y Percepcion antes de sus propias migraciones-. Se mueven
 * aquí por la misma razón: que el formulario los recorra sin tenerlos
 * tecleados, y que el controlador no sea a la vez la fuente y uno de sus
 * consumidores.
 *
 * A diferencia de Fit::BLOQUES o Percepcion::$categorias, esta clase NO
 * declara pesos. El cálculo de Potencialidad no reparte un peso por sección
 * de la forma "bloque => peso, criterios" -tiene cuatro niveles de anidamiento
 * (p.ej. RN > Cuerpos de Agua > criterio, y RN mismo se pondera dentro de
 * Recursos Turísticos junto a RC) que ya vive, comentado y cubierto por
 * PotencialidadCalculoTest, dentro de
 * EvaluacionPotencialidadController::calcular(). Forzar los pesos aquí habría
 * significado reescribir ese cálculo para leer de una estructura nueva, que es
 * exactamente el riesgo de comportamiento que esta migración -un cambio de
 * presentación, no de cálculo- tiene que evitar. SECCIONES cubre lo que
 * necesitan el formulario y los mensajes de validación: el catálogo de
 * criterios y su etiqueta.
 *
 * Escala: 0 (Ausencia), 1 (Fragilidad), 2 (Aprovechable), igual para los 156
 * criterios -una escala genérica, como la de Fit/Fet, no una descripción
 * propia por criterio como en Paisaje-. Verificada contra el instrumento
 * original (Documentación/IMPLEMENTADA MATRIZ DE POTENCIALIDAD TURÍSTICA
 * TUR.xlsx) antes de asumir que "más alto es mejor" -que es lo que dan por
 * hecho <x-criterio-pildoras> y <x-franja-matriz>, coloreando por posición-
 * valía para los 156, no solo para los que suenan obviamente así. Cada una de
 * las 17 hojas de criterios fija la misma leyenda ("Rojo o Ausencia"=0,
 * "Amarillo o Fragilidad"=1, "Verde o Aprovechable"=2) y, revisando las
 * descripciones criterio por criterio, ninguno puntúa al revés -incluidos los
 * dos que suenan ambiguos a primera vista, "Explotaciones Mineras" y
 * "Complejos Industriales" (RC - Expresiones Contemporáneas): no miden
 * contaminación o impacto, miden si el sitio está abierto y acondicionado
 * para recibir visitantes, así que 2 sigue siendo el mejor valor-. La hoja
 * "TT- Productos Turísticos" marca un 3 en la fila de "Turismo Cultural" en
 * vez de un 2 -único caso en las 17 hojas-, pero es una errata de tecleo del
 * propio instrumento: la descripción de esa fila es la de "Aprovechable", la
 * misma redacción que las demás filas de esa hoja, y la validación del sistema
 * ya fija esta escala en 0-2 para los 156 por igual, así que no hay un cuarto
 * nivel real que reproducir.
 *
 * A diferencia de Fit/Fet (paleta de 4 niveles nueva) esta matriz usa 3
 * niveles: la paleta que <x-criterio-pildoras>/<x-franja-matriz> ya tenían
 * antes de fit-fet-componentes, la misma de Paisaje, ValoracionTerritorial y
 * Percepcion. No hizo falta tocar ninguno de los dos componentes.
 */
class Potencialidad
{
    public const ESCALA_MIN = 0;
    public const ESCALA_MAX = 2;

    /** Etiquetas de los 3 niveles, iguales para los 156 criterios. */
    public const NIVELES = [
        0 => 'Ausencia',
        1 => 'Fragilidad',
        2 => 'Aprovechable',
    ];

    /**
     * Título de sección => [campo => etiqueta].
     *
     * @var array<string, array<string, string>>
     */
    public const SECCIONES = [
        'RN — Zonas de Litoral' => [
            'rn_litoral_playas'               => 'Playas',
            'rn_litoral_arrecifes'            => 'Arrecifes',
            'rn_litoral_cuevas'               => 'Cuevas / Grutas / Cenotes',
            'rn_litoral_flora_fauna'          => 'Flora y Fauna Litoral',
            'rn_litoral_actividades_acuaticas'=> 'Actividades Acuáticas',
            'rn_litoral_areas_deserticas'     => 'Áreas Desérticas Costeras',
        ],
        'RN — Zonas de Montaña' => [
            'rn_montana_montanas'         => 'Montañas',
            'rn_montana_sierras'          => 'Sierras',
            'rn_montana_canadas'          => 'Cañadas',
            'rn_montana_canones'          => 'Cañones',
            'rn_montana_cuevas'           => 'Cuevas y Grutas',
            'rn_montana_geisers'          => 'Géisers',
            'rn_montana_volcanes'         => 'Volcanes',
            'rn_montana_valles'           => 'Valles',
            'rn_montana_bosques'          => 'Bosques',
            'rn_montana_flora_fauna'      => 'Flora y Fauna de Montaña',
            'rn_montana_areas_deserticas' => 'Áreas Desérticas de Montaña',
        ],
        'RN — Áreas Naturales Protegidas' => [
            'rn_anp_reservas_marinas'      => 'Reservas Marinas',
            'rn_anp_reserva_geobotanica'   => 'Reserva Geobotánica',
            'rn_anp_reserva_ecologica'     => 'Reserva Ecológica',
            'rn_anp_reserva_fauna'         => 'Reserva de Producción de Fauna',
            'rn_anp_reserva_biologica'     => 'Reserva Biológica',
            'rn_anp_reserva_vida_silvestre'=> 'Reserva de Vida Silvestre',
            'rn_anp_parques_nacionales'    => 'Parques Nacionales',
            'rn_anp_area_privada'          => 'Área Protegida Privada',
            'rn_anp_area_comunitaria'      => 'Área Protegida Comunitaria',
            'rn_anp_area_recreacion'       => 'Área Nacional de Recreación',
            'rn_anp_area_conservacion'     => 'Área Ecológica de Conservación',
        ],
        'RN — Cuerpos de Agua' => [
            'rn_agua_lagos'    => 'Lagos y Lagunas',
            'rn_agua_rios'     => 'Ríos y Arroyos',
            'rn_agua_cascadas' => 'Cascadas y Caídas de Agua',
            'rn_agua_termas'   => 'Termas',
            'rn_agua_esteros'  => 'Esteros',
        ],
        'RC — Artístico Monumental' => [
            'rc_am_zonas_arqueologicas'  => 'Zonas Arqueológicas',
            'rc_am_fosiles'              => 'Fósiles',
            'rc_am_pinturas_rupestres'   => 'Pinturas Rupestres',
            'rc_am_ciudades_coloniales'  => 'Ciudades Coloniales',
            'rc_am_pueblos_antiguos'     => 'Pueblos Antiguos',
            'rc_am_patrimonio_humanidad' => 'Sitios Patrimonio de la Humanidad',
            'rc_am_santuarios'           => 'Santuarios',
        ],
        'RC — Nacionalidades y Pueblos' => [
            'rc_np_grupos_etnicos'         => 'Grupos Étnicos',
            'rc_np_expresiones_artisticas' => 'Expresiones Artísticas Folklóricas',
            'rc_np_ferias_mercados'        => 'Ferias y Mercados Tradicionales',
            'rc_np_eventos_folkloricos'    => 'Eventos Folklóricos',
            'rc_np_eventos_historicos'     => 'Eventos Históricos y/o Religiosos',
        ],
        'RC — Expresiones Contemporáneas' => [
            'rc_ec_obras_arte'             => 'Obras de Arte',
            'rc_ec_centros_cientificos'    => 'Centros Científicos y Técnicos',
            'rc_ec_explotaciones_mineras'  => 'Explotaciones Mineras',
            'rc_ec_plantaciones'           => 'Plantaciones Agropecuarias',
            'rc_ec_complejos_industriales' => 'Complejos Industriales',
        ],
        'PT — Alojamiento' => [
            'pt_aloj_hoteles'        => 'Hoteles',
            'pt_aloj_hostales'       => 'Hostales',
            'pt_aloj_hosterias'      => 'Hosterías',
            'pt_aloj_haciendas'      => 'Haciendas Turísticas',
            'pt_aloj_lodges'         => 'Lodges',
            'pt_aloj_resorts'        => 'Resorts',
            'pt_aloj_refugios'       => 'Refugios',
            'pt_aloj_campamentos'    => 'Campamentos Turísticos',
            'pt_aloj_casa_huespedes' => 'Casa de Huéspedes',
            'pt_aloj_ctc'            => 'Centro de Turismo Comunitario',
        ],
        'PT — Restauración' => [
            'pt_rest_restaurantes'  => 'Restaurantes',
            'pt_rest_cafeterias'    => 'Cafeterías',
            'pt_rest_bares'         => 'Bares',
            'pt_rest_discotecas'    => 'Discotecas',
            'pt_rest_moviles'       => 'Establecimientos Móviles',
            'pt_rest_plazas_comida' => 'Plazas de Comida',
            'pt_rest_catering'      => 'Servicios de Catering',
            'pt_rest_ctc'           => 'CTC — Restauración',
        ],
        'PT — Intermediación' => [
            'pt_inter_mayoristas'      => 'Agencias Mayoristas',
            'pt_inter_internacionales' => 'Agencias Internacionales',
            'pt_inter_operadoras'      => 'Operadoras Turísticas',
            'pt_inter_duales'          => 'Agencias Duales',
            'pt_inter_ctc'             => 'CTC — Operación',
        ],
        'PT — Transportación' => [
            'pt_trans_terrestre'   => 'Transportación Terrestre',
            'pt_trans_ferroviaria' => 'Transportación Ferroviaria',
            'pt_trans_aerea'       => 'Transportación Aérea',
            'pt_trans_maritima'    => 'Transportación Marítima',
            'pt_trans_fluvial'     => 'Transportación Fluvial',
            'pt_trans_ctc'         => 'CTC — Transportación',
        ],
        'PT — Interpretación / Guianza' => [
            'pt_guia_locales'    => 'Guías Locales',
            'pt_guia_nacionales' => 'Guías Nacionales',
            'pt_guia_patrimonio' => 'Guías Especializados Patrimonio',
            'pt_guia_aventura'   => 'Guías Especializados Aventura',
        ],
        'Tipologías de Turismo' => [
            'tt_naturaleza'          => 'Turismo de Naturaleza',
            'tt_sol_playa'           => 'Turismo de Sol y Playa',
            'tt_cultural'            => 'Turismo Cultural',
            'tt_urbano'              => 'Turismo Urbano',
            'tt_especializado'       => 'Turismo Especializado / Alternativo',
            'tt_rural'               => 'Turismo Rural',
            'tt_agroturismo'         => 'Agroturismo',
            'tt_etnoturismo'         => 'Etnoturismo',
            'tt_aventura'            => 'Turismo de Aventura',
            'tt_deportivo'           => 'Turismo Deportivo',
            'tt_ecoturismo'          => 'Ecoturismo',
            'tt_negocios'            => 'Turismo de Negocios',
            'tt_gastronomico'        => 'Turismo Gastronómico',
            'tt_activo'              => 'Turismo Activo',
            'tt_vivencial'           => 'Turismo Vivencial',
            'tt_experiencial'        => 'Turismo Experiencial',
            'tt_patrimonial'         => 'Turismo Patrimonial',
            'tt_historico'           => 'Turismo Histórico',
            'tt_arqueologico'        => 'Turismo Arqueológico',
            'tt_arquitectonico'      => 'Turismo Arquitectónico y Monumental',
            'tt_literario'           => 'Turismo Literario',
            'tt_astronomico'         => 'Turismo Astronómico',
            'tt_espacial'            => 'Turismo Espacial',
            'tt_compras'             => 'Turismo de Compras',
            'tt_enoturismo'          => 'Enoturismo',
            'tt_salud'               => 'Turismo de Salud',
            'tt_artistico'           => 'Turismo Artístico / Lúdico / Festivo',
            'tt_cinematografico'     => 'Turismo Cinematográfico',
            'tt_cinegetico'          => 'Turismo Cinegético',
            'tt_intereses_especiales'=> 'Turismo de Intereses Especiales',
            'tt_idiomatico'          => 'Turismo Idiomático',
            'tt_cruceros'            => 'Turismo de Cruceros',
            'tt_marino_costero'      => 'Turismo Marino Costero',
            'tt_nautico'             => 'Turismo Náutico',
            'tt_religioso'           => 'Turismo Religioso',
            'tt_social'              => 'Turismo Social',
            'tt_comunitario'         => 'Turismo Comunitario',
        ],
        'Infraestructura' => [
            'i_transporte'         => 'Transporte',
            'i_vialidad'           => 'Vialidad',
            'i_comunicaciones'     => 'Comunicaciones',
            'i_salud'              => 'Salud',
            'i_energia'            => 'Energía Eléctrica',
            'i_agua_potable'       => 'Agua Potable',
            'i_alcantarillado'     => 'Alcantarillado',
            'i_tratamiento_basura' => 'Tratamiento de Basura',
            'i_aguas_residuales'   => 'Aguas Residuales',
            'i_conectividad'       => 'Conectividad / Internet',
            'i_senalizacion'       => 'Señalización',
        ],
        'Afluencia Turística' => [
            'dt_at_locales'         => 'Flujos Locales',
            'dt_at_regionales'      => 'Flujos Regionales',
            'dt_at_nacionales'      => 'Flujos Nacionales',
            'dt_at_internacionales' => 'Flujos Internacionales',
            'dt_at_estadia'         => 'Estadía Promedio',
        ],
        'Marketing Turístico' => [
            'dt_mk_organismo_promotor'        => 'Organismo Público Promotor',
            'dt_mk_plan_marketing'            => 'Plan de Marketing',
            'dt_mk_tendencias_mercado'        => 'Tendencias de Mercado',
            'dt_mk_investigacion'             => 'Investigación y Segmentación',
            'dt_mk_publicidad'                => 'Publicidad y Promoción',
            'dt_mk_comercializacion_tradicional' => 'Comercialización Tradicional',
            'dt_mk_comercializacion_digital'  => 'Comercialización Digital',
            'dt_mk_imagen_sitio'              => 'Imagen del Sitio',
            'dt_mk_presencia_digital'         => 'Presencia Digital',
            'dt_mk_innovacion'                => 'Innovación Turística',
        ],
        'Superestructura' => [
            'st_politica_publica'              => 'Política Pública de Turismo',
            'st_modelo_gestion'                => 'Modelo de Gestión Turística',
            'st_actores'                       => 'Actores de la Actividad Turística',
            'st_marco_normativo'               => 'Marco Normativo',
            'st_participacion_ciudadana'       => 'Participación Ciudadana',
            'st_planificacion_participativa'   => 'Planificación Participativa',
            'st_entes_publicos'                => 'Entes Públicos de Turismo',
            'st_representacion_gremial'        => 'Representación Gremial',
            'st_representatividad_comunitaria' => 'Representatividad Comunitaria',
            'st_fomento'                       => 'Fomento al Sector Turístico',
        ],
    ];

    /** @return array<string, string> los 156 criterios, aplanados: campo => etiqueta */
    public static function todos(): array
    {
        return array_merge(...array_values(self::SECCIONES));
    }
}
