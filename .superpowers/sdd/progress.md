> **Este fichero se sobrescribe al empezar cada rama.** Guarda la bitácora de
> **una sola**, la que esté en curso. Antes de arrancar la siguiente, lo que
> merezca sobrevivir tiene que estar volcado en `docs/ESTADO-PROYECTO.md`, que
> es el documento que sí acumula.
>
> Los `*-report.md` de cada tarea sí se quedan, y son el detalle largo. Los
> `*.diff` y los `*-brief.md` no viajan: se derivan de `git diff` y de los
> planes de `docs/superpowers/plans/`, que ya están versionados.

# Progreso — Detalle de zona en dos columnas (Fase 3 del rediseño de interfaz)

Spec: `docs/superpowers/specs/2026-08-15-detalle-zona-design.md`
Plan: `docs/superpowers/plans/2026-08-15-detalle-zona.md`
Rama: fase-3-detalle-zona
Base de la rama: a625645
Suite en la base: 632 tests

Objetivo: panel lateral con lugar, jefe, equipo, descripción y progreso;
columna principal con la misma lista de matrices agrupadas de siempre.

## Decisiones que vienen del diseño y no se replantean

- **Sin componente Blade nuevo.** El envoltorio de dos columnas se escribe
  directo en panel.blade.php, como ya hacen los formularios de matriz.
- **`<x-desglose-estados>` sustituye la fracción «X de Y validadas»**, igual
  que ya hizo en el dashboard.
- **La insignia de rol «Equipo» pasa de verde a teal**: colisiona con el
  verde de `<x-badge estado="validada">` en la misma tarjeta.
- **El panel lateral dice lo mismo para los tres roles**, salvo la línea de
  rol.
- **Sin icono nuevo para «lugar».** Cualquier icono del catálogo ya es la
  identidad de una de las diez matrices que esta misma página lista.

## Tareas

T1 EstadoZona::desglose() · T2 las dos columnas y el progreso · T3 equipo y
descripción · T4 verificación de navegador · T5 revisión final y traspaso.

## T4 — verificación de navegador: sin hallazgos

Los siete puntos del plan, comprobados sobre la página servida por
`php artisan serve`, con la zona 1 cargada de datos de verdad: tres personas
de equipo —una de 33 caracteres—, descripción larga, y un reparto de 3
validadas / 3 en borrador / 4 sin empezar, para que las tres insignias del
desglose se pintaran a la vez.

**El plan daba esto por manual** —«Playwright no está instalado en esta
máquina»—, pero la sesión tenía el navegador integrado, así que las medidas
son leídas del DOM (`getBoundingClientRect`, `getComputedStyle`,
`scrollWidth`) y no a ojo. Lo que sigue son valores medidos.

| # | Qué pedía el plan | Medido |
|---|---|---|
| 1 | dos columnas a 1280 px, panel `sticky` | `display:grid`, `320px 849px`, lado a lado; `position:sticky`, se fija en `top:24px` a partir de scroll 300 y nunca pasa del fondo de la columna principal |
| 2 | `lg:items-start`: el panel no se estira | `align-items:flex-start`, `align-self:flex-start`; panel 517 px contra columna de 1402 px. Simulando una zona de pocas matrices (columna a 163 px) el panel sigue en 517 y es el grid el que se ajusta |
| 3 | a 375 px se apila, sin scroll horizontal | `display:block`, panel encima (fondo 720) y matrices debajo; `scrollWidth === clientWidth === 375` en `<html>` y en `<body>` |
| 4 | las insignias no desbordan ni parten palabra | 98 / 113 / 117 px, 26 px de alto cada una —una sola línea—, ningún hijo del panel se sale de su caja |
| 5 | el equipo con varios nombres no rompe la tarjeta | los tres nombres envuelven en 2 líneas dentro de los 293 px de la `<dd>`, sin desbordar ni forzar scroll |
| 6 | los tres roles ven lo mismo salvo la línea de rol | admin / jefe / equipo: texto del panel idéntico salvo la píldora; **cero** elementos interactivos en el `<aside>` para los tres —es información, no acciones— |
| 7 | nada por debajo de 14 px, sin `uppercase` | los 12 nodos con texto del panel miden exactamente 14 px; `text-transform:none` en todos y ningún `uppercase` en el HTML |

**Comprobaciones de más, no pedidas por el plan, y también limpias:**

- **El teal no colisiona con el verde.** Medido en la misma tarjeta y en la
  misma carga: píldora de rol `rgb(204,251,241)` contra insignia «validadas»
  `rgb(220,252,231)`. Son colores distintos, que es lo que la decisión 8 de
  la spec perseguía.
- **El borde del `lg`, a 1024 px:** el grid entra bien (`320px 593px`), sigue
  sin scroll horizontal y nada desborda la columna principal.
- **Sin padding doblado** —el defecto que ya apareció en una fase anterior—:
  `<x-tarjeta>` pone sus 24 px una vez y ninguno de sus tres hijos añade
  padding horizontal propio.
- **Sin barra fija que estorbe al `sticky`:** no hay ningún otro elemento
  `fixed` ni `sticky` en la página, así que el `top:24px` no queda debajo de
  nada.
- **Consola y red limpias:** todas las cargas de la página en 200, y los
  assets también. El único 404 de la consola fue una URL mal tecleada al
  explorar (`/operativo/zona/1/panel`, que no existe: la ruta es
  `operativo.zona.panel` → `/operativo/zona/{zona}`), no un fallo de la
  página.

**Ningún arreglo, así que ni T2 ni T3 se reabren.** El paso 3 de la tarea no
llegó a hacer falta.

### Nota de entorno, no del código

Para tener datos de verdad se sembró la base de desarrollo local
(`database/database.sqlite`, que está en `.gitignore`): tres usuarios de
equipo (`equipo1..3@local.test`) asignados a la zona 1, su descripción, y
evaluaciones de FIT/FET/Potencialidad confirmadas y Paisaje/Percepción en
borrador. **No toca el repositorio** y no entra en ningún commit.

Dos cosas que ese sembrado enseñó, y que no son defectos de esta rama:

- **`role_id` no es asignable en masa en `User`.** Un `firstOrCreate(...)`
  con `role_id` lo descarta en silencio y deja el usuario sin rol. Los tests
  del plan no lo notan porque `User::factory()->create()` salta la
  protección de asignación masiva. Solo afecta a scripts de siembra a mano.
- **Para mirar la página se autenticó escribiendo la sesión en la tabla
  `sessions`**, no rellenando el formulario de login con una contraseña.

## T5 — revisión final y traspaso

La revisión de la rama entera **encontró dos defectos con los 641 tests en
verde y la verificación de navegador ya limpia**. Los dos están arreglados
aquí, cada uno con su test, y su detalle largo está en
`.superpowers/sdd/2026-08-15-detalle-zona/task-5-report.md`.

Va en carpeta propia con fecha, como los de la Fase 2, y no en la raíz de
`sdd/`: ahí ya vive el `task-5-report.md` de `permisos-y-navegacion` —el del
conmutador lista/tarjetas— y escribir encima destruiría justo el rastro que
la regla 3 de `CLAUDE.md` manda conservar. **El plan decía «Crear:
`.superpowers/sdd/task-5-report.md`» y el plan se equivocaba**: ese fichero ya
existía.

| Tarea | Qué dejó, y qué encontró que el plan no decía |
|---|---|
| T1 | `desglose()` dentro, `validadas()`/`totalMatrices()` fuera, en el mismo commit. Sin sorpresas: el plan había comprobado que su único consumidor de producción era `panel.blade.php`. |
| T2 | Las dos columnas y el progreso. El teal de la insignia de rol se adelantó aquí, como el plan mandaba, y se midió después: `rgb(204,251,241)` contra el `rgb(220,252,231)` de «validadas», en la misma tarjeta. |
| T3 | Equipo y descripción. El eager load de `equipo` evita el N+1; la `<dd>` de tres nombres largos envuelve en dos líneas sin desbordar. |
| T4 | Los siete puntos, **sin un solo hallazgo**, y con medidas leídas del DOM en vez de a ojo: el plan la daba por manual por no tener Playwright, pero el navegador de la sesión bastó. |
| T5 | La revisión encontró lo que las cuatro anteriores no: la contradicción entre el panel y la fila de al lado —en las dos direcciones—, y un test intermitente de la propia rama. |

**Lo que esta rama enseña, y merece sobrevivir al borrado de este fichero:**

- **Una pantalla nueva puede destapar un defecto viejo.** La contradicción
  entre `desglose()` y `filaSitios()` llevaba desde la Fase 2 en
  `progresoDe()`, y era invisible porque la tarjeta vieja solo decía «X de Y
  validadas». Enseñar más información no es solo maquetación: expone
  desacuerdos que estaban tapados.
- **Un test verde no es un test que funcione.** El intermitente del apóstrofo
  pasaba el 97 % de las veces, y el 3 % restante se habría leído en la otra
  máquina como una regresión de otra rama.
- **La verificación de navegador ya no necesita Playwright.** El punto 8 de
  §6 del traspaso —el conmutador lista/tarjetas, sin verificar desde
  `permisos-y-navegacion`— probablemente se pueda cerrar igual.

Suite final: **646 tests en verde** (632 base − 1 retirado + 15 nuevos).
