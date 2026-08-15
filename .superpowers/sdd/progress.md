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
