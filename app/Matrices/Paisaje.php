<?php

namespace App\Matrices;

/**
 * Criterios de la Matriz de Análisis y Valoración del Paisaje.
 *
 * GENERADO por database/matrices/generar_paisaje.py desde
 * Documentación/Matriz de Análisis y Valoración del Paisaje.xlsx.
 * No editar a mano: vuelve a ejecutar el generador.
 *
 * Instrumento de Calle Lituma y Chaca Espinoza, sobre Muñoz 2012 y
 * Bertinni 2019. Cada criterio se califica 0, 3 o 5 con etiquetas propias;
 * cada categoría promedia los suyos y el promedio se pondera. Los pesos
 * suman 1, así que el resultado final va de 0 a 5.
 */
class Paisaje
{
    /** Valores admitidos por el instrumento. No es un rango continuo. */
    public const VALORES = [0, 3, 5];

    public const MAXIMO = 5;

    /** @var array<string, array> categoría => nombre, peso y criterios */
    public const CATEGORIAS = [
        'ep' => [
            'nombre' => 'Evolución del Paisaje',
            'peso'   => 0.15,
            'criterios' => [
                'ep_cambios_tiempo' => [
                    'nombre'  => 'Cambios del Paisaje en el Tiempo',
                    'niveles' => [
                        0 => 'Negativo',
                        3 => 'Neutro',
                        5 => 'Positivo',
                    ],
                ],
                'ep_imagen_pasada' => [
                    'nombre'  => 'Imagen del Territorio Pasada',
                    'niveles' => [
                        0 => 'Negativo',
                        3 => 'Neutro',
                        5 => 'Positivo',
                    ],
                ],
                'ep_imagen_actual' => [
                    'nombre'  => 'Imagen del Territorio Actual',
                    'niveles' => [
                        0 => 'Negativo',
                        3 => 'Neutro',
                        5 => 'Positivo',
                    ],
                ],
                'ep_imagen_futura' => [
                    'nombre'  => 'Imagen del Territorio Futura',
                    'niveles' => [
                        0 => 'Negativo',
                        3 => 'Neutro',
                        5 => 'Positivo',
                    ],
                ],
                'ep_conservacion' => [
                    'nombre'  => 'Principios de Conservación',
                    'niveles' => [
                        0 => 'Desfavorable',
                        3 => 'Regular',
                        5 => 'Favorable',
                    ],
                ],
                'ep_sostenibilidad' => [
                    'nombre'  => 'Principios de Sostenibilidad',
                    'niveles' => [
                        0 => 'Desfavorable',
                        3 => 'Regular',
                        5 => 'Favorable',
                    ],
                ],
            ],
        ],
        'pn' => [
            'nombre' => 'Recursos Paisajísticos de Interés Natural',
            'peso'   => 0.25,
            'criterios' => [
                'pn_areas_verdes' => [
                    'nombre'  => 'Áreas Naturales o Verdes',
                    'niveles' => [
                        0 => 'Sin Presencia',
                        3 => 'Descuidadas',
                        5 => 'Conservadas',
                    ],
                ],
                'pn_areas_protegidas' => [
                    'nombre'  => 'Áreas Naturales Protegidas',
                    'niveles' => [
                        0 => 'Sin Presencia',
                        3 => 'Descuidadas',
                        5 => 'Conservadas',
                    ],
                ],
                'pn_geomorfologia' => [
                    'nombre'  => 'Geomorfología',
                    'niveles' => [
                        0 => 'Irrelevante',
                        3 => 'Neutra',
                        5 => 'Relevante',
                    ],
                ],
                'pn_topografia' => [
                    'nombre'  => 'Topografía',
                    'niveles' => [
                        0 => 'Irrelevante',
                        3 => 'Neutra',
                        5 => 'Relevante',
                    ],
                ],
                'pn_hidrografia' => [
                    'nombre'  => 'Hidrografía',
                    'niveles' => [
                        0 => 'Irrelevante',
                        3 => 'Neutra',
                        5 => 'Relevante',
                    ],
                ],
                'pn_geologia' => [
                    'nombre'  => 'Geología',
                    'niveles' => [
                        0 => 'Irrelevante',
                        3 => 'Neutra',
                        5 => 'Relevante',
                    ],
                ],
                'pn_flora' => [
                    'nombre'  => 'Flora Representativa',
                    'niveles' => [
                        0 => 'Irrelevante',
                        3 => 'Neutra',
                        5 => 'Relevante',
                    ],
                ],
                'pn_fauna' => [
                    'nombre'  => 'Fauna Representativa',
                    'niveles' => [
                        0 => 'Irrelevante',
                        3 => 'Neutra',
                        5 => 'Relevante',
                    ],
                ],
                'pn_cobertura_suelo' => [
                    'nombre'  => 'Cobertura de Suelo',
                    'niveles' => [
                        0 => 'Inadecuada',
                        3 => 'Promedio',
                        5 => 'Adecuada',
                    ],
                ],
            ],
        ],
        'pc' => [
            'nombre' => 'Recursos Paisajísticos de Interés Cultural',
            'peso'   => 0.25,
            'criterios' => [
                'pc_arquitectonico_moderno' => [
                    'nombre'  => 'Patrimonio Arquitectónico Moderno',
                    'niveles' => [
                        0 => 'Sin Presencia',
                        3 => 'Descuidado',
                        5 => 'Conservado',
                    ],
                ],
                'pc_vernacular' => [
                    'nombre'  => 'Patrimonio Vernacular',
                    'niveles' => [
                        0 => 'Sin Presencia',
                        3 => 'Descuidado',
                        5 => 'Conservado',
                    ],
                ],
                'pc_edificado_historico' => [
                    'nombre'  => 'Patrimonio Edificado Histórico',
                    'niveles' => [
                        0 => 'Sin Presencia',
                        3 => 'Descuidado',
                        5 => 'Conservado',
                    ],
                ],
                'pc_religioso' => [
                    'nombre'  => 'Patrimonio Religioso',
                    'niveles' => [
                        0 => 'Sin Presencia',
                        3 => 'Descuidado',
                        5 => 'Conservado',
                    ],
                ],
                'pc_vivo' => [
                    'nombre'  => 'Patrimonio Vivo',
                    'niveles' => [
                        0 => 'Sin Presencia',
                        3 => 'Similar',
                        5 => 'Identitario',
                    ],
                ],
            ],
        ],
        'iv' => [
            'nombre' => 'Recursos Paisajísticos de Interés Visual',
            'peso'   => 0.25,
            'criterios' => [
                'iv_referentes_naturales' => [
                    'nombre'  => 'Referentes Visuales Naturales',
                    'niveles' => [
                        0 => 'Sin Presencia',
                        3 => 'Similares',
                        5 => 'Identitarios',
                    ],
                ],
                'iv_referentes_antropicos' => [
                    'nombre'  => 'Referentes Visuales Antrópicos',
                    'niveles' => [
                        0 => 'Sin Presencia',
                        3 => 'Similares',
                        5 => 'Identitarios',
                    ],
                ],
                'iv_puntos_observacion' => [
                    'nombre'  => 'Puntos de Observación',
                    'niveles' => [
                        0 => 'Sin Presencia',
                        3 => 'Similares',
                        5 => 'Identitarios',
                    ],
                ],
                'iv_singularidad' => [
                    'nombre'  => 'Singularidad',
                    'niveles' => [
                        0 => 'Nula',
                        3 => 'Promedio',
                        5 => 'Importante',
                    ],
                ],
                'iv_representatividad' => [
                    'nombre'  => 'Representatividad',
                    'niveles' => [
                        0 => 'Nula',
                        3 => 'Promedio',
                        5 => 'Importante',
                    ],
                ],
                'iv_visibilidad' => [
                    'nombre'  => 'Visibilidad',
                    'niveles' => [
                        0 => 'Inadecuado',
                        3 => 'Neutro',
                        5 => 'Adecuado',
                    ],
                ],
                'iv_calidad_escenica' => [
                    'nombre'  => 'Calidad Escénica',
                    'niveles' => [
                        0 => 'Inadecuado',
                        3 => 'Neutro',
                        5 => 'Adecuado',
                    ],
                ],
            ],
        ],
        'cp' => [
            'nombre' => 'Conflictos Paisajísticos',
            'peso'   => 0.1,
            'criterios' => [
                'cp_degradacion_natural' => [
                    'nombre'  => 'Degradación del Paisaje Natural',
                    'niveles' => [
                        0 => 'Afectado',
                        3 => 'Presente',
                        5 => 'Controlado',
                    ],
                ],
                'cp_degradacion_antropica' => [
                    'nombre'  => 'Degradación Antrópica del Paisaje',
                    'niveles' => [
                        0 => 'Afectado',
                        3 => 'Presente',
                        5 => 'Controlado',
                    ],
                ],
                'cp_dinamicas_territoriales' => [
                    'nombre'  => 'Dinámicas Territoriales',
                    'niveles' => [
                        0 => 'Afectado',
                        3 => 'Presente',
                        5 => 'Controlado',
                    ],
                ],
                'cp_fragmentacion' => [
                    'nombre'  => 'Fragmentación del Territorio',
                    'niveles' => [
                        0 => 'Afectado',
                        3 => 'Presente',
                        5 => 'Controlado',
                    ],
                ],
                'cp_infraestructura' => [
                    'nombre'  => 'Implantación de Infraestructura',
                    'niveles' => [
                        0 => 'Afectado',
                        3 => 'Presente',
                        5 => 'Controlado',
                    ],
                ],
                'cp_actividades_diversas' => [
                    'nombre'  => 'Implantación de Actividades Diversas',
                    'niveles' => [
                        0 => 'Afectado',
                        3 => 'Presente',
                        5 => 'Controlado',
                    ],
                ],
                'cp_conurbaciones' => [
                    'nombre'  => 'Conurbaciones',
                    'niveles' => [
                        0 => 'Afectado',
                        3 => 'Presente',
                        5 => 'Controlado',
                    ],
                ],
            ],
        ],
    ];

    /** Escenarios del resultado final, de mayor a menor. */
    public const ESCENARIOS = [
        [
            'rango'  => 'De 4 a 5',
            'nombre' => 'Eficiente',
            'texto'  => 'El paisaje cuenta con elementos naturales y/o culturales y de claridad visual para se disfrutado por los visitantes y su factor de unicidad permite trabajarlo como un recurso turístico territorial clave que permita su identificación, caracterización y diferenciación.',
        ],
        [
            'rango'  => 'De 3 a 4',
            'nombre' => 'Proactivo',
            'texto'  => 'El paisaje cuenta con elementos naturales y/o culturales atractivos pero la claridad visual para el disfrute de los mismos no es la adecuada lo que limita su capacidad de ser tomado como un recurso turístico territorial de perepción amplia.',
        ],
        [
            'rango'  => 'De 2 a 3',
            'nombre' => 'Neutro',
            'texto'  => 'El paisaje cuenta con una claridad visual importante que permite que el recurso escénico sea potencial para el desarrollo de actividades turísticas, pero los elementos naturales y/o culturales que se ubican en el sitio o sus alrededores carecen de diferenciación.',
        ],
        [
            'rango'  => 'De 1 a 2',
            'nombre' => 'Reactivo',
            'texto'  => 'El territorio cuenta con elementos naturales y/o culturales que pueden ser utilizados como recurso escénico, pero los mismos no son reconocidos por la localidad y no se encuentran puestos en valor; su calidad visual presenta limitaciones evidentes.',
        ],
        [
            'rango'  => 'Inferior a 1',
            'nombre' => 'Inexistente',
            'texto'  => 'El territorio no posee características escénicas, de ubicación e identitarias que puedan demostrar su unicidad turístico-territorial.',
        ],
    ];

    /** @return array<string, array> los 34 criterios, aplanados */
    public static function todos(): array
    {
        $criterios = [];

        foreach (self::CATEGORIAS as $categoria) {
            $criterios += $categoria['criterios'];
        }

        return $criterios;
    }
}
