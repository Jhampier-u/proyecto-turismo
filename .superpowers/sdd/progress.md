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

## T7 — verificación de navegador: sin hallazgos

Los siete puntos del plan, medidos sobre el DOM con `getBoundingClientRect()`,
`getComputedStyle()` y `scrollWidth`, no a ojo. **Sin Playwright**: basta el
navegador de la propia sesión, igual que en la Fase 3.

Datos: zona 1 con FIT en borrador —cuatro niveles, el caso ancho—, FET y
Potencialidad validadas, y tres personas de equipo para ver el tercer estado.

| # | Qué pedía | Medido |
|---|---|---|
| 1 | una sola caja a 1280 px, sin empujar la barra lateral | franja de **74 px** donde antes había tres cajas; borde izquierdo ámbar `rgb(245,158,11)` de 4 px; el `<aside>` arranca en y=285, **la misma altura** que la franja |
| 2 | a 375 px, el caso de cuatro niveles | 343×150 px; `scrollWidth === clientWidth === 375` en `<html>` y `<body>`; **cero** hijos se salen de la franja; las cuatro píldoras a 20 px, una línea cada una |
| 3 | que el verde y el neutro se distingan de verdad | borrador `rgb(245,158,11)` · validada `rgb(34,197,94)` · cerrada `rgb(148,163,184)`. Comprobado que el tercero **no** es el mismo verde que el segundo |
| 4 | «quién y cuándo» sin solaparse | dentro de la caja a 375 px, con la escala envuelta |
| 5 | Potencialidad, con su CSS propio | franja **encima** de la rejilla (y=286, rejilla en 454) y a todo el ancho: la desviación documentada. Sus avisos de configuración —«Modo Jefe», «activar o desactivar campos»— **siguen ahí**; el banner viejo con ✅/✏️ no |
| 6 | las dos sin escala | Concentración e Irritación: **46 px**, solo «Borrador», sin separador, sin frase de método y sin hueco donde iría la escala |
| 7 | 14 px y sin `uppercase` | todos los nodos con texto de la franja miden **exactamente 14 px** en las vistas comprobadas; ningún `uppercase` |

**De propina, y es lo que más valía comprobar:** en una FET validada mirada
por el jefe, la barra lateral **sí** pinta ahora «Esta matriz está validada.
Guardarla la devolverá a borrador y habrá que validarla de nuevo.». Es el
hueco que el diseño destapó —su botón «Guardar Borrador» reabría sin avisar—
cerrado y visto en el navegador, no solo en un test.

Y en la misma página vista por el equipo: la franja dice «Validada · solo
lectura» arriba, no hay botón de guardar, no hay «Actualizar Datos», y el
aviso que antes había que bajar a buscar al pie ya no existe.

Todas las cargas en 200. El único 404 de la consola fue una URL mal tecleada
al explorar (`/potencialidad` en vez de `/evaluacion-potencialidad`), no un
fallo de la página — el mismo tropiezo que en la Fase 3.

**Ningún arreglo: ni T2 ni T5 se reabren por esta tarea.**
