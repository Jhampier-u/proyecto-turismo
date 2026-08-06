"""Genera la definición PHP de los criterios de la Matriz de Valoración Territorial
directamente desde el instrumento original, para evitar errores de transcripción.

Uso, desde la raíz del proyecto:  python database/matrices/generar_valoracion_territorial.py
"""
import openpyxl
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
ORIGEN = RAIZ / "Documentación" / "Matriz de Valoración Territorial.xlsx"
DESTINO = RAIZ / "app" / "Matrices" / "ValoracionTerritorial.php"

# sigla en el instrumento -> nombre de columna en la base de datos
CAMPOS = {
    'EE': 'ct_energia_electrica',       'AP': 'ct_agua_potable',
    'SC': 'ct_comunicacion',            'RB': 'ct_recoleccion_basura',
    'PS': 'ct_problemas_sociales',      'SS': 'ct_salud',
    'SG': 'ct_seguridad',               'CR': 'ct_conservacion_recursos',
    'AE': 'ct_actividad_economica',     'OS': 'ct_organizacion_social',
    'DC': 'ct_elementos_culturales',    'DN': 'ct_espacios_naturales',
    'V':  'uc_vialidad',                'IC': 'uc_infraestructura_conectividad',
    'FC': 'uc_frecuencia_conectividad', 'DT': 'uc_distancia_atractivo',
    'DS': 'uc_distancia_sitio_visita',  'DD': 'uc_distancia_destino',
    'DM': 'uc_distancia_mercado_emisor','CO': 'uc_conglomeracion_oferta',
    'S':  'uc_senalizacion',
}

wb = openpyxl.load_workbook(ORIGEN, data_only=True)


def limpiar(t):
    return " ".join(str(t).split()) if t else ""


def leer(hoja_ref, fila_ini, fila_fin, hoja_val, val_ini, val_fin):
    """Empareja la hoja de descripciones con la de pesos. Ambas listan los
    criterios en el mismo orden; la aserción lo verifica."""
    ref, val = wb[hoja_ref], wb[hoja_val]
    filas_ref = list(range(fila_ini, fila_fin + 1))
    filas_val = list(range(val_ini, val_fin + 1))
    assert len(filas_ref) == len(filas_val), "las hojas no tienen el mismo número de criterios"

    salida = []
    for fr, fv in zip(filas_ref, filas_val):
        sigla = limpiar(val.cell(fv, 6).value)          # columna F
        salida.append({
            'sigla': sigla,
            'campo': CAMPOS[sigla],
            'peso': val.cell(fv, 2).value,              # columna B
            'nombre': limpiar(ref.cell(fr, 1).value),   # columna A
            'desc': [limpiar(ref.cell(fr, c).value) for c in (2, 3, 4)],  # B, C, D
        })
    return salida


ct = leer('Contenido Territorial', 6, 17, 'Valoración CT', 5, 16)
uc = leer('Ubicación y Conectividad', 6, 14, 'Valoración UC', 5, 13)

for nombre, grupo in (('CT', ct), ('UC', uc)):
    total = round(sum(c['peso'] for c in grupo), 10)
    assert total == 1.0, f"{nombre}: los pesos suman {total}, no 1.0"
    print(f"// {nombre}: {len(grupo)} criterios, pesos suman {total}")


def php_str(s):
    return "'" + s.replace("\\", "\\\\").replace("'", "\\'") + "'"


lineas = ["<?php", "", "namespace App\\Matrices;", "",
          "/**", " * Criterios de la Matriz de Valoración Territorial.",
          " *", " * GENERADO por database/matrices/generar_valoracion_territorial.py",
          " * desde Documentación/Matriz de Valoración Territorial.xlsx.",
          " * No editar a mano: vuelve a ejecutar el generador.",
          " *", " * Instrumento de Calle Lituma y Chaca Espinoza. Los pesos de cada",
          " * dimensión suman 1 y la escala es 0-2, así que cada total va de 0 a 2.",
          " */", "class ValoracionTerritorial", "{",
          "    public const ESCALA_MIN = 0;",
          "    public const ESCALA_MAX = 2;", "",
          "    /** Umbral que separa los cuadrantes en ambos ejes. */",
          "    public const UMBRAL = 1.0;", ""]

for clave, grupo, titulo, fuente in (
    ('CT', ct, 'Contenido Territorial',
     'PDOT, PDT, fuentes secundarias y fuentes oficiales públicas. Para elementos '
     'culturales y espacios naturales: visitas in situ y documentos públicos.'),
    ('UC', uc, 'Ubicación y Conectividad',
     'PDOT, fuentes de información primaria y secundaria, visitas in situ y '
     'documentos oficiales.'),
):
    lineas.append(f"    /** {titulo}. Fuente sugerida: {fuente} */")
    lineas.append(f"    public const {clave} = [")
    for c in grupo:
        lineas.append(f"        {php_str(c['campo'])} => [")
        lineas.append(f"            'sigla'  => {php_str(c['sigla'])},")
        lineas.append(f"            'peso'   => {c['peso']},")
        lineas.append(f"            'nombre' => {php_str(c['nombre'])},")
        lineas.append("            'niveles' => [")
        for i, d in enumerate(c['desc']):
            lineas.append(f"                {i} => {php_str(d)},")
        lineas.append("            ],")
        lineas.append("        ],")
    lineas.append("    ];")
    lineas.append("")

lineas += ["    /** @return array<string, array> todos los criterios, CT seguidos de UC */",
           "    public static function todos(): array",
           "    {",
           "        return self::CT + self::UC;",
           "    }",
           "}"]

DESTINO.parent.mkdir(parents=True, exist_ok=True)
DESTINO.write_text("\n".join(lineas) + "\n", encoding="utf-8")
print(f"escrito: {DESTINO}")
