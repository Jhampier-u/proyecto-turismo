// Generates UDAExplore presentation — Midnight Executive palette
const PptxGenJS = require('pptxgenjs');

const pres = new PptxGenJS();
pres.layout = 'LAYOUT_WIDE'; // 13.333 × 7.5 in
pres.title = 'UDAExplore — Plataforma Web para Gestión Turística';
pres.author = 'Juan Pedro Pesántez López';
pres.company = 'Universidad del Azuay';

// ───────── Palette
const C = {
  navy:   '0F1E5C', // primary dark
  deep:   '1E2761', // header bg
  ice:    'CADCFC', // soft accent
  white:  'FFFFFF',
  cream:  'F4F7FB',
  amber:  'F4B942', // bright accent
  coral:  'F26A4B', // secondary accent
  teal:   '4ECDC4', // tech accent
  text:   '0F172A',
  muted:  '64748B',
  green:  '22C55E',
  red:    'EF4444',
  yellow: 'EAB308',
};
const FONT = 'Calibri';
const FONT_H = 'Calibri';

// ───────── helpers
const W = 13.333, H = 7.5;

function bgSlide(opts = {}) {
  const slide = pres.addSlide();
  slide.background = { color: opts.bg || C.cream };
  return slide;
}

// Page number footer (subtle)
function footer(slide, n, total) {
  slide.addText(`${n} / ${total}`, {
    x: W - 1.2, y: H - 0.45, w: 1, h: 0.3,
    fontFace: FONT, fontSize: 9, color: C.muted, align: 'right',
  });
  slide.addText('UDAExplore', {
    x: 0.5, y: H - 0.45, w: 3, h: 0.3,
    fontFace: FONT, fontSize: 9, color: C.muted, italic: true,
  });
}

// Title with subtle eyebrow text on top — no underline (the spec warns against accent lines)
function slideTitle(slide, eyebrow, title) {
  slide.addText(eyebrow, {
    x: 0.5, y: 0.45, w: 12, h: 0.35,
    fontFace: FONT, fontSize: 12, color: C.deep, bold: true, charSpacing: 4,
  });
  slide.addText(title, {
    x: 0.5, y: 0.85, w: 12.3, h: 0.9,
    fontFace: FONT_H, fontSize: 32, bold: true, color: C.text,
  });
}

const TOTAL = 15;
let slideN = 0;

// ============================================================
// SLIDE 1 — Cover
// ============================================================
{
  slideN++;
  const s = pres.addSlide();
  s.background = { color: C.navy };

  // Diagonal accent shape (top-right)
  s.addShape('rect', { x: 9.5, y: 0, w: 3.83, h: 7.5, fill: { color: C.deep }, line: { color: C.deep } });
  s.addShape('ellipse', { x: 10.5, y: 1.0, w: 2.5, h: 2.5, fill: { color: C.amber }, line: { color: C.amber } });
  s.addShape('ellipse', { x: 10.0, y: 4.5, w: 1.6, h: 1.6, fill: { color: C.teal }, line: { color: C.teal } });

  // Eyebrow
  s.addText('UNIVERSIDAD DEL AZUAY · ESCUELA DE TURISMO', {
    x: 0.6, y: 1.0, w: 9, h: 0.35,
    fontFace: FONT, fontSize: 12, color: C.ice, bold: true, charSpacing: 6,
  });

  // Title
  s.addText('UDAExplore', {
    x: 0.6, y: 1.6, w: 10, h: 1.6,
    fontFace: FONT_H, fontSize: 80, bold: true, color: C.white,
  });
  s.addText('Plataforma web para la evaluación del\npotencial turístico territorial', {
    x: 0.6, y: 3.4, w: 9, h: 1.4,
    fontFace: FONT_H, fontSize: 24, italic: true, color: C.ice,
  });

  // Divider rectangle (NOT under title; it's a credit block separator)
  s.addShape('rect', { x: 0.6, y: 5.4, w: 0.5, h: 0.07, fill: { color: C.amber }, line: { color: C.amber } });

  s.addText('Juan Pedro Pesántez López', {
    x: 0.6, y: 5.55, w: 8, h: 0.45,
    fontFace: FONT, fontSize: 20, bold: true, color: C.white,
  });
  s.addText('Prácticas Pre-Profesionales · Ingeniería en Ciencias de la Computación', {
    x: 0.6, y: 6.0, w: 9, h: 0.35,
    fontFace: FONT, fontSize: 14, color: C.ice,
  });
  s.addText('Tutor: Lic. Ronal Edison Chaca Espinoza, Mg., PhD.', {
    x: 0.6, y: 6.4, w: 9, h: 0.3,
    fontFace: FONT, fontSize: 12, italic: true, color: C.ice,
  });

  s.addText('Cuenca · Ecuador  ·  2026', {
    x: 0.6, y: 6.95, w: 8, h: 0.3,
    fontFace: FONT, fontSize: 11, color: C.ice, charSpacing: 3,
  });
}

// ============================================================
// SLIDE 2 — El problema
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '01 · EL PUNTO DE PARTIDA', 'Cuatro hojas de cálculo, cero trazabilidad');

  // Left: narrative
  s.addText(
    'La Escuela de Turismo desarrolló una metodología robusta para diagnosticar el potencial turístico territorial. El instrumento técnico estaba completo, pero su soporte operativo presentaba fricciones que limitaban su utilidad real.',
    { x: 0.5, y: 2.0, w: 5.8, h: 2.2, fontFace: FONT, fontSize: 15, color: C.text, valign: 'top' }
  );

  // Right: pain points as cards
  const issues = [
    ['📁', 'Archivos dispersos', 'Cuatro Excel independientes, circulando por correo, con riesgo de versionado y pérdida.'],
    ['👥', 'Sin trabajo colaborativo', 'Imposible que múltiples evaluadores trabajen sobre el mismo territorio al mismo tiempo.'],
    ['🔒', 'Cero control de acceso', 'No se distingue quién carga, quién valida, quién consulta. Todo igual de editable.'],
    ['📊', 'Reportes manuales', 'Los gráficos y diagnósticos exigían copia-pegado entre hojas y formatos.'],
  ];
  const cardX = 6.7, cardW = 6.2, cardH = 1.05, gap = 0.18;
  issues.forEach(([icon, title, desc], i) => {
    const y = 1.95 + i * (cardH + gap);
    s.addShape('roundRect', {
      x: cardX, y, w: cardW, h: cardH,
      fill: { color: C.white }, line: { color: C.ice, width: 1 },
      rectRadius: 0.08,
    });
    s.addText(icon, { x: cardX + 0.15, y: y + 0.15, w: 0.7, h: 0.75, fontFace: FONT, fontSize: 28, align: 'center' });
    s.addText(title, { x: cardX + 0.9, y: y + 0.1, w: cardW - 1.0, h: 0.4, fontFace: FONT_H, fontSize: 14, bold: true, color: C.deep });
    s.addText(desc, { x: cardX + 0.9, y: y + 0.45, w: cardW - 1.0, h: 0.55, fontFace: FONT, fontSize: 11, color: C.muted });
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 3 — La solución
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '02 · LA SOLUCIÓN', 'Una plataforma única que preserva el rigor metodológico');

  s.addText(
    'UDAExplore digitaliza los cuatro instrumentos sin sacrificar la rigurosidad académica. Centraliza la información, automatiza los cálculos y genera reportes interpretables. Lo que antes tomaba días, ahora ocurre en minutos.',
    { x: 0.5, y: 1.9, w: 12.3, h: 1.0, fontFace: FONT, fontSize: 16, color: C.text }
  );

  const benefits = [
    ['🎯', 'Un único entorno', 'Las 4 matrices integradas bajo un mismo flujo de trabajo.', C.amber],
    ['⚡', 'Cálculo automático', 'Algoritmos ponderados ejecutados en tiempo real al guardar.', C.teal],
    ['📈', 'Reportes visuales', 'Radares, cuadrantes y dashboards generados sin intervención manual.', C.coral],
    ['🔐', 'Roles y validación', 'Cada acción auditada por usuario, estado y fecha.', C.deep],
  ];
  const bx = 0.5, by = 3.3, bw = 3.05, bh = 3.3, bgap = 0.16;
  benefits.forEach(([icon, title, desc, color], i) => {
    const x = bx + i * (bw + bgap);
    // Top accent block
    s.addShape('rect', { x, y: by, w: bw, h: 0.4, fill: { color }, line: { color } });
    s.addShape('rect', { x, y: by + 0.4, w: bw, h: bh - 0.4, fill: { color: C.white }, line: { color: C.ice, width: 1 } });
    s.addText(icon, { x, y: by + 0.7, w: bw, h: 0.9, fontFace: FONT, fontSize: 44, align: 'center' });
    s.addText(title, { x: x + 0.2, y: by + 1.7, w: bw - 0.4, h: 0.5, fontFace: FONT_H, fontSize: 18, bold: true, color: C.text, align: 'center' });
    s.addText(desc, { x: x + 0.2, y: by + 2.2, w: bw - 0.4, h: 1.0, fontFace: FONT, fontSize: 12, color: C.muted, align: 'center' });
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 4 — Marco teórico
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '03 · FUNDAMENTO TURÍSTICO', 'Cuatro instrumentos, una metodología');

  s.addText(
    'La metodología desarrollada por la facultad articula cuatro dimensiones complementarias del diagnóstico territorial. Cada matriz responde a una pregunta distinta sobre el destino.',
    { x: 0.5, y: 1.9, w: 12.3, h: 0.9, fontFace: FONT, fontSize: 14, color: C.muted, italic: true }
  );

  const matrices = [
    ['Inventario', '¿Qué tenemos?', 'Registro normalizado de los recursos turísticos del territorio según la clasificación oficial del MINTUR.', C.amber],
    ['Vocación (VTT)', '¿Para qué sirve?', 'Aptitud real del territorio = (FIT × 60 %) + (FET × 40 %). Tres rangos: Alta, Media o Baja vocación.', C.teal],
    ['Potencialidad', '¿Hasta dónde llega?', 'Cruce de Factores Endógenos (FN) y Exógenos (FX) sobre cuatro cuadrantes interpretativos de potencial.', C.coral],
    ['Percepción', '¿Cómo lo vive la comunidad?', 'Receptividad e involucramiento de la población local frente al turismo, en 16 atributos.', C.deep],
  ];
  const mx = 0.5, my = 2.95, mw = 6.2, mh = 1.85, mgap = 0.2;
  matrices.forEach(([name, q, desc, color], i) => {
    const x = mx + (i % 2) * (mw + mgap);
    const y = my + Math.floor(i / 2) * (mh + mgap);
    s.addShape('roundRect', { x, y, w: mw, h: mh, fill: { color: C.white }, line: { color: C.ice, width: 1 }, rectRadius: 0.1 });
    // Color tag on the left
    s.addShape('rect', { x, y, w: 0.15, h: mh, fill: { color }, line: { color } });
    s.addText(name, { x: x + 0.35, y: y + 0.15, w: 3.0, h: 0.5, fontFace: FONT_H, fontSize: 22, bold: true, color: C.text });
    s.addText(q, { x: x + 0.35, y: y + 0.65, w: mw - 0.5, h: 0.35, fontFace: FONT, fontSize: 13, italic: true, color: color });
    s.addText(desc, { x: x + 0.35, y: y + 1.0, w: mw - 0.5, h: 0.85, fontFace: FONT, fontSize: 11.5, color: C.muted });
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 5 — Arquitectura
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '04 · ARQUITECTURA', 'Tres capas, separación limpia de responsabilidades');

  // Stack diagram
  const layers = [
    { name: 'PRESENTACIÓN', tech: 'Blade Templates · Tailwind CSS · Alpine.js · Chart.js', color: C.coral, icon: '🎨' },
    { name: 'LÓGICA DE NEGOCIO', tech: 'Laravel 12 · PHP 8.2 · MVC · Eloquent ORM · Middlewares', color: C.amber, icon: '⚙️' },
    { name: 'PERSISTENCIA', tech: 'PostgreSQL (prod) · SQLite (dev) · Migraciones versionadas', color: C.teal, icon: '🗄️' },
  ];

  const lx = 0.7, ly = 2.05, lw = 7.5, lh = 1.1, lgap = 0.2;
  layers.forEach((l, i) => {
    const y = ly + i * (lh + lgap);
    s.addShape('roundRect', { x: lx, y, w: lw, h: lh, fill: { color: l.color }, line: { color: l.color }, rectRadius: 0.08 });
    s.addText(l.icon, { x: lx + 0.2, y: y + 0.15, w: 0.9, h: 0.8, fontFace: FONT, fontSize: 36, align: 'center' });
    s.addText(l.name, { x: lx + 1.1, y: y + 0.15, w: lw - 1.3, h: 0.4, fontFace: FONT_H, fontSize: 16, bold: true, color: C.white, charSpacing: 4 });
    s.addText(l.tech, { x: lx + 1.1, y: y + 0.55, w: lw - 1.3, h: 0.5, fontFace: FONT, fontSize: 13, color: C.white });
  });

  // Right side: deployment box
  s.addShape('roundRect', { x: 8.6, y: 2.05, w: 4.25, h: 3.85, fill: { color: C.white }, line: { color: C.ice, width: 1 }, rectRadius: 0.1 });
  s.addText('☁️', { x: 8.6, y: 2.2, w: 4.25, h: 0.7, fontFace: FONT, fontSize: 36, align: 'center' });
  s.addText('DESPLIEGUE', { x: 8.6, y: 2.95, w: 4.25, h: 0.4, fontFace: FONT_H, fontSize: 16, bold: true, color: C.deep, align: 'center', charSpacing: 4 });
  s.addText(
    '· Docker multi-stage build\n· Render Web Service\n· PostgreSQL gestionado\n· CI/CD con GitHub\n· Auto-deploy en cada push\n· TrustProxies para HTTPS',
    { x: 8.9, y: 3.45, w: 3.7, h: 2.4, fontFace: FONT, fontSize: 12.5, color: C.text, paraSpaceAfter: 4 }
  );

  // Bottom callout
  s.addShape('roundRect', { x: 0.7, y: 6.0, w: 12.15, h: 0.85, fill: { color: C.deep }, line: { color: C.deep }, rectRadius: 0.1 });
  s.addText('🔧  Sistema bajo Git + GitHub  ·  Despliegue continuo  ·  Entornos separados (dev / prod)  ·  Variables de entorno seguras', {
    x: 0.85, y: 6.05, w: 11.85, h: 0.75, fontFace: FONT, fontSize: 13, color: C.white, valign: 'middle',
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 6 — Stack tecnológico
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '05 · STACK TECNOLÓGICO', 'Herramientas maduras, ampliamente adoptadas');

  const stack = [
    ['Laravel 12',    'PHP 8.2 · Framework',       C.coral],
    ['PostgreSQL',    'Base de datos relacional',  C.teal],
    ['Tailwind CSS',  'Diseño utility-first',      C.amber],
    ['Blade',         'Motor de plantillas',       C.deep],
    ['Chart.js 4',    'Visualización dinámica',    C.coral],
    ['Alpine.js',     'Reactividad ligera',        C.teal],
    ['Vite',          'Bundler / HMR',             C.amber],
    ['Docker',        'Contenedorización',         C.deep],
    ['Render',        'Plataforma cloud',          C.coral],
    ['Git + GitHub',  'Control de versiones · CI', C.teal],
    ['Composer',      'Gestor de paquetes PHP',    C.amber],
    ['Eloquent ORM',  'Mapeo objeto-relacional',   C.deep],
  ];

  const cols = 4, rows = 3;
  const sx = 0.6, sy = 1.95, sw = 3.0, sh = 1.5, sgapX = 0.13, sgapY = 0.15;
  stack.forEach(([name, role, color], i) => {
    const col = i % cols, row = Math.floor(i / cols);
    const x = sx + col * (sw + sgapX);
    const y = sy + row * (sh + sgapY);
    s.addShape('roundRect', { x, y, w: sw, h: sh, fill: { color: C.white }, line: { color: C.ice, width: 1 }, rectRadius: 0.08 });
    s.addShape('ellipse', { x: x + 0.25, y: y + 0.42, w: 0.45, h: 0.45, fill: { color }, line: { color } });
    s.addText(name, { x: x + 0.85, y: y + 0.3, w: sw - 0.95, h: 0.45, fontFace: FONT_H, fontSize: 16, bold: true, color: C.text });
    s.addText(role, { x: x + 0.85, y: y + 0.75, w: sw - 0.95, h: 0.45, fontFace: FONT, fontSize: 11.5, color: C.muted });
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 7 — Inventario
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '06 · MÓDULO 1', 'Inventario de recursos turísticos');

  // Left description
  s.addText('¿Qué es?', { x: 0.5, y: 1.95, w: 6, h: 0.4, fontFace: FONT_H, fontSize: 16, bold: true, color: C.deep });
  s.addText(
    'Registro normalizado de cada recurso turístico, alineado a la clasificación oficial del Ministerio de Turismo del Ecuador (MINTUR).',
    { x: 0.5, y: 2.35, w: 6, h: 1.2, fontFace: FONT, fontSize: 14, color: C.text }
  );

  s.addText('Tres bloques de información', { x: 0.5, y: 3.6, w: 6, h: 0.4, fontFace: FONT_H, fontSize: 16, bold: true, color: C.deep });
  const blocks = [
    ['Identificación', 'Referencia · Ubicación · Denominación · Categoría / Tipo / Subtipo'],
    ['Información básica', 'Descripción · Calendario · Propiedad · Accesibilidad'],
    ['Características turísticas', 'Demanda · Equipamiento · Especificidad · Valoración'],
  ];
  blocks.forEach(([t, d], i) => {
    const y = 4.05 + i * 0.85;
    s.addShape('rect', { x: 0.5, y: y + 0.05, w: 0.1, h: 0.6, fill: { color: C.amber }, line: { color: C.amber } });
    s.addText(t, { x: 0.75, y, w: 5.5, h: 0.35, fontFace: FONT_H, fontSize: 13.5, bold: true, color: C.text });
    s.addText(d, { x: 0.75, y: y + 0.35, w: 5.5, h: 0.4, fontFace: FONT, fontSize: 11.5, color: C.muted });
  });

  // Right: feature list
  s.addShape('roundRect', { x: 7.0, y: 1.95, w: 5.85, h: 4.6, fill: { color: C.deep }, line: { color: C.deep }, rectRadius: 0.1 });
  s.addText('CARACTERÍSTICAS TÉCNICAS', { x: 7.2, y: 2.1, w: 5.45, h: 0.4, fontFace: FONT_H, fontSize: 14, bold: true, color: C.amber, charSpacing: 4 });
  const features = [
    ['🗂️', 'Fichas por zona', 'Cada zona acumula su propio catálogo'],
    ['🖼️', 'Galería de imágenes', 'Multimedia asociada a cada recurso (1:N)'],
    ['✅', 'Validación de inputs', 'Tipos numéricos, obligatorios, formatos'],
    ['🌳', 'Categorías jerárquicas', 'Tabla auto-referenciada padre/hijo'],
    ['📋', 'Sigue normativa MINTUR', 'Compatibilidad con catastros oficiales'],
  ];
  features.forEach(([icon, t, d], i) => {
    const y = 2.6 + i * 0.78;
    s.addText(icon, { x: 7.2, y, w: 0.55, h: 0.5, fontFace: FONT, fontSize: 22 });
    s.addText(t, { x: 7.85, y: y + 0.02, w: 4.8, h: 0.35, fontFace: FONT_H, fontSize: 14, bold: true, color: C.white });
    s.addText(d, { x: 7.85, y: y + 0.36, w: 4.8, h: 0.32, fontFace: FONT, fontSize: 11, color: C.ice });
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 8 — Vocación (VTT)
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '07 · MÓDULO 2', 'Vocación Turística Territorial (VTT)');

  // Formula card top
  s.addShape('roundRect', { x: 0.5, y: 1.9, w: 12.35, h: 1.1, fill: { color: C.navy }, line: { color: C.navy }, rectRadius: 0.1 });
  s.addText('FÓRMULA OFICIAL', { x: 0.75, y: 1.95, w: 4, h: 0.35, fontFace: FONT, fontSize: 11, bold: true, color: C.amber, charSpacing: 4 });
  s.addText('VTT  =  (FIT × 0.60)  +  (FET × 0.40)', {
    x: 0.5, y: 2.25, w: 12.35, h: 0.7,
    fontFace: 'Consolas', fontSize: 28, bold: true, color: C.white, align: 'center',
  });

  // Two pills FIT and FET
  s.addShape('roundRect', { x: 0.5, y: 3.25, w: 6.1, h: 1.8, fill: { color: C.white }, line: { color: C.coral, width: 2 }, rectRadius: 0.1 });
  s.addText('FIT', { x: 0.7, y: 3.4, w: 1.5, h: 0.5, fontFace: FONT_H, fontSize: 28, bold: true, color: C.coral });
  s.addText('Factores Intrínsecos Territoriales · 60 %', { x: 2.2, y: 3.45, w: 4.3, h: 0.4, fontFace: FONT_H, fontSize: 13, bold: true, color: C.deep });
  s.addText(
    'Recursos · Atractivos · Prestadores · Producto · Infraestructura · Facilidades (señalética, senderos, baterías, miradores...)',
    { x: 0.7, y: 4.0, w: 5.7, h: 1.0, fontFace: FONT, fontSize: 12, color: C.text }
  );

  s.addShape('roundRect', { x: 6.75, y: 3.25, w: 6.1, h: 1.8, fill: { color: C.white }, line: { color: C.teal, width: 2 }, rectRadius: 0.1 });
  s.addText('FET', { x: 6.95, y: 3.4, w: 1.5, h: 0.5, fontFace: FONT_H, fontSize: 28, bold: true, color: C.teal });
  s.addText('Factores Extrínsecos Territoriales · 40 %', { x: 8.45, y: 3.45, w: 4.4, h: 0.4, fontFace: FONT_H, fontSize: 13, bold: true, color: C.deep });
  s.addText(
    'Demanda · Marketing · Superestructura · Seguridad · Imagen percibida · Apertura comunitaria',
    { x: 6.95, y: 4.0, w: 5.7, h: 1.0, fontFace: FONT, fontSize: 12, color: C.text }
  );

  // Interpretation bands
  s.addText('Interpretación del resultado (escala 0–3)', { x: 0.5, y: 5.3, w: 12, h: 0.4, fontFace: FONT_H, fontSize: 14, bold: true, color: C.deep });

  const ranges = [
    ['BAJA VOCACIÓN', '< 1.1', C.red],
    ['MEDIANA VOCACIÓN', '1.1 – 2.0', C.yellow],
    ['ALTA VOCACIÓN', '≥ 2.1', C.green],
  ];
  const rx = 0.5, ry = 5.75, rw = 4.05, rh = 0.85, rgap = 0.1;
  ranges.forEach(([t, v, col], i) => {
    const x = rx + i * (rw + rgap);
    s.addShape('roundRect', { x, y: ry, w: rw, h: rh, fill: { color: col }, line: { color: col }, rectRadius: 0.08 });
    s.addText(t, { x: x + 0.2, y: ry + 0.1, w: rw - 1.5, h: 0.32, fontFace: FONT_H, fontSize: 12, bold: true, color: C.white, charSpacing: 3 });
    s.addText(v, { x: x + 0.2, y: ry + 0.42, w: rw - 1.5, h: 0.4, fontFace: FONT_H, fontSize: 18, bold: true, color: C.white });
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 9 — Potencialidad: cuadrantes
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '08 · MÓDULO 3', 'Potencialidad Turística · Diagrama de Cuadrantes');

  // Left side: quadrant diagram
  const cx = 0.7, cy = 2.0, cs = 4.3;
  // Outer frame
  s.addShape('rect', { x: cx, y: cy, w: cs, h: cs, fill: { color: C.white }, line: { color: C.deep, width: 1.5 } });
  // Quadrant fills (4 colors)
  const qSize = cs / 2;
  // TL: FN<1, FX≥1 — Potencial Exógeno
  s.addShape('rect', { x: cx, y: cy, w: qSize, h: qSize, fill: { color: 'FEF3C7' }, line: { color: C.ice } });
  // TR: FN≥1, FX≥1 — Alto Potencial
  s.addShape('rect', { x: cx + qSize, y: cy, w: qSize, h: qSize, fill: { color: 'DCFCE7' }, line: { color: C.ice } });
  // BL: FN<1, FX<1 — Bajo
  s.addShape('rect', { x: cx, y: cy + qSize, w: qSize, h: qSize, fill: { color: 'FEE2E2' }, line: { color: C.ice } });
  // BR: FN≥1, FX<1 — Endógeno
  s.addShape('rect', { x: cx + qSize, y: cy + qSize, w: qSize, h: qSize, fill: { color: 'DBEAFE' }, line: { color: C.ice } });
  // Center axes lines
  s.addShape('line', { x: cx + qSize, y: cy, w: 0, h: cs, line: { color: C.deep, width: 1.5 } });
  s.addShape('line', { x: cx, y: cy + qSize, w: cs, h: 0, line: { color: C.deep, width: 1.5 } });
  // Quadrant labels (concise)
  s.addText('Potencial\nExógeno', { x: cx + 0.1, y: cy + 0.2, w: qSize - 0.2, h: 0.7, fontFace: FONT_H, fontSize: 11, bold: true, color: C.muted, align: 'center' });
  s.addText('Alto\nPotencial', { x: cx + qSize + 0.1, y: cy + 0.2, w: qSize - 0.2, h: 0.7, fontFace: FONT_H, fontSize: 11, bold: true, color: C.green, align: 'center' });
  s.addText('Bajo\nPotencial', { x: cx + 0.1, y: cy + qSize + 0.2, w: qSize - 0.2, h: 0.7, fontFace: FONT_H, fontSize: 11, bold: true, color: C.red, align: 'center' });
  s.addText('Potencial\nEndógeno', { x: cx + qSize + 0.1, y: cy + qSize + 0.2, w: qSize - 0.2, h: 0.7, fontFace: FONT_H, fontSize: 11, bold: true, color: C.muted, align: 'center' });
  // Axes labels
  s.addText('FN →', { x: cx + cs - 0.6, y: cy + cs - 0.4, w: 0.6, h: 0.3, fontFace: FONT, fontSize: 10, bold: true, color: C.deep });
  s.addText('↑ FX', { x: cx + 0.05, y: cy + 0.05, w: 0.5, h: 0.3, fontFace: FONT, fontSize: 10, bold: true, color: C.deep });

  // Right side: weights breakdown
  s.addText('Ponderaciones', { x: 5.6, y: 1.95, w: 7, h: 0.4, fontFace: FONT_H, fontSize: 16, bold: true, color: C.deep });

  // FN box
  s.addShape('roundRect', { x: 5.6, y: 2.4, w: 7.25, h: 2.1, fill: { color: C.white }, line: { color: C.coral, width: 2 }, rectRadius: 0.1 });
  s.addText('FN · Factores Endógenos', { x: 5.8, y: 2.5, w: 6.85, h: 0.4, fontFace: FONT_H, fontSize: 14, bold: true, color: C.coral });
  const fnW = [
    ['Recursos Turísticos', '40 %'],
    ['Planta Turística', '20 %'],
    ['Tipologías de Turismo', '20 %'],
    ['Infraestructura', '20 %'],
  ];
  fnW.forEach(([k, v], i) => {
    const y = 2.95 + i * 0.35;
    s.addText(k, { x: 5.85, y, w: 5.5, h: 0.3, fontFace: FONT, fontSize: 12, color: C.text });
    s.addText(v, { x: 11.6, y, w: 1.0, h: 0.3, fontFace: FONT_H, fontSize: 12, bold: true, color: C.coral, align: 'right' });
  });

  // FX box
  s.addShape('roundRect', { x: 5.6, y: 4.6, w: 7.25, h: 1.85, fill: { color: C.white }, line: { color: C.teal, width: 2 }, rectRadius: 0.1 });
  s.addText('FX · Factores Exógenos', { x: 5.8, y: 4.7, w: 6.85, h: 0.4, fontFace: FONT_H, fontSize: 14, bold: true, color: C.teal });
  const fxW = [
    ['Afluencia Turística', '40 %'],
    ['Marketing Turístico', '30 %'],
    ['Superestructura', '30 %'],
  ];
  fxW.forEach(([k, v], i) => {
    const y = 5.15 + i * 0.35;
    s.addText(k, { x: 5.85, y, w: 5.5, h: 0.3, fontFace: FONT, fontSize: 12, color: C.text });
    s.addText(v, { x: 11.6, y, w: 1.0, h: 0.3, fontFace: FONT_H, fontSize: 12, bold: true, color: C.teal, align: 'right' });
  });

  // Innovation pill
  s.addShape('roundRect', { x: 0.7, y: 6.55, w: 12.15, h: 0.6, fill: { color: C.amber }, line: { color: C.amber }, rectRadius: 0.1 });
  s.addText('💡 Innovación: configuración dinámica de campos por zona — el sistema reasigna los pesos cuando un grupo no aplica al territorio', {
    x: 0.85, y: 6.6, w: 11.85, h: 0.5, fontFace: FONT, fontSize: 12, bold: true, color: C.deep, valign: 'middle',
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 10 — Percepción Local
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '09 · MÓDULO 4', 'Matriz de Percepción de la Localidad');

  s.addText(
    'A diferencia de los anteriores, este instrumento evalúa el territorio desde la dimensión humana: el grado de receptividad e involucramiento de la comunidad anfitriona frente al turismo.',
    { x: 0.5, y: 1.95, w: 12.3, h: 0.7, fontFace: FONT, fontSize: 13.5, color: C.muted, italic: true }
  );

  // 4 categories with their weights
  const cats = [
    ['DS', 'Dimensión Social', '20 %', '3 atributos', C.coral, 'Posición · Interés · Contribución'],
    ['PL', 'Percepción Local', '40 %', '6 atributos', C.amber, 'Conocimiento · Sentimiento · Necesidad'],
    ['PE', 'Percepción Económica', '20 %', '3 atributos', C.teal, 'Ingresos · Beneficios · Inversión'],
    ['NO', 'Nivel de Organización', '20 %', '4 atributos', C.deep, 'Cohesión · Liderazgo · Conflictos'],
  ];
  const px = 0.5, py = 2.85, pw = 3.05, ph = 2.7, pgap = 0.16;
  cats.forEach(([code, name, weight, count, color, items], i) => {
    const x = px + i * (pw + pgap);
    // Card
    s.addShape('roundRect', { x, y: py, w: pw, h: ph, fill: { color: C.white }, line: { color: C.ice, width: 1 }, rectRadius: 0.08 });
    // Top color bar with code
    s.addShape('rect', { x, y: py, w: pw, h: 0.6, fill: { color }, line: { color } });
    s.addText(code, { x, y: py + 0.07, w: pw, h: 0.5, fontFace: FONT_H, fontSize: 22, bold: true, color: C.white, align: 'center' });

    s.addText(name, { x: x + 0.15, y: py + 0.75, w: pw - 0.3, h: 0.4, fontFace: FONT_H, fontSize: 13, bold: true, color: C.text, align: 'center' });
    s.addText(weight, { x: x + 0.15, y: py + 1.15, w: pw - 0.3, h: 0.45, fontFace: FONT_H, fontSize: 26, bold: true, color: color, align: 'center' });
    s.addText(count, { x: x + 0.15, y: py + 1.65, w: pw - 0.3, h: 0.3, fontFace: FONT, fontSize: 11, color: C.muted, align: 'center', italic: true });
    s.addText(items, { x: x + 0.15, y: py + 2.05, w: pw - 0.3, h: 0.55, fontFace: FONT, fontSize: 10.5, color: C.text, align: 'center' });
  });

  // Scale + formula
  s.addShape('roundRect', { x: 0.5, y: 5.7, w: 6.1, h: 1.45, fill: { color: C.navy }, line: { color: C.navy }, rectRadius: 0.1 });
  s.addText('ESCALA VALORATIVA', { x: 0.7, y: 5.8, w: 6, h: 0.3, fontFace: FONT, fontSize: 10.5, bold: true, color: C.amber, charSpacing: 3 });
  // Three scale chips
  const scales = [['1', 'Negativo', C.red], ['2', 'Neutral', C.yellow], ['3', 'Positivo', C.green]];
  scales.forEach(([n, label, col], i) => {
    const x = 0.7 + i * 1.95;
    s.addShape('roundRect', { x, y: 6.2, w: 1.85, h: 0.85, fill: { color: col }, line: { color: col }, rectRadius: 0.08 });
    s.addText(n, { x, y: 6.25, w: 1.85, h: 0.4, fontFace: FONT_H, fontSize: 22, bold: true, color: C.white, align: 'center' });
    s.addText(label, { x, y: 6.6, w: 1.85, h: 0.35, fontFace: FONT_H, fontSize: 12, bold: true, color: C.white, align: 'center' });
  });

  // Formula
  s.addShape('roundRect', { x: 6.75, y: 5.7, w: 6.1, h: 1.45, fill: { color: C.white }, line: { color: C.deep, width: 1.5 }, rectRadius: 0.1 });
  s.addText('ÍNDICE GLOBAL', { x: 6.95, y: 5.8, w: 6, h: 0.3, fontFace: FONT, fontSize: 10.5, bold: true, color: C.deep, charSpacing: 3 });
  s.addText('Σ ((avg × peso) ÷ 3)', { x: 6.75, y: 6.15, w: 6.1, h: 0.5, fontFace: 'Consolas', fontSize: 22, bold: true, color: C.text, align: 'center' });
  s.addText('Normalizado a 0–1 · expresado como % de cumplimiento', { x: 6.75, y: 6.7, w: 6.1, h: 0.3, fontFace: FONT, fontSize: 10.5, italic: true, color: C.muted, align: 'center' });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 11 — Sistema de roles
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '10 · CONTROL DE ACCESO', 'Tres roles, tres responsabilidades');

  s.addText(
    'El sistema refleja la cadena real de responsabilidades del trabajo de campo. Cada acción es auditada por usuario, estado y fecha.',
    { x: 0.5, y: 1.95, w: 12.3, h: 0.65, fontFace: FONT, fontSize: 13, italic: true, color: C.muted }
  );

  const roles = [
    {
      icon: '👑', label: 'ADMINISTRADOR', id: 'role_id = 1', color: C.coral,
      perms: ['Crea y gestiona usuarios', 'Define lugares y zonas', 'Consulta todas las evaluaciones', 'Visión global del sistema'],
    },
    {
      icon: '🧭', label: 'JEFE DE ZONA', id: 'role_id = 2', color: C.amber,
      perms: ['Lidera la(s) zona(s) asignadas', 'Configura los campos aplicables', 'Edita evaluaciones en borrador', 'Confirma (valida) las matrices'],
    },
    {
      icon: '🤝', label: 'EQUIPO OPERATIVO', id: 'role_id = 3', color: C.teal,
      perms: ['Trabajo de campo', 'Captura datos en borrador', 'Aporta al inventario', 'No puede confirmar evaluaciones'],
    },
  ];

  const rx = 0.5, ry = 2.75, rw = 4.1, rh = 4.0, rgap = 0.16;
  roles.forEach((r, i) => {
    const x = rx + i * (rw + rgap);
    s.addShape('roundRect', { x, y: ry, w: rw, h: rh, fill: { color: C.white }, line: { color: C.ice, width: 1 }, rectRadius: 0.12 });
    // Top accent banner
    s.addShape('rect', { x, y: ry, w: rw, h: 0.9, fill: { color: r.color }, line: { color: r.color } });
    s.addText(r.icon, { x, y: ry + 0.1, w: rw, h: 0.7, fontFace: FONT, fontSize: 36, align: 'center' });
    s.addText(r.label, { x, y: ry + 1.05, w: rw, h: 0.4, fontFace: FONT_H, fontSize: 16, bold: true, color: C.text, align: 'center', charSpacing: 3 });
    s.addText(r.id, { x, y: ry + 1.5, w: rw, h: 0.35, fontFace: 'Consolas', fontSize: 12, color: r.color, align: 'center' });
    // Permission lines
    r.perms.forEach((perm, j) => {
      const y = ry + 1.95 + j * 0.45;
      s.addShape('ellipse', { x: x + 0.35, y: y + 0.12, w: 0.15, h: 0.15, fill: { color: r.color }, line: { color: r.color } });
      s.addText(perm, { x: x + 0.6, y, w: rw - 0.8, h: 0.4, fontFace: FONT, fontSize: 12, color: C.text });
    });
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 12 — Despliegue + DevOps
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '11 · DESPLIEGUE', 'De código a producción en un push');

  // Timeline-style flow
  const steps = [
    ['1', 'git push', 'Commit a la rama main de GitHub', C.coral],
    ['2', 'Render detecta', 'Auto-deploy desde el blueprint render.yaml', C.amber],
    ['3', 'Docker build', 'Node 20 → assets · PHP 8.2 → runtime', C.teal],
    ['4', 'Entrypoint', 'Cache de config, migraciones, seed inicial', C.deep],
    ['5', 'En producción', 'Apache servida bajo HTTPS de Render', C.coral],
  ];

  const stepW = 2.4, stepH = 1.5, sx2 = 0.5, sy2 = 2.0, sGap = 0.07;
  steps.forEach(([n, t, d, c], i) => {
    const x = sx2 + i * (stepW + sGap);
    s.addShape('roundRect', { x, y: sy2, w: stepW, h: stepH, fill: { color: c }, line: { color: c }, rectRadius: 0.1 });
    s.addText(n, { x, y: sy2 + 0.05, w: stepW, h: 0.5, fontFace: FONT_H, fontSize: 28, bold: true, color: C.white, align: 'center' });
    s.addText(t, { x: x + 0.1, y: sy2 + 0.55, w: stepW - 0.2, h: 0.35, fontFace: FONT_H, fontSize: 13, bold: true, color: C.white, align: 'center' });
    s.addText(d, { x: x + 0.15, y: sy2 + 0.95, w: stepW - 0.3, h: 0.5, fontFace: FONT, fontSize: 10, color: C.white, align: 'center' });

    // Arrow between steps
    if (i < steps.length - 1) {
      s.addText('▶', {
        x: x + stepW - 0.05, y: sy2 + 0.55, w: 0.2, h: 0.4,
        fontFace: FONT, fontSize: 14, bold: true, color: C.muted, align: 'center',
      });
    }
  });

  // Key challenges block
  s.addText('Desafíos técnicos resueltos', { x: 0.5, y: 3.85, w: 12, h: 0.4, fontFace: FONT_H, fontSize: 16, bold: true, color: C.deep });

  const challenges = [
    ['🔑', 'APP_KEY base64', 'Generación correcta de clave AES-256 para sesiones cifradas y compatibilidad con el cipher Laravel.'],
    ['🌐', 'TrustProxies', 'Detección de HTTPS detrás del proxy de Render mediante header X-Forwarded-Proto para evitar mixed-content.'],
    ['🎨', 'Vite manifest', 'Inclusión explícita de estilo.css en el manifest de Vite para que los assets se compilen en producción.'],
    ['🌱', 'Seed automático', 'Bootstrap remoto para sembrar datos iniciales sin necesidad de Shell (plan free de Render).'],
  ];
  const chx = 0.5, chy = 4.35, chw = 6.1, chh = 1.1, chgap = 0.12;
  challenges.forEach(([icon, t, d], i) => {
    const x = chx + (i % 2) * (chw + chgap);
    const y = chy + Math.floor(i / 2) * (chh + chgap);
    s.addShape('roundRect', { x, y, w: chw, h: chh, fill: { color: C.white }, line: { color: C.ice, width: 1 }, rectRadius: 0.08 });
    s.addText(icon, { x: x + 0.15, y: y + 0.2, w: 0.7, h: 0.75, fontFace: FONT, fontSize: 26, align: 'center' });
    s.addText(t, { x: x + 0.95, y: y + 0.12, w: chw - 1.0, h: 0.35, fontFace: FONT_H, fontSize: 13, bold: true, color: C.deep });
    s.addText(d, { x: x + 0.95, y: y + 0.46, w: chw - 1.0, h: 0.6, fontFace: FONT, fontSize: 10.5, color: C.muted });
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 13 — Lo destacado
// ============================================================
{
  slideN++;
  const s = bgSlide();
  slideTitle(s, '12 · LO QUE QUEREMOS DESTACAR', 'Decisiones técnicas con valor metodológico');

  const highlights = [
    {
      stat: '+50', label: 'campos configurables por zona',
      desc: 'El módulo de Potencialidad reasigna pesos cuando un grupo no aplica al territorio. Evita penalizaciones injustas al diagnóstico.',
      color: C.coral,
    },
    {
      stat: '16', label: 'atributos en la matriz de Percepción',
      desc: 'Cuatro categorías ponderadas que capturan la dimensión humana del turismo, traducidas fielmente al esquema relacional.',
      color: C.amber,
    },
    {
      stat: '4', label: 'instrumentos integrados',
      desc: 'Inventario, Vocación, Potencialidad y Percepción operan sobre un mismo flujo, con control unificado de estados y roles.',
      color: C.teal,
    },
    {
      stat: '3', label: 'niveles de rol con auditoría',
      desc: 'Cada evaluación queda firmada con usuario, timestamp y estado. La validación del Jefe bloquea la edición posterior.',
      color: C.deep,
    },
  ];

  const hx = 0.5, hy = 2.0, hw = 6.15, hh = 2.4, hgap = 0.2;
  highlights.forEach((h, i) => {
    const x = hx + (i % 2) * (hw + hgap);
    const y = hy + Math.floor(i / 2) * (hh + hgap);
    s.addShape('roundRect', { x, y, w: hw, h: hh, fill: { color: C.white }, line: { color: C.ice, width: 1 }, rectRadius: 0.12 });
    // Left side: stat
    s.addText(h.stat, { x: x + 0.15, y: y + 0.25, w: 2.0, h: 1.2, fontFace: FONT_H, fontSize: 64, bold: true, color: h.color, align: 'center' });
    s.addText(h.label, { x: x + 0.15, y: y + 1.5, w: 2.0, h: 0.7, fontFace: FONT, fontSize: 10.5, color: C.muted, align: 'center' });
    // Right side: description
    s.addShape('line', { x: x + 2.3, y: y + 0.3, w: 0, h: 1.8, line: { color: C.ice, width: 1 } });
    s.addText(h.desc, { x: x + 2.55, y: y + 0.3, w: hw - 2.75, h: 1.85, fontFace: FONT, fontSize: 13, color: C.text, valign: 'middle' });
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 14 — Conclusiones
// ============================================================
{
  slideN++;
  const s = pres.addSlide();
  s.background = { color: C.navy };

  s.addText('13 · CONCLUSIONES', {
    x: 0.6, y: 0.7, w: 12, h: 0.4, fontFace: FONT, fontSize: 12, color: C.amber, bold: true, charSpacing: 4,
  });
  s.addText('Lo que aprendimos al hacerlo', {
    x: 0.6, y: 1.1, w: 12, h: 1.0, fontFace: FONT_H, fontSize: 36, bold: true, color: C.white,
  });

  const conclusions = [
    ['📐', 'Rigor metodológico preservado', 'Los pesos, escalas y fórmulas del modelo original se trasladaron sin distorsión. La validez técnica del instrumento queda intacta en el entorno digital.'],
    ['🔄', 'Adaptación dinámica', 'La configuración de campos por zona resuelve un problema metodológico real: no penalizar al territorio por carencias inaplicables.'],
    ['👥', 'Roles que reflejan el trabajo real', 'La cadena Equipo → Jefe → Administrador modela cómo se evalúa un destino en la práctica académica y profesional.'],
    ['📊', 'De datos a decisiones', 'Las visualizaciones (radar, cuadrantes) transforman valores numéricos en diagnósticos accionables más allá del aula.'],
  ];

  const sx = 0.6, sy = 2.5, sw = 12.1, sh = 1.0, sgap = 0.1;
  conclusions.forEach(([icon, t, d], i) => {
    const y = sy + i * (sh + sgap);
    s.addShape('rect', { x: sx, y: y + 0.05, w: 0.07, h: sh - 0.1, fill: { color: C.amber }, line: { color: C.amber } });
    s.addText(icon, { x: sx + 0.2, y, w: 0.7, h: sh, fontFace: FONT, fontSize: 26, valign: 'middle' });
    s.addText(t, { x: sx + 1.0, y: y + 0.1, w: sw - 1.0, h: 0.35, fontFace: FONT_H, fontSize: 16, bold: true, color: C.white });
    s.addText(d, { x: sx + 1.0, y: y + 0.45, w: sw - 1.0, h: 0.55, fontFace: FONT, fontSize: 12, color: C.ice });
  });

  footer(s, slideN, TOTAL);
}

// ============================================================
// SLIDE 15 — Gracias
// ============================================================
{
  slideN++;
  const s = pres.addSlide();
  s.background = { color: C.navy };

  // Decorative elements
  s.addShape('ellipse', { x: -1.5, y: -1.5, w: 4, h: 4, fill: { color: C.amber }, line: { color: C.amber } });
  s.addShape('ellipse', { x: 11.5, y: 5.5, w: 3.5, h: 3.5, fill: { color: C.teal }, line: { color: C.teal } });
  s.addShape('ellipse', { x: 10.5, y: 4.5, w: 2, h: 2, fill: { color: C.coral }, line: { color: C.coral } });

  s.addText('Gracias', {
    x: 0.5, y: 2.5, w: 12.3, h: 1.6, fontFace: FONT_H, fontSize: 120, bold: true, color: C.white, align: 'center',
  });
  s.addText('¿Preguntas?', {
    x: 0.5, y: 4.2, w: 12.3, h: 0.8, fontFace: FONT_H, fontSize: 32, italic: true, color: C.ice, align: 'center',
  });

  s.addShape('rect', { x: 5.5, y: 5.6, w: 2.3, h: 0.05, fill: { color: C.amber }, line: { color: C.amber } });

  s.addText('Juan Pedro Pesántez López', {
    x: 0.5, y: 5.85, w: 12.3, h: 0.4, fontFace: FONT_H, fontSize: 18, bold: true, color: C.white, align: 'center',
  });
  s.addText('UDAExplore · 2026', {
    x: 0.5, y: 6.3, w: 12.3, h: 0.4, fontFace: FONT, fontSize: 14, color: C.ice, align: 'center', italic: true,
  });
}

// ============================================================
// SAVE
// ============================================================
pres.writeFile({ fileName: __dirname + '/Presentacion_UDAExplore.pptx' }).then((fn) => {
  console.log('✓ PPTX creado:', fn);
});
