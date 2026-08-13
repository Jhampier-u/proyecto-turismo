> **Este fichero se sobrescribe al empezar cada rama.** Guarda la bitácora de
> **una sola**, la que esté en curso. Antes de arrancar la siguiente, lo que
> merezca sobrevivir tiene que estar volcado en `docs/ESTADO-PROYECTO.md`, que
> es el documento que sí acumula.
>
> Los `*-report.md` de cada tarea sí se quedan, y son el detalle largo. Los
> `*.diff` y los `*-brief.md` no viajan: se derivan de `git diff` y de los
> planes de `docs/superpowers/plans/`, que ya están versionados.

# Progreso — Dashboard «Mis Zonas» (Fase 2 del rediseño de interfaz)

Spec: `docs/superpowers/specs/2026-08-13-dashboard-mis-zonas-design.md`
Plan: `docs/superpowers/plans/2026-08-13-dashboard-mis-zonas.md`
Rama: dashboard-mis-zonas
Base de la rama: 0cd8ecf
Suite en la base: 608 tests

Lo de la Fase 1 que estaba aquí antes ya está volcado en el traspaso, así que
sobrescribir este fichero no pierde nada.

Objetivo: cifras de conjunto, un orden que se pueda pedir por URL, y un estado
que distinga lo que nadie ha abierto de lo que espera validación.

## Decisiones que vienen del diseño y no se replantean

- **La ordenación va en el servidor**, por parámetro de URL. Playwright no está
  instalado en esta máquina: con Alpine sería invisible para la suite.
- **Desglose por estado, no una insignia de «zona terminada».** Los colores de
  `ESTILOS_ESTADO` significan el estado de una MATRIZ.
- **Ninguna pantalla de admin se toca**, y `hechas` no se renombra.
- **El orden por defecto pasa a ser nombre ascendente.**

## Tareas

T1 el desglose en el servicio · T2 la ordenación en el servidor · T3 la franja
de cifras · T4 el desglose en las dos maquetaciones · T5 la tabla ordenable ·
T6 cero zonas · T7 revisión y traspaso.
