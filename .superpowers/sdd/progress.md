> **Este fichero se sobrescribe al empezar cada rama.** Guarda la bitácora de
> **una sola**, la que esté en curso. Antes de arrancar la siguiente, lo que
> merezca sobrevivir tiene que estar volcado en `docs/ESTADO-PROYECTO.md`, que
> es el documento que sí acumula.
>
> Los `*-report.md` de cada tarea sí se quedan, y son el detalle largo. Los
> `*.diff` y los `*-brief.md` no viajan: se derivan de `git diff` y de los
> planes de `docs/superpowers/plans/`, que ya están versionados.

# Progreso — Navbar y migas (Fase 1 del rediseño de interfaz)

Spec: `docs/superpowers/specs/2026-08-13-navbar-y-migas-design.md`
Plan: `docs/superpowers/plans/2026-08-13-navbar-y-migas.md`
Rama: navbar-y-migas
Base de la rama: 6dc2019
Suite en la base: 589 tests

Lo de `restos-fase-0` que estaba aquí antes ya está volcado en el traspaso (§3),
así que sobrescribir este fichero no pierde nada.

Objetivo: una sola forma de saber dónde se está y de subir de nivel —migas— y
una barra que sirva a los dos perfiles sin duplicar sus enlaces.

## Decisiones que vienen del diseño y no se replantean

- **Las migas sustituyen a `<x-boton-volver>`**, no conviven con él. 22 llamadas
  y el componente se borran.
- **El `Cmd+K` queda descartado**, no aplazado por tercera vez.
- **Cada vista declara su miga**, siguiendo el patrón que `<x-pestanas-matriz>`
  ya estableció en 18 vistas. No se deriva de la ruta.
- **Los grupos del `Registro` no entran en el rastro**: ninguno tiene ruta.
- **El selector de zona es solo del operativo.**
- **Orden innegociable:** las migas se ponen antes de quitar ningún botón.

## Tareas

T1 el componente · T2 panel y matrices · T3 inventarios, involucrados y
frecuentación · T4 fuera `<x-boton-volver>` · T5 los destinos del navbar en un
sitio · T6 selector de zona · T7 estética y fuera Breeze · T8 revisión y
traspaso.

T1: completa. `<x-migas>` con siete tests. Suite 589 -> 596, ningún test
existente tocado. Rojo verificado: los siete fallaban con «Unable to locate a
class or view for component [migas]».
  - **El riesgo que el plan avisaba no se materializó:** `$this->blade()` tras
    `actingAs()` resuelve `auth()->user()` sin problema, así que no hizo falta
    montar páginas servidas como hacía `BotonVolverTest`. Queda dicho porque la
    duda era razonable y ahora está resuelta con un hecho.
  - Dos guardias que revientan con mensaje útil: clave desconocida —listando
    las válidas, como hace `<x-boton>`— y clave sin zona, porque la ruta de una
    matriz necesita el id y sin él saldría una URL rota en vez de un error.
  - El test del último tramo lleva su contraparte: sin
    `test_con_hoja_la_zona_pasa_a_ser_enlace`, un componente que no pintara
    nunca el enlace de la zona pasaría los dos.
