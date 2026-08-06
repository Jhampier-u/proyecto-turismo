<?php

namespace App\Matrices;

/**
 * Criterios de la Matriz de Valoración Territorial.
 *
 * GENERADO por database/matrices/generar_valoracion_territorial.py
 * desde Documentación/Matriz de Valoración Territorial.xlsx.
 * No editar a mano: vuelve a ejecutar el generador.
 *
 * Instrumento de Calle Lituma y Chaca Espinoza. Los pesos de cada
 * dimensión suman 1 y la escala es 0-2, así que cada total va de 0 a 2.
 */
class ValoracionTerritorial
{
    public const ESCALA_MIN = 0;
    public const ESCALA_MAX = 2;

    /** Umbral que separa los cuadrantes en ambos ejes. */
    public const UMBRAL = 1.0;

    /** Contenido Territorial. Fuente sugerida: PDOT, PDT, fuentes secundarias y fuentes oficiales públicas. Para elementos culturales y espacios naturales: visitas in situ y documentos públicos. */
    public const CT = [
        'ct_energia_electrica' => [
            'sigla'  => 'EE',
            'peso'   => 0.1,
            'nombre' => 'Disponibilidad de Servicios de Energía Eléctica y Alumbrado Público',
            'niveles' => [
                0 => 'Ausencia de servicios de energía electríca y alumbrado público en el lugar',
                1 => 'El 50% o menos del territorio cuenta con el servicio de energía eléctrica y alumbrado público',
                2 => 'El 100% del territorio cuenta con el servicio de energía eléctrica y alumbrado público',
            ],
        ],
        'ct_agua_potable' => [
            'sigla'  => 'AP',
            'peso'   => 0.1,
            'nombre' => 'Disponibilidad de Servicios de Agua Potable y Alcantarillado',
            'niveles' => [
                0 => 'Ausencia de servicios de agua potable y alcantarillado en el lugar',
                1 => 'El 50% o menos del territorio cuenta con el servicio de agua potable y alcantarillado',
                2 => 'El 100% del territorio cuenta con el servicio de agua potable y alcantarillado',
            ],
        ],
        'ct_comunicacion' => [
            'sigla'  => 'SC',
            'peso'   => 0.05,
            'nombre' => 'Disponibilidad de Servicios de Comunicación',
            'niveles' => [
                0 => 'Ausencia de servicios de telefonía (pública y/o móvil) en el territorio',
                1 => 'El 50% o menos del territorio cuenta con el servicio telefonía pública, y el sitio no cuenta con recepción de señal móvil',
                2 => 'El 100% del territorio cuenta con el servicio de telefonía pública y el servicio de recepción de señal móvil es adecuado',
            ],
        ],
        'ct_recoleccion_basura' => [
            'sigla'  => 'RB',
            'peso'   => 0.1,
            'nombre' => 'Disponibilidad de Recolección de Basura',
            'niveles' => [
                0 => 'Ausencia de servicios de recolección de basura en el lugar',
                1 => 'El territorio cuenta con servicio de recolección de basura pero no existe tratamiento de la misma.',
                2 => 'El territorio cuenta con el servicio de recolección de basura y tratamiento de la misma',
            ],
        ],
        'ct_problemas_sociales' => [
            'sigla'  => 'PS',
            'peso'   => 0.05,
            'nombre' => 'Problemas Sociales en el Territorio',
            'niveles' => [
                0 => 'El territorio presenta altos índices de problemática social que se podrían ver afectadas por el turismo',
                1 => 'El territorio se encuentra trabajando en procesos de mitigación de impactos sociales pero que no han sido ejecutados aún',
                2 => 'El territorio cuenta con procesos para mitigar o disminuir el impacto negativo de los problemas sociales en el lugar',
            ],
        ],
        'ct_salud' => [
            'sigla'  => 'SS',
            'peso'   => 0.05,
            'nombre' => 'Disponibilidad de Servicios de Salud',
            'niveles' => [
                0 => 'Presencia de dispensarios médicos en el lugar',
                1 => 'Presencia de centros de salud o puestos de atención inmediata básica en el lugar',
                2 => 'Presencia de hospitales, clínicas, centros de salud y dispensarios médicos en el territorio.',
            ],
        ],
        'ct_seguridad' => [
            'sigla'  => 'SG',
            'peso'   => 0.05,
            'nombre' => 'Disponibilidad de Seguridad',
            'niveles' => [
                0 => 'Ausencia de servicios de seguridad públicos y/o privados en el lugar',
                1 => 'Presencia de servicios de seguridad públicos y/o privados en el lugar pero con un ineficiente cumplimiento de la actividad en el lugar',
                2 => 'Presencia de servicios públicos y/o privados de seguridad que generan un nivel de delincuencia mínimo en el sitio',
            ],
        ],
        'ct_conservacion_recursos' => [
            'sigla'  => 'CR',
            'peso'   => 0.1,
            'nombre' => 'Conservación y cuidado de los recursos naturales y culturales',
            'niveles' => [
                0 => 'Altos índices de contaminación, pérdida de la identidad local o efectos negativos socioculturales y ambientales',
                1 => 'El territorio demuestra indicios de procesos de conservación y cuidado de los recursos naturales y culturales del lugar',
                2 => 'El territorio demuestra una gestión adecuada para minimizar los impactos socioculturales y ambientales del sitio.',
            ],
        ],
        'ct_actividad_economica' => [
            'sigla'  => 'AE',
            'peso'   => 0.1,
            'nombre' => 'Actividad económica del lugar',
            'niveles' => [
                0 => 'El turismo no es visto como una actividad económica importante para el sitio',
                1 => 'El turismo es visto como una herramienta de dinamización económica pero no se han evidenciado sus resultados en el lugar',
                2 => 'El turismo forma parte importante de la dinámica turística en la economía del lugar',
            ],
        ],
        'ct_organizacion_social' => [
            'sigla'  => 'OS',
            'peso'   => 0.1,
            'nombre' => 'Organización Social',
            'niveles' => [
                0 => 'El territorio demuestra no cuenta con procesos de organización social evidenciables',
                1 => 'El lugar cuenta con indicios de organización social pero aún se mantienen problemas internos visibles',
                2 => 'El territorio se encuentra organizado de manera eficiente y activa por parte de la sociedad que lo conforma',
            ],
        ],
        'ct_elementos_culturales' => [
            'sigla'  => 'DC',
            'peso'   => 0.1,
            'nombre' => 'Disponibilidad de elementos culturales identitarios del territorio',
            'niveles' => [
                0 => 'No se evidencias manifestaciones culturales identitarias en el territorio',
                1 => 'El territorio cuenta con manifestaciones culturales relevantes pero con poca valorización de los residentes',
                2 => 'El lugar cuenta con manifestaciones culturales relevantes y características de la población local',
            ],
        ],
        'ct_espacios_naturales' => [
            'sigla'  => 'DN',
            'peso'   => 0.1,
            'nombre' => 'Disponibilidad de espacios naturales característicos y zonas de recreación en el territorio',
            'niveles' => [
                0 => 'No se evidencian sitios naturales o espacios de recreación que puedan ser utilizados en el territorio.',
                1 => 'El territorio cuenta con sitios naturales y zonas de recreación poco aprovechados',
                2 => 'El territorio cuenta con sitios naturales y espacios de recreación atractivos para el desarrollo de actividades turísticas y/o lúdicas',
            ],
        ],
    ];

    /** Ubicación y Conectividad. Fuente sugerida: PDOT, fuentes de información primaria y secundaria, visitas in situ y documentos oficiales. */
    public const UC = [
        'uc_vialidad' => [
            'sigla'  => 'V',
            'peso'   => 0.15,
            'nombre' => 'Vialidad',
            'niveles' => [
                0 => 'Las vías de conectividad hacia el territorio se encuentran en mal estado y son de tercer orden principalmente',
                1 => 'Las vías de acceso al territorio muestran un deterioro considerable y en su mayoría son de segundo orden',
                2 => 'Las vías de acceso al territorio cuentan con un mantenimiento adecuado y son de primer orden',
            ],
        ],
        'uc_infraestructura_conectividad' => [
            'sigla'  => 'IC',
            'peso'   => 0.1,
            'nombre' => 'Infraestructura de conectividad',
            'niveles' => [
                0 => 'El territorio no cuenta con espacios públicos de conectividad de ningún tipo',
                1 => 'El territorio cuenta con un terminal terrestre o estación de buses que facilita su traslado hacia el sitio',
                2 => 'El lugar cuenta con un aeropuerto, terminal terrestre y/o puerto que facilita su conectividad para el residente y el visitante',
            ],
        ],
        'uc_frecuencia_conectividad' => [
            'sigla'  => 'FC',
            'peso'   => 0.1,
            'nombre' => 'Frecuencia de conectividad hacia el territorio',
            'niveles' => [
                0 => 'El territorio cuenta con servicios de transporte público y/o privado que lo conectan de manera semanal',
                1 => 'El territorio cuenta con servicios de transporte público y/o privado que lo conecta ciertos días de la semana y en horarios específicos',
                2 => 'El territorio cuenta con servicios de transportación públicos y/o privados que lo conectan con otros lugares de manera diaria y permanente',
            ],
        ],
        'uc_distancia_atractivo' => [
            'sigla'  => 'DT',
            'peso'   => 0.1,
            'nombre' => 'Distancia del lugar al atractivo turístico más importante',
            'niveles' => [
                0 => 'El territorio se encuentra ubicado a más de 200 km de distancia del atractivo turístico más relevante de su zona de influencia',
                1 => 'El territorio se encuentra ubicado entre los 199 a 100 km de distancia del atractivo turístico más relevante de la zona de influencia',
                2 => 'El territorio se encuentra ubicado a menos de 100 km de distancia del atractivo turístico más relevante de la zona de influencia',
            ],
        ],
        'uc_distancia_sitio_visita' => [
            'sigla'  => 'DS',
            'peso'   => 0.1,
            'nombre' => 'Distancia del lugar al sitio de visita más cercano',
            'niveles' => [
                0 => 'El territorio se encuentra ubicado a más de 200 km de distancia del sitio de visita más relevante de su zona de influencia',
                1 => 'El territorio se encuentra ubicado entre los 199 a 100 km de distancia del sitio de visita más relevante de la zona de influencia',
                2 => 'El territorio se encuentra ubicado a menos de 100 km de distancia del sitio de visita más relevante de la zona de influencia',
            ],
        ],
        'uc_distancia_destino' => [
            'sigla'  => 'DD',
            'peso'   => 0.1,
            'nombre' => 'Distancia del lugar al destino turístico más cercano',
            'niveles' => [
                0 => 'El territorio se encuentra ubicado a más de 200 km de distancia del destino turístico más relevante de su zona de influencia',
                1 => 'El territorio se encuentra ubicado entre los 199 a 100 km de distancia del destino turístico más relevante de la zona de influencia',
                2 => 'El territorio se encuentra ubicado a menos de 100 km de distancia del destino turístico más relevante de la zona de influencia',
            ],
        ],
        'uc_distancia_mercado_emisor' => [
            'sigla'  => 'DM',
            'peso'   => 0.1,
            'nombre' => 'Distancia desde el lugar y el principal mercado emisor',
            'niveles' => [
                0 => 'El territorio se encuentra ubicado a más de 200 km de distancia de su principal espacio emisor de visitantes',
                1 => 'El territorio se encuentra ubicado entre los 199 a 100 km de distancia de su principal espacio emisor de visitantes',
                2 => 'El territorio se encuentra ubicado a menos de 100 km de distancia de su principal espacio emisor de visitantes',
            ],
        ],
        'uc_conglomeracion_oferta' => [
            'sigla'  => 'CO',
            'peso'   => 0.15,
            'nombre' => 'Conglomeración territorial y concentración de oferta actual y/o potencial',
            'niveles' => [
                0 => 'El 100% de los recursos y/o atractivos que posee el lugar se encuentran en un radio mayor a los 15 kilómetros',
                1 => 'El 100% de los recursos y/o atractivos que posee el lugar se encuentran en un radio mayor a los 10 kilómetros',
                2 => 'El 100% de los recursos y/o atractivos que posee el lugar se encuentran en un radio mayor a los 5 kilómetros',
            ],
        ],
        'uc_senalizacion' => [
            'sigla'  => 'S',
            'peso'   => 0.1,
            'nombre' => 'Señalización',
            'niveles' => [
                0 => 'Ausencia de señalización vial que favorezca la llegada al territorio',
                1 => 'Señalización vial incomprensible, con dificultad de lectura o de ubicación para llegar al sitio',
                2 => 'Señalización vial adecuadamente colocada y mantenida que favorece el traslado adecuado hacia el sitio',
            ],
        ],
    ];

    /** @return array<string, array> todos los criterios, CT seguidos de UC */
    public static function todos(): array
    {
        return self::CT + self::UC;
    }
}
