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

## Estado al 13 de agosto — la sesión se cortó en mitad de la rama

**Seis tareas de siete, hechas y comiteadas. La rama NO está fusionada.**

| Tarea | Commit | Suite | Revisión |
|---|---|---|---|
| T1 el desglose en el servicio | `7b3c247` | 611 | ✅ limpia |
| T2 la ordenación en el servidor | `7ffe6a6` | 619 | ✅ limpia |
| T3 la franja de cifras | `272453c` | 622 | ✅ limpia |
| T4 el desglose en las maquetaciones | `c0910ed` | 626 | ✅ limpia |
| T5 la tabla ordenable | `3fb0732` | 631 | ✅ limpia |
| T6 cero zonas | `1bcffd1` | **632** | **pendiente** — el paquete quedó armado y la sesión murió antes de que volviera el revisor |
| T7 revisión de rama y traspaso | — | — | **entera, sin empezar** |

El recuento cierra en **632 y no en 633**: la tabla del plan traía un error
aritmético —contaba nueve tests en T2 donde el brief lista ocho— que se detectó
al ejecutar T2 y arrastra un uno de menos desde ahí. No falta ningún test.

**Para retomar**, el detalle largo está en
`.superpowers/sdd/2026-08-13-dashboard-mis-zonas/progress.md`: los rulings de
cada tarea, los seis menores aplazados que la revisión de rama debe ver, y los
cinco pasos de la T7. Los `task-N-report.md` de esa carpeta son el porqué de
cada commit.

Lo que queda, en corto: **mirar la página de verdad** (en las cuatro últimas
ramas ese paso encontró lo que ningún test veía), **revisar la rama entera**
contra `0cd8ecf`, correr la suite, escribir el traspaso y preguntar por la
fusión.
