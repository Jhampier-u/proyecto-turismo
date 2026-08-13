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

T1b: **hueco de mi propio plan, encontrado al ir a usar el componente.** `vtt`
es de tipo `resultado` —se calcula a partir de FIT y FET— y su entrada del
Registro trae `ver` pero **no** `editar`. El componente pedía `rutas['editar']`
a secas, así que reventaba con «Undefined array key» ante una situación
legítima. Suite de migas 7 -> 8.
  - El arreglo obligó a separar dos cosas que eran la misma: **«es la hoja» y
    «no tiene destino»**. Confundirlas ponía `aria-current` en un tramo
    intermedio, es decir, anunciaba como página actual una que no lo es. Ahora
    la hoja se decide por posición.
  - El tramo se sigue pintando —el nombre hace falta para saber dónde estás—
    pero sin enlace. Inventarle un destino habría sido peor.

T2: completa. Migas en el panel de zona, en las nueve matrices y en el
resultado de VTT. Suite 596 -> 598.
  - **Las 20 migas se insertaron tomando la clave de la línea de
    `<x-pestanas-matriz>` que tenían al lado**, no escribiéndolas a mano. Las
    dos responden a «qué matriz es esta» y así no pueden discrepar.
  - `vtt/resultado` va **sin `actual`**: la Vocación del territorio *es* esa
    página, no un apartado suyo.
  - **Un test anterior se puso rojo, y la restricción funcionó.**
    `PaginaZonaTest::test_la_pagina_de_zona_no_repite_el_nombre_de_la_zona_en_cada_fila`
    afirmaba `assertSame(1, ...)`. Su propio docstring dice que vigila que
    `<x-fila-matriz>` no pinte el nombre **una vez por fila**; el 1 era un
    atajo. La miga añade una segunda aparición legítima. Consultado y decidido:
    se compara contra el **número de matrices**, que es lo que el docstring
    dice de verdad —si el defecto volviera serían catorce, no dos—. Es el mismo
    caso que el `max-w-7xl` de FV2: un test que afirma sobre la maquetación
    como sustituto del comportamiento.

T3: completa. Migas en inventarios, involucrados y frecuentación, más el
guardián. Suite 598 -> 599, que cubre 29 páginas.
  - **El guardián se escribió antes de tocar las vistas y se usó para
    encontrarlas**, en vez de adivinar cuáles faltaban. Nombró siete.
  - **Una de las siete no debe llevarlas:** `operativo/dashboard` *es* «Mis
    Zonas», la raíz del rastro, así que su miga sería un solo tramo
    apuntándose a sí misma. Excluida con el motivo escrito, igual que las
    páginas de `admin/`.

T4: completa, y **la parte que no estaba en el plan fue la mitad del trabajo.**
22 llamadas fuera en 20 ficheros, el componente y `BotonVolverTest` borrados.
Suite 599 -> 597 (se van tres, entra uno).
  - **La cobertura se movió, no se perdió.** `BotonVolverTest` afirmaba tres
    cosas; dos ya las cubría `MigasTest` y la tercera —que los **tres** roles
    suben al panel de la misma zona— no. Se escribió **antes** de borrar nada.
    No se dio por hecha porque el equipo caiga en la misma rama que el jefe: el
    fallo que motivó aquel test era justamente de rol.
  - **Dos «← Volver a la zona» más**, en `involucrados/index` y
    `frecuentacion/index`, escritos como `<x-boton>` y no como
    `<x-boton-volver>`. Ningún barrido del componente los encontraba y
    duplicaban la miga igual. **Es la tercera vez en este repositorio que un
    barrido falla por cómo se buscó y no por lo que había.**
  - Comentarios que narraban dónde vivía un botón que ya no existe —en cinco
    vistas— y **dos contenedores que se quedaron vacíos** al irse su único
    hijo. Quitar la llamada no era terminar.
