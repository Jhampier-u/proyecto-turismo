> **Este fichero se sobrescribe al empezar cada rama.** Guarda la bitácora de
> **una sola**, la que esté en curso. Antes de arrancar la siguiente, lo que
> merezca sobrevivir tiene que estar volcado en `docs/ESTADO-PROYECTO.md`, que
> es el documento que sí acumula.
>
> Los `*-report.md` de cada tarea sí se quedan, y son el detalle largo. Los
> `*.diff` y los `*-brief.md` no viajan: se derivan de `git diff` y de los
> planes de `docs/superpowers/plans/`, que ya están versionados.

# Progreso — La franja de estado de los formularios (Fase 4 del rediseño)

Spec: `docs/superpowers/specs/2026-08-15-formularios-design.md`
Plan: `docs/superpowers/plans/2026-08-15-formularios.md`
Rama: fase-4-formularios
Base de la rama: 2938e23
Suite en la base: 646 tests

Objetivo: una sola franja donde hoy hay tres cajas, en los ocho formularios
de matriz.

## Decisiones que vienen del diseño y no se replantean

- **La franja deriva su estado**, no lo recibe por prop: siete vistas lo
  llaman $estaConfirmado/$bloqueado y Potencialidad $isConfirmado/$soloLectura.
- **`:niveles` es null por defecto y null significa «sin escala»**, a
  diferencia de <x-leyenda-escala>, cuyo defecto 0/1/2 hacía que «sin escala»
  y «escala corriente» se escribieran igual.
- **Tres estados, no dos**: el verde se reserva para «validada y puedes
  editarla»; cerrada se pinta neutro. CLAUDE.md recuerda que una fase anterior
  pintó de verde un estado bloqueado.
- **«Validada» a secas**, sin el nombre de la matriz: ya sale tres veces antes.
- **La franja describe; la advertencia de reapertura acompaña al botón.**

## Tareas

T1 el componente · T2 FIT y FET · T3 Paisaje y VT · T4 Percepción,
Concentración e Irritación · T5 Potencialidad · T6 retirar los dos
componentes y el aviso de la barra lateral · T7 verificación de navegador ·
T8 revisión final y traspaso.
