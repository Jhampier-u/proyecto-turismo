const fs = require('fs');
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, LevelFormat, HeadingLevel,
  BorderStyle, WidthType, ShadingType, PageBreak, PageNumber,
  TabStopType, TabStopPosition, TableOfContents,
} = require('docx');

// ============================================================
//  PALETA Y HELPERS
// ============================================================
const PALETTE = {
  primary:   '1E3A8A', // indigo-900
  secondary: '0E7490', // cyan-700
  accent:    'D97706', // amber-600
  light:     'F1F5F9', // slate-100
  border:    'CBD5E1', // slate-300
  text:      '0F172A', // slate-900
  muted:     '475569', // slate-600
  red:       'B91C1C',
  green:     '15803D',
  yellow:    'CA8A04',
};

const FONT = 'Arial';

const border = (color = PALETTE.border) => ({ style: BorderStyle.SINGLE, size: 6, color });
const allBorders = (color = PALETTE.border) => ({
  top: border(color), bottom: border(color), left: border(color), right: border(color),
});

// Convenience runs
const T  = (text, opts = {}) => new TextRun({ text, font: FONT, ...opts });
const P  = (text, opts = {}) => new Paragraph({
  children: typeof text === 'string' ? [T(text)] : text,
  spacing: { after: 120 },
  ...opts,
});
const H1 = (text) => new Paragraph({
  heading: HeadingLevel.HEADING_1,
  children: [T(text)],
  spacing: { before: 360, after: 200 },
});
const H2 = (text) => new Paragraph({
  heading: HeadingLevel.HEADING_2,
  children: [T(text)],
  spacing: { before: 280, after: 160 },
});
const H3 = (text) => new Paragraph({
  heading: HeadingLevel.HEADING_3,
  children: [T(text)],
  spacing: { before: 220, after: 120 },
});

const bullet = (text) => new Paragraph({
  numbering: { reference: 'bullets', level: 0 },
  children: typeof text === 'string' ? [T(text)] : text,
  spacing: { after: 80 },
});
const num = (text) => new Paragraph({
  numbering: { reference: 'numbers', level: 0 },
  children: typeof text === 'string' ? [T(text)] : text,
  spacing: { after: 80 },
});

const cell = (children, opts = {}) => new TableCell({
  borders: allBorders(),
  margins: { top: 100, bottom: 100, left: 140, right: 140 },
  width: opts.width,
  shading: opts.shading,
  children: Array.isArray(children) ? children : [
    new Paragraph({ children: [T(typeof children === 'string' ? children : '', opts.run || {})] })
  ],
});

const headerCell = (text, width) => cell(
  [new Paragraph({ children: [T(text, { bold: true, color: 'FFFFFF', size: 22 })] })],
  { width: { size: width, type: WidthType.DXA }, shading: { fill: PALETTE.primary, type: ShadingType.CLEAR } }
);

const divider = () => new Paragraph({
  border: { bottom: { style: BorderStyle.SINGLE, size: 12, color: PALETTE.secondary } },
  spacing: { before: 80, after: 200 },
});

const spacer = (size = 200) => new Paragraph({ children: [T('')], spacing: { after: size } });

// ============================================================
//  CONTENT
// ============================================================
const content = [];

// ───────────── PORTADA ─────────────
content.push(
  new Paragraph({ spacing: { before: 1800 }, children: [T('')] }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 200 },
    children: [T('UNIVERSIDAD DEL AZUAY', { bold: true, size: 28, color: PALETTE.primary, font: FONT })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 80 },
    children: [T('Facultad de Ciencias de la Administración', { size: 22, color: PALETTE.muted })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 600 },
    children: [T('Escuela de Ingeniería en Ciencias de la Computación', { size: 22, color: PALETTE.muted })],
  }),
  divider(),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 200 },
    children: [T('INFORME TÉCNICO DEL PROYECTO', { bold: true, size: 32, color: PALETTE.text })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 200 },
    children: [T('UDAExplore', { bold: true, size: 44, color: PALETTE.secondary })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 600 },
    children: [T('Plataforma Web para la Gestión y Evaluación del Potencial Turístico Territorial', { italics: true, size: 26, color: PALETTE.muted })],
  }),
  divider(),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 600, after: 100 },
    children: [T('Elaborado por:', { bold: true, size: 22 })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 200 },
    children: [T('Juan Pedro Pesántez López', { size: 24 })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 100 },
    children: [T('Tutor externo:', { bold: true, size: 22 })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 600 },
    children: [T('Lic. Ronal Edison Chaca Espinoza, Mg., PhD.', { size: 22 })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 800 },
    children: [T('Cuenca — Ecuador · 2026', { italics: true, size: 22, color: PALETTE.muted })],
  }),
  new Paragraph({ children: [new PageBreak()] }),
);

// ───────────── ÍNDICE ─────────────
content.push(
  H1('Índice de contenidos'),
  new Paragraph({
    children: [new TableOfContents('Índice', { hyperlink: true, headingStyleRange: '1-3' })],
  }),
  new Paragraph({ children: [new PageBreak()] }),
);

// ───────────── RESUMEN EJECUTIVO ─────────────
content.push(
  H1('Resumen ejecutivo'),
  divider(),
  P('UDAExplore es una plataforma web desarrollada para la Escuela de Turismo de la Universidad del Azuay, cuya finalidad es la sistematización de los procesos de diagnóstico y evaluación del potencial turístico territorial. El sistema digitaliza cuatro instrumentos técnicos —Inventario, Vocación, Potencialidad y Percepción de la Localidad— que originalmente operaban en hojas de cálculo independientes, integrándolos bajo un único entorno colaborativo con control de roles, persistencia transaccional y generación automatizada de reportes visuales.'),
  P('La plataforma fue construida sobre una arquitectura MVC implementada en el framework Laravel 12 (PHP 8.2), con persistencia relacional en PostgreSQL, capa de presentación basada en Tailwind CSS y visualizaciones dinámicas mediante Chart.js. El despliegue se realizó en la plataforma Render bajo un contenedor Docker, lo que garantiza reproducibilidad de entornos y portabilidad.'),
  P('La implementación traduce con fidelidad metodológica el modelo de evaluación turística desarrollado por los docentes de la facultad, preservando los pesos porcentuales, escalas valorativas y fórmulas de ponderación originales. El sistema introduce, además, mejoras estructurales respecto al modelo en Excel: configuración dinámica de campos por zona, control de estados (borrador / confirmado), restricciones de edición por rol y trazabilidad de evaluaciones por usuario y fecha.'),
  P('Como resultado, UDAExplore se posiciona como una herramienta de apoyo a la docencia, la investigación aplicada y la toma de decisiones en gestión turística, capaz de generar diagnósticos territoriales precisos a partir de instrumentos técnicos validados por la academia.'),
  new Paragraph({ children: [new PageBreak()] }),
);

// ───────────── 1. INTRODUCCIÓN ─────────────
content.push(
  H1('1. Introducción'),
  divider(),
  H2('1.1 Contexto y planteamiento del problema'),
  P('La Escuela de Turismo de la Universidad del Azuay desarrolla, desde hace varios ciclos académicos, una metodología propia para la evaluación del potencial turístico territorial. Esta metodología se materializa en cuatro instrumentos: la Matriz de Inventario, la Matriz de Vocación Turística (que articula Factores Intrínsecos y Extrínsecos), la Matriz de Potencialidad Turística, y la Matriz de Percepción de la Localidad. Cada uno de estos instrumentos había sido construido como hoja de cálculo independiente, con celdas interrelacionadas por fórmulas, escalas cromáticas y validaciones desplegables.'),
  P('Si bien esta aproximación resultaba adecuada para el trabajo individual, presentaba limitaciones significativas en cuanto a escalabilidad operativa, trazabilidad de la información, colaboración multiusuario y consistencia entre evaluaciones de distintos territorios. La distribución de archivos por correo electrónico introducía riesgos de versionado, pérdida de datos y errores manuales en la copia de fórmulas. Asimismo, la inexistencia de un control de roles imposibilitaba diferenciar las facultades de cada actor del proceso (docente, jefe de zona, equipo de campo) sobre la información ingresada.'),
  H2('1.2 Objetivo general'),
  P('Implementar una plataforma web técnica para la Escuela de Turismo orientada a la sistematización de procesos, mediante la validación de las matrices preexistentes y el desarrollo de módulos digitales para el cálculo de potencialidad y percepción local del territorio.'),
  H2('1.3 Objetivos específicos'),
  bullet('Validar la integridad de los datos y componentes de las matrices de inventario y vocación turística desarrolladas previamente.'),
  bullet('Diseñar una interfaz de usuario intuitiva que facilite el ingreso de variables técnicas y la visualización dinámica de resultados.'),
  bullet('Programar los algoritmos de cálculo y escalas valorativas para los módulos de potencialidad turística y percepción de la localidad.'),
  bullet('Garantizar la trazabilidad de las evaluaciones mediante un sistema de roles diferenciado y control de estados de los registros.'),
  bullet('Disponer la plataforma en un entorno de producción accesible a través de internet, replicable y portable.'),
  new Paragraph({ children: [new PageBreak()] }),
);

// ───────────── 2. MARCO CONCEPTUAL ─────────────
content.push(
  H1('2. Marco conceptual del dominio turístico'),
  divider(),
  P('La correcta implementación del sistema exigió la apropiación previa del marco conceptual que sustenta los instrumentos de evaluación turística empleados por la facultad. A continuación se sintetizan los constructos centrales que subyacen a los cuatro módulos del sistema.'),

  H2('2.1 Diagnóstico turístico territorial'),
  P('El diagnóstico turístico territorial es el proceso técnico mediante el cual se caracteriza un territorio en términos de su capacidad para sostener actividad turística. Articula tres dimensiones: la oferta (recursos, atractivos, planta, infraestructura, facilidades), la demanda (afluencia, perfil del visitante, estadía media) y los factores condicionantes del entorno (marketing, superestructura institucional, organización comunitaria). El resultado del diagnóstico no es un indicador único, sino un conjunto de índices que orientan la planificación posterior.'),

  H2('2.2 Inventario de recursos turísticos'),
  P('El inventario es el registro sistemático y normalizado de los recursos y atractivos turísticos de un territorio. Cumple con la normativa técnica del Ministerio de Turismo del Ecuador, que clasifica los recursos en categorías (Sitios Naturales y Manifestaciones Culturales), tipos y subtipos. Cada ficha de inventario documenta tres bloques: identificación (código, ubicación, denominación, tipología), información básica (descripción, calendario, propiedad, accesibilidad) y características turísticas (demanda, equipamiento, especificidad, valoración).'),
  P('La rigurosidad del inventario condiciona la validez de todos los procesos posteriores: una jerarquización imprecisa de los atractivos distorsiona el cálculo de la vocación y de la potencialidad del territorio.'),

  H2('2.3 Vocación turística territorial (VTT)'),
  P('La vocación turística expresa la aptitud real de un territorio para el desarrollo de actividad turística. Se construye como una combinación ponderada de dos componentes:'),
  bullet([
    T('Factores Intrínsecos Territoriales (FIT)', { bold: true }),
    T(' (60 %): condiciones internas del territorio —recursos, atractivos, prestadores de servicios, producto turístico, infraestructura básica y de apoyo, facilidades turísticas como señalética, centros de interpretación, senderos, estacionamientos, campamentos, miradores y baterías sanitarias—.'),
  ]),
  bullet([
    T('Factores Extrínsecos Territoriales (FET)', { bold: true }),
    T(' (40 %): condiciones del entorno que inciden sobre la actividad —demanda turística, marketing, superestructura institucional, seguridad del destino, imagen percibida y apertura comunitaria—.'),
  ]),
  P('La fórmula VTT = (FIT × 0.60) + (FET × 0.40) produce un valor sobre la escala 0–3 que la metodología interpreta en tres rangos: Alta Vocación (≥ 2.1), Mediana Vocación (1.1 – 2.0) y Baja Vocación (< 1.1).'),

  H2('2.4 Potencialidad turística'),
  P('La potencialidad mide la capacidad real de aprovechamiento turístico del territorio, integrando dos macro-componentes:'),
  bullet([
    T('Factores Endógenos (FN)', { bold: true }),
    T(': aquellos sobre los cuales el territorio tiene control —Recursos Turísticos (40 %), Planta Turística (20 %), Tipologías de Turismo (20 %) e Infraestructura (20 %)—.'),
  ]),
  bullet([
    T('Factores Exógenos (FX)', { bold: true }),
    T(': condiciones externas al territorio —Afluencia Turística (40 %), Marketing Turístico (30 %) y Superestructura (30 %)—.'),
  ]),
  P('El resultado se grafica en un plano cartesiano cuyos ejes representan FN y FX (escala 0–2), generando cuatro cuadrantes interpretativos: Alto Potencial (FN ≥ 1 y FX ≥ 1), Potencial Endógeno – Demanda Limitada (FN ≥ 1, FX < 1), Potencial Exógeno – Oferta Limitada (FN < 1, FX ≥ 1) y Bajo Potencial (FN < 1, FX < 1).'),
  P('La escala valorativa de cada atributo opera bajo un código cromático tricolor: 0 (rojo) ausencia o estado crítico, 1 (amarillo) fragilidad o desarrollo intermedio, y 2 (verde) estado aprovechable u óptimo.'),

  H2('2.5 Percepción local hacia el turismo'),
  P('La percepción local mide el grado de receptividad, conocimiento e involucramiento de la comunidad anfitriona frente a la actividad turística. A diferencia de los anteriores instrumentos, no evalúa el territorio desde una perspectiva técnica sino desde la dimensión humana y social. El instrumento se organiza en cuatro categorías ponderadas:'),
  bullet([T('Dimensión Social (DS)', { bold: true }), T(' — 20 %, con 3 atributos.')]),
  bullet([T('Percepción Local (PL)', { bold: true }), T(' — 40 %, con 6 atributos.')]),
  bullet([T('Percepción Económica (PE)', { bold: true }), T(' — 20 %, con 3 atributos.')]),
  bullet([T('Nivel de Organización (NO)', { bold: true }), T(' — 20 %, con 4 atributos.')]),
  P('Cada atributo se valora en una escala ternaria —1 Negativo, 2 Neutral, 3 Positivo—. El índice global se obtiene como la suma de las contribuciones ponderadas por categoría, normalizado y expresado como porcentaje de cumplimiento.'),
  new Paragraph({ children: [new PageBreak()] }),
);

// ───────────── 3. ARQUITECTURA DEL SISTEMA ─────────────
content.push(
  H1('3. Arquitectura del sistema'),
  divider(),

  H2('3.1 Stack tecnológico'),
  P('El sistema fue construido sobre tecnologías ampliamente adoptadas en la industria, priorizando la madurez del ecosistema, la facilidad de mantenimiento y la portabilidad del entorno de despliegue.'),

  new Table({
    width: { size: 9360, type: WidthType.DXA },
    columnWidths: [3120, 6240],
    rows: [
      new TableRow({
        tableHeader: true,
        children: [
          headerCell('Capa', 3120),
          headerCell('Tecnología', 6240),
        ],
      }),
      ...[
        ['Backend',              'Laravel 12 sobre PHP 8.2 (arquitectura MVC, ORM Eloquent, sistema de migraciones)'],
        ['Frontend',             'Blade Templates, Tailwind CSS 3, Alpine.js, Chart.js 4 para visualización'],
        ['Compilación',          'Vite con plugin Laravel-Vite para bundling y hot module replacement en desarrollo'],
        ['Base de datos',        'PostgreSQL en producción; SQLite en desarrollo local'],
        ['Autenticación',        'Laravel Breeze con sesiones cifradas y CSRF token por formulario'],
        ['Contenedorización',    'Docker multi-stage build (Node 20 → PHP 8.2 Apache)'],
        ['Plataforma cloud',     'Render (Web Service + PostgreSQL gestionado)'],
        ['Control de versiones', 'Git + GitHub con despliegue continuo (auto-deploy on push)'],
      ].map(([k, v]) => new TableRow({
        children: [
          cell([new Paragraph({ children: [T(k, { bold: true })] })], { width: { size: 3120, type: WidthType.DXA } }),
          cell([new Paragraph({ children: [T(v)] })], { width: { size: 6240, type: WidthType.DXA } }),
        ],
      })),
    ],
  }),
  spacer(),

  H2('3.2 Patrón Modelo–Vista–Controlador'),
  P('La aplicación sigue estrictamente el patrón MVC consagrado por Laravel:'),
  bullet([T('Modelos', { bold: true }), T(' (app/Models): clases Eloquent que mapean cada tabla relacional —Lugar, Zona, Inventario, EvaluacionFit, EvaluacionFet, VocacionTuristicaTerritorio, EvaluacionPotencialidad, EvaluacionPercepcion, User, Role— y encapsulan las relaciones (hasMany, belongsTo, belongsToMany).')]),
  bullet([T('Controladores', { bold: true }), T(' (app/Http/Controllers): organizados en dos espacios de nombres —Admin y Operativo— que reflejan la separación entre gestión y evaluación operativa. Cada matriz cuenta con su propio controlador especializado.')]),
  bullet([T('Vistas', { bold: true }), T(' (resources/views): plantillas Blade jerárquicamente organizadas por dominio (admin/, operativo/, auth/, components/), con uso intensivo de Blade Components reutilizables como select-0-3 y select-percepcion.')]),

  H2('3.3 Modelo de datos'),
  P('El esquema relacional se compone de las siguientes entidades principales y sus cardinalidades:'),
  num('lugares — registra los lugares geográficos contenedores (cantón, parroquia).'),
  num('zonas — pertenecen a un lugar (N:1) y son la unidad operativa de evaluación; vinculan un Jefe (1:1 con users) y un equipo (N:M con users a través de zona_equipo).'),
  num('inventarios — fichas de recursos turísticos pertenecientes a una zona; cada inventario admite imágenes en relación 1:N con inventario_imagenes.'),
  num('evaluaciones_fit, evaluaciones_fet, vocacion_turistica_territorio — registros únicos por zona con los resultados de los cálculos FIT, FET y VTT respectivamente.'),
  num('evaluaciones_potencialidad — registro único por zona con todos los atributos de potencialidad y los índices FN y FX calculados.'),
  num('potencialidad_campos_activos — almacena la configuración dinámica de campos aplicables a cada zona (vector serializado en JSON).'),
  num('evaluaciones_percepcion — registro único por zona con los 16 atributos de percepción, los promedios y ponderados por categoría, y el índice total.'),
  num('users, roles — gestión de usuarios e identificación de su rol funcional (Administrador, Jefe de Zona, Equipo Operativo).'),

  H2('3.4 Sistema de roles y control de acceso'),
  P('Se implementaron tres roles con responsabilidades diferenciadas:'),

  new Table({
    width: { size: 9360, type: WidthType.DXA },
    columnWidths: [2340, 1560, 5460],
    rows: [
      new TableRow({
        tableHeader: true,
        children: [
          headerCell('Rol', 2340),
          headerCell('role_id', 1560),
          headerCell('Atribuciones', 5460),
        ],
      }),
      ...[
        ['Administrador',     '1', 'Gestiona usuarios, lugares, zonas y consulta los resultados de todas las matrices en cualquier zona del sistema.'],
        ['Jefe de Zona',      '2', 'Opera sobre las zonas que lidera: ingresa inventarios, evalúa matrices, configura los campos aplicables y confirma (valida) las evaluaciones para cerrar su edición.'],
        ['Equipo Operativo',  '3', 'Participa en el ingreso de información de evaluación en estado borrador; no puede confirmar ni editar evaluaciones ya cerradas por el Jefe.'],
      ].map(([rol, id, atr]) => new TableRow({
        children: [
          cell([new Paragraph({ children: [T(rol, { bold: true })] })], { width: { size: 2340, type: WidthType.DXA } }),
          cell([new Paragraph({ alignment: AlignmentType.CENTER, children: [T(id)] })], { width: { size: 1560, type: WidthType.DXA } }),
          cell([new Paragraph({ children: [T(atr)] })], { width: { size: 5460, type: WidthType.DXA } }),
        ],
      })),
    ],
  }),
  spacer(),
  P('El control de acceso se aplica mediante middlewares personalizados —IsAdmin y isPersonal— que filtran las rutas correspondientes y, dentro de cada controlador, mediante verificación condicional del role_id del usuario autenticado.'),
  new Paragraph({ children: [new PageBreak()] }),
);

// ───────────── 4. MÓDULOS IMPLEMENTADOS ─────────────
content.push(
  H1('4. Módulos implementados'),
  divider(),

  H2('4.1 Módulo de Inventario Turístico'),
  H3('4.1.1 Funcionalidad'),
  P('El módulo permite el registro normalizado de los recursos turísticos del territorio, alineado a la clasificación oficial del Ministerio de Turismo. Cada ficha de inventario captura: referencia técnica, ubicación, denominación, tipología (categoría / tipo / subtipo), descripción detallada, calendario de utilización, propiedad y organismo responsable, accesibilidad, características de la demanda, equipamiento, especificidad y valoración técnica. Las fichas se almacenan a nivel de zona y admiten una galería de imágenes asociadas.'),
  H3('4.1.2 Validaciones implementadas'),
  P('Se incorporaron validaciones de integridad referencial sobre el formulario: campos obligatorios para identificación, control de tipos numéricos en valoración, validación de extensiones permitidas para subida de imágenes y normalización de cadenas de texto para evitar inconsistencias de formato.'),

  H2('4.2 Módulo de Vocación Turística'),
  H3('4.2.1 Componentes FIT y FET'),
  P('La vocación se calcula mediante la integración de los formularios independientes de Factores Intrínsecos (FIT) y Factores Extrínsecos (FET), implementados respectivamente por los controladores EvaluacionFitController y EvaluacionFetController. Cada formulario expone los componentes con su respectiva ponderación, sobre la escala 0–3.'),
  H3('4.2.2 Cálculo del índice VTT'),
  P('El cálculo se ejecuta en VttController::resultadoFinal($zonaId), que solo se activa cuando ambas evaluaciones FIT y FET poseen estado "confirmado". El método aplica la fórmula vtt = (fit × 0.60) + (fet × 0.40) y persiste el resultado en la tabla vocacion_turistica_territorio junto con la interpretación textual ("Alta Vocación Turística", "Mediana Vocación Turística" o "Baja Vocación Turística") según el rango obtenido.'),
  H3('4.2.3 Reporte gráfico'),
  P('La vista de resultados muestra los componentes individuales (RTt, At, PSt, PTt, I, Ft, Demanda, Marketing, Superestructura, Seguridad, Imagen, Apertura) en un gráfico de radar Chart.js que permite identificar visualmente las fortalezas y debilidades del territorio.'),

  H2('4.3 Módulo de Potencialidad Turística'),
  H3('4.3.1 Configuración dinámica de campos por zona'),
  P('Este módulo introduce una innovación metodológica respecto al modelo original en Excel: la configuración dinámica de campos por zona. El Jefe de Zona selecciona, mediante un panel con acordeones agrupados por área temática, únicamente los campos aplicables a las condiciones geográficas de su territorio. La selección se persiste como un arreglo JSON en la tabla potencialidad_campos_activos.'),
  P('Esta característica resuelve un problema metodológico crítico: en el modelo estático, un territorio de interior se veía penalizado por la ausencia de playas y arrecifes; un territorio costero, por la ausencia de páramos y volcanes. La configuración dinámica garantiza que el índice refleje únicamente lo que es estructuralmente aplicable.'),
  H3('4.3.2 Algoritmo de cálculo ponderado'),
  P('El núcleo computacional reside en EvaluacionPotencialidadController::calcular($valores, $camposActivos). El algoritmo opera en tres pasos:'),
  num('Calcula promedios parciales solo sobre los subgrupos que contienen al menos un campo activo, evitando la dilución del índice por subgrupos vacíos.'),
  num('Redistribuye dinámicamente los pesos entre los macrocomponentes que tienen campos activos: si un macrocomponente está completamente inactivo, su peso se reasigna proporcionalmente a los demás (técnica de re-normalización de pesos).'),
  num('Integra los resultados parciales según la jerarquía de pesos: FN = RT(40 %) + PT(20 %) + TT(20 %) + I(20 %); FX = AT(40 %) + MK(30 %) + ST(30 %).'),
  H3('4.3.3 Diagrama de cuadrantes'),
  P('Los índices FN y FX se grafican en un plano cartesiano (Chart.js scatter) con escala 0–2 en ambos ejes, dividido en cuatro cuadrantes interpretativos. La posición resultante categoriza el territorio en: Alto Potencial Turístico, Potencial Endógeno con Demanda Limitada, Potencial Exógeno con Oferta Limitada, o Bajo Potencial Turístico. Cada cuadrante se acompaña de una banda interpretativa con el diagnóstico textual correspondiente.'),

  H2('4.4 Módulo de Matriz de Percepción de la Localidad'),
  H3('4.4.1 Modelo de datos'),
  P('La migración 2026_04_21_000001_create_evaluaciones_percepcion_table.php define la tabla evaluaciones_percepcion con: foreign key a zonas y users; campo estado (borrador/confirmado); dieciséis columnas unsignedTinyInteger para los atributos (codificados como ds1..ds3, pl1..pl6, pe1..pe3, no1..no4); ocho columnas decimal para las medias y ponderados por categoría; una columna decimal para el índice global; y un campo de texto para acciones de mejora propuestas. La restricción UNIQUE sobre zona_id garantiza una sola evaluación de percepción por territorio.'),
  H3('4.4.2 Algoritmo de cálculo'),
  P('El controlador EvaluacionPercepcionController declara estáticamente la estructura de categorías y pesos. El método privado calcular($v) itera sobre las cuatro categorías, computa el promedio simple de los atributos de cada una, aplica el peso porcentual normalizado por la escala máxima (multiplicando por el peso y dividiendo por tres) y suma las contribuciones para obtener el índice global expresado en escala 0–1, fácilmente interpretable como porcentaje de cumplimiento.'),
  H3('4.4.3 Visualización'),
  P('La vista de resultados (operativo.evaluacion_percepcion.ponderacion) presenta cuatro elementos: una tabla detallada con cada atributo y su etiqueta cromática (rojo/amarillo/verde); la tabla de ponderación por categoría con peso, promedio, valor ponderado y porcentaje de cumplimiento; un banner interpretativo del nivel global (Percepción Favorable ≥ 70 %, Moderada ≥ 40 %, Desfavorable < 40 %); y dos gráficos sincronizados —radar y lineal— construidos con Chart.js. La vista admite un flag $readonly que la habilita también como vista de consulta para el rol Administrador.'),
  H3('4.4.4 Integración con el sistema de roles'),
  P('El módulo se integró tanto al panel operativo (con flujo de borrador / confirmación restringido por rol) como al panel administrativo (ruta admin.zonas.percepcion en modo solo lectura). La vista reutiliza la misma plantilla Blade gracias al flag $readonly, evitando duplicación de código y garantizando consistencia visual.'),
  new Paragraph({ children: [new PageBreak()] }),
);

// ───────────── 5. ALGORITMOS Y FÓRMULAS ─────────────
content.push(
  H1('5. Algoritmos y fórmulas de ponderación'),
  divider(),
  P('Esta sección consolida las fórmulas implementadas en el backend. Todas las operaciones aritméticas se ejecutan en tiempo de servidor (PHP) y los resultados se persisten en columnas DECIMAL(5,3) para preservar la precisión hasta el milésimo.'),

  H2('5.1 Vocación Turística (VTT)'),
  P('VTT = (FIT × 0.60) + (FET × 0.40)', { alignment: AlignmentType.CENTER }),
  P('Donde FIT y FET resultan, a su vez, de la sumatoria ponderada de sus componentes internos. La interpretación cualitativa se define por rangos sobre la escala 0–3.'),

  H2('5.2 Potencialidad Turística'),
  P('FN = Σ (avg_grupoᵢ × pesoᵢ)   para i ∈ {RT, PT, TT, I}', { alignment: AlignmentType.CENTER }),
  P('FX = Σ (avg_grupoⱼ × pesoⱼ)   para j ∈ {AT, MK, ST}', { alignment: AlignmentType.CENTER }),
  P('Donde los pesos se redistribuyen proporcionalmente cuando un grupo no contiene campos activos. La posición (FN, FX) sobre el plano cartesiano determina el cuadrante de potencialidad.'),

  H2('5.3 Percepción de la Localidad'),
  P('Percepción Total = Σ ((avg_categoríaₖ × pesoₖ) / 3)   para k ∈ {DS, PL, PE, NO}', { alignment: AlignmentType.CENTER }),
  P('La división por tres normaliza el resultado a la escala 0–1, donde la escala original es ternaria (1–3). El porcentaje de cumplimiento es Percepción Total × 100.'),

  new Paragraph({ children: [new PageBreak()] }),
);

// ───────────── 6. DESPLIEGUE ─────────────
content.push(
  H1('6. Despliegue en producción'),
  divider(),

  H2('6.1 Contenedorización con Docker'),
  P('Se diseñó un Dockerfile multi-etapa que separa la fase de compilación de assets (Node 20 Alpine + Vite) de la fase de runtime (PHP 8.2 con Apache). Esta arquitectura optimiza el tamaño final de la imagen al excluir node_modules y las herramientas de build del contenedor productivo.'),
  P('La imagen runtime instala las extensiones PHP requeridas (pdo_pgsql, pdo_mysql, mbstring, zip, exif, pcntl, bcmath, gd, intl), Composer en su versión 2 y configura Apache con DocumentRoot apuntando a /var/www/html/public y módulo rewrite habilitado para soportar la URL refactorización de Laravel.'),

  H2('6.2 Script de inicialización (entrypoint)'),
  P('El script docker/entrypoint.sh ejecuta, en cada arranque del contenedor: la creación del symlink storage:link (con detección automática de disco persistente cuando está disponible), el cacheo de configuración, rutas y vistas (config:cache, route:cache, view:cache), la ejecución de las migraciones pendientes (migrate --force) y, si la tabla users se encuentra vacía, el seeding inicial automático.'),

  H2('6.3 Plataforma Render'),
  P('El despliegue se orquesta mediante un archivo render.yaml (Render Blueprint) que declara: una base de datos PostgreSQL gestionada (plan free), un Web Service tipo Docker con auto-deploy desde la rama main, y la inyección de las variables de entorno necesarias —incluyendo APP_KEY (clave AES-256 base64), conexión a la base de datos por referencia cruzada al recurso turismo-db, y configuración de drivers (session, cache, queue) sobre base de datos para evitar dependencias de Redis—.'),

  H2('6.4 Detección de HTTPS detrás del proxy'),
  P('Render expone el servicio detrás de un proxy reverso que termina TLS y propaga el esquema original mediante la cabecera X-Forwarded-Proto. Para que Laravel genere correctamente URLs HTTPS en los enlaces a assets (Vite manifest), se configuró el middleware TrustProxies en bootstrap/app.php con la directiva trustProxies(at: \'*\'), evitando así el bloqueo de recursos por mixed content.'),

  new Paragraph({ children: [new PageBreak()] }),
);

// ───────────── 7. PRÁCTICAS DE INGENIERÍA ─────────────
content.push(
  H1('7. Prácticas de ingeniería y aseguramiento de calidad'),
  divider(),

  H2('7.1 Control de versiones y despliegue continuo'),
  P('Todo el código fuente está versionado con Git y alojado en repositorio GitHub. Cualquier commit a la rama main desencadena automáticamente un proceso de build y despliegue en Render. Esta práctica de continuous deployment elimina la fricción del despliegue manual y permite iteraciones rápidas sobre el sistema en producción.'),

  H2('7.2 Validación de entradas y control de estados'),
  P('Cada formulario valida los datos en el servidor mediante reglas explícitas del Validator de Laravel (integer, min, max, required). La verificación se complementa con el control de estados borrador/confirmado, que impide ediciones sobre evaluaciones cerradas. La interfaz aplica adicionalmente atributos disabled sobre los controles cuando corresponde, pero la validación de fondo siempre se realiza del lado del servidor.'),

  H2('7.3 Protección contra ataques comunes'),
  P('La plataforma hereda de Laravel sus mecanismos estándar de seguridad: tokens CSRF en todos los formularios, escapado automático de las salidas Blade ({{ }}) para prevenir XSS, parámetros enlazados en las consultas Eloquent que mitigan SQL injection, hash bcrypt de las contraseñas con factor 12, y sesiones cifradas firmadas con la APP_KEY.'),

  H2('7.4 Separación de entornos'),
  P('El proyecto distingue claramente los entornos de desarrollo y producción mediante variables de entorno (.env). En desarrollo se utiliza SQLite y APP_DEBUG=true; en producción, PostgreSQL y APP_DEBUG=false. Las rutas, vistas y configuración se cachean únicamente en producción para optimizar el rendimiento.'),

  new Paragraph({ children: [new PageBreak()] }),
);

// ───────────── 8. CONCLUSIONES ─────────────
content.push(
  H1('8. Conclusiones'),
  divider(),
  P('UDAExplore evidencia que es viable migrar instrumentos técnicos de evaluación territorial desde el entorno estático de hojas de cálculo hacia un sistema web colaborativo sin sacrificar la rigurosidad metodológica que los sustenta. El proceso requirió, ante todo, comprender en profundidad la materia turística: las fórmulas no son arbitrarias, los pesos no son cosméticos, y la escala valorativa codifica un significado técnico preciso. Cualquier traducción superficial habría comprometido la validez del diagnóstico.'),
  P('La introducción de la configuración dinámica de campos en el módulo de Potencialidad constituye una mejora metodológica respecto al modelo original: el sistema reconoce que un territorio no debe ser penalizado por carencias estructuralmente inaplicables, y reasigna los pesos de manera proporcional. Esta característica, técnicamente sencilla, posee implicaciones científicas relevantes en la calidad del diagnóstico final.'),
  P('El sistema de roles de tres niveles (Administrador, Jefe de Zona, Equipo Operativo) demostró ser una arquitectura acertada para reflejar la cadena de responsabilidades del trabajo de campo: el Equipo construye, el Jefe valida, el Administrador supervisa. La restricción de edición posterior a la confirmación protege la integridad analítica del registro.'),
  P('La generación automatizada de reportes visuales —diagramas de dispersión por cuadrantes para Potencialidad, gráficas de radar y lineales para Percepción— transforma datos crudos en diagnósticos interpretables, lo que extiende el alcance del sistema más allá del aula universitaria y lo proyecta como una herramienta de apoyo a la toma de decisiones en gestión turística pública y comunitaria.'),
  P('Finalmente, la experiencia confirma que el desarrollo de software aplicado a contextos académicos exige un trabajo estrecho entre el ingeniero y los expertos del dominio. La pregunta correcta no es "qué tecnología usamos", sino "qué metodología estamos digitalizando y cómo preservamos su sentido". Esta es, en definitiva, la enseñanza más relevante del proyecto.'),
  new Paragraph({ children: [new PageBreak()] }),
);

// ───────────── 9. REFERENCIAS ─────────────
content.push(
  H1('9. Referencias'),
  divider(),
  P('Arias, M. A. (2013). Introducción a PHP. IT Campus Academy.'),
  P('Casado-Vara, R. (2019). Introducción a HTML. Campus Academy.'),
  P('Grant, K. J. (2024). CSS in Depth. Simon and Schuster.'),
  P('Laravel LLC. (2025). Laravel 12.x Documentation. https://laravel.com/docs/12.x'),
  P('Ministerio de Turismo del Ecuador. (2017). Manual de Atractivos Turísticos: Metodología para la jerarquización de atractivos y generación de espacios turísticos. Quito.'),
  P('OMT — Organización Mundial del Turismo. (2019). Definiciones de Turismo de la OMT. UNWTO. https://www.unwto.org'),
  P('Otwell, T. (2025). Eloquent ORM Reference Guide. Laravel Documentation.'),
  P('Pesántez López, J. P. (2026). Informe de Prácticas Pre-Profesionales: Desarrollo de plataforma web para la validación y gestión de matrices de diagnóstico y potencial turístico. Universidad del Azuay.'),
  P('Render Services Inc. (2025). Render Blueprint Specification. https://render.com/docs/blueprint-spec'),
  P('Schulz, R. G. (2009). Diseño web con CSS. Marcombo.'),
  P('Spona, H. (2010). Programación de bases de datos con MySQL y PHP. Marcombo.'),
);

// ============================================================
//  DOCUMENT ASSEMBLY
// ============================================================
const doc = new Document({
  creator: 'Juan Pedro Pesántez López',
  title: 'Informe Técnico UDAExplore',
  description: 'Documentación técnica del proyecto UDAExplore — Plataforma Web para Gestión Turística',
  styles: {
    default: {
      document: { run: { font: FONT, size: 22 } }, // 11pt
    },
    paragraphStyles: [
      { id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 36, bold: true, font: FONT, color: PALETTE.primary },
        paragraph: { spacing: { before: 360, after: 200 }, outlineLevel: 0 } },
      { id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 28, bold: true, font: FONT, color: PALETTE.secondary },
        paragraph: { spacing: { before: 280, after: 160 }, outlineLevel: 1 } },
      { id: 'Heading3', name: 'Heading 3', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 24, bold: true, font: FONT, color: PALETTE.accent },
        paragraph: { spacing: { before: 220, after: 120 }, outlineLevel: 2 } },
    ],
  },
  numbering: {
    config: [
      { reference: 'bullets',
        levels: [{ level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } }] },
      { reference: 'numbers',
        levels: [{ level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } }] },
    ],
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 },
      },
    },
    headers: {
      default: new Header({
        children: [new Paragraph({
          alignment: AlignmentType.RIGHT,
          border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: PALETTE.primary } },
          children: [T('UDAExplore — Informe Técnico', { size: 18, color: PALETTE.muted, italics: true })],
        })],
      }),
    },
    footers: {
      default: new Footer({
        children: [new Paragraph({
          alignment: AlignmentType.CENTER,
          children: [
            T('Página ', { size: 18, color: PALETTE.muted }),
            new TextRun({ children: [PageNumber.CURRENT], size: 18, color: PALETTE.muted, font: FONT }),
            T(' de ', { size: 18, color: PALETTE.muted }),
            new TextRun({ children: [PageNumber.TOTAL_PAGES], size: 18, color: PALETTE.muted, font: FONT }),
          ],
        })],
      }),
    },
    children: content,
  }],
});

Packer.toBuffer(doc).then((buffer) => {
  fs.writeFileSync(__dirname + '/Informe_Tecnico_UDAExplore.docx', buffer);
  console.log('✓ DOCX creado: Informe_Tecnico_UDAExplore.docx');
});
