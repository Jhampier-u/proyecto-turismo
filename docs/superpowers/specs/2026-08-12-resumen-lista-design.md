# Franja de resumen para Involucrados y Frecuentación

**Fecha:** 2026-08-12
**Estado:** diseño aprobado, pendiente de plan de implementación

## De dónde viene

`docs/superpowers/specs/2026-08-12-dashboard-y-formularios-design.md` dejó a
Involucrados y Frecuentación fuera de la barra lateral de los formularios de
matriz, con la decisión escrita: son CRUD de filas, no tienen bloques que
indexar, y meterles el componente compartido crearía **uno que significa dos
cosas según quién lo use** —el error que este proyecto ya pagó con el tipo
`actores` y con `x-boton-volver`—.

Aquel documento anotó que «sí les vendría bien una barra lateral, con otro
contenido», y nombró tres cosas: un recuento de progreso, el botón de validar, y
en Frecuentación la Superficie Territorial, «que hoy es un dato de zona perdido
entre las filas».

## La premisa que cambió

**Esa tercera razón ya no aplica.** Se escribió como predicción, antes de
implementar Frecuentación. Hoy la ST tiene su propia sección en
`resources/views/operativo/frecuentacion/index.blade.php`, con título,
explicación, formulario propio y su botón de guardar, y un comentario que razona
por qué vive ahí.

**Moverla a una columna fija sería un retroceso:** es un campo editable con envío
propio, y eso no encaja en una barra lateral.

De las otras dos: el recuento de progreso **no existe** hoy en ninguna de las dos
vistas —cada fila dice «— sin responder —» pero nadie suma—, y el botón de
validar existe, al final de la página.

## Por qué una franja y no una barra lateral

La barra lateral de las matrices existe porque un formulario de 156 criterios es
kilométrico: el botón de guardar queda a un scroll enorme y no se sabe cuánto se
lleva. **Aquí las listas son de unas pocas filas**, así que ese problema es
mucho menor.

Una columna fija costaría bastante maquetación para ahorrar poco scroll, y
tentaría a mover la ST allí, que sería peor que dejarla donde está. Lo que de
verdad falta —«cuánto me queda»— es una línea, no una columna.

## Decisiones

| # | Decisión | Elegido |
|---|----------|---------|
| 1 | Forma | Franja de resumen sobre la tabla, no barra lateral |
| 2 | Botón de validar | **Se mueve** a la franja; desaparece del final |

Contra la decisión 2 juega que el gesto natural es «termino de rellenar, bajo,
valido». Si alguna de las dos listas creciera a decenas de filas, conviene
revisarla.

---

## El componente

`<x-resumen-lista>`, colocado sobre la tabla en las dos vistas.

Sigue la misma regla que `<x-barra-lateral-formulario>`: **pinta lo que le dan,
no lo deriva**. Las dos listas cuentan cosas distintas, y forzar una forma común
entre ellas es lo que produjo el tipo `actores` con `zona->involucrados()`
cableado dentro.

```blade
<x-resumen-lista sustantivo="sitio"
                 faltante="sin DET"
                 :total="$sitios->count()"
                 :incompletos="$incompletos"
                 :puede-validar="$puedeValidar"
                 :ruta-validar="route('operativo.frecuentacion.validar', $zona->id)">
    ST: 1.200            {{-- ranura: solo Frecuentación la usa --}}
</x-resumen-lista>
```

`sustantivo` y `faltante` son dos props con nombre y con un solo trabajo cada
uno: el primero da «5 **sitios**», el segundo «2 **sin DET**». `faltante` vale
por defecto `sin completar`, que es lo que usa Involucrados, donde a un actor le
pueden faltar varios campos y no uno concreto.

La parte que varía de verdad entre las dos —la ST— va en una **ranura**, no en un
prop `extra`. Un prop genérico «por si acaso» es justo lo que el docblock de la
barra lateral advierte que no se haga.

## Dónde va

**Lo primero de la página**, justo debajo del banner de lista validada y **por
encima de todo lo demás**: en Involucrados, sobre la tabla; en Frecuentación,
sobre la sección de la Superficie Territorial.

No debajo de esa sección, aunque la franja mencione la ST: si van pegadas, el
mismo dato aparece dos veces seguidas y la franja parece un duplicado en vez de
un resumen. Arriba del todo resume lo que viene después, que es su trabajo.

## Qué muestra cada una

| | Involucrados | Frecuentación |
|---|---|---|
| Recuento | «5 actores · 2 sin completar» | «5 sitios · 2 sin DET» |
| Ranura | — | «ST: 1.200» o «ST sin responder» |
| Botón | Validar y Cerrar la Lista | Validar y Cerrar la Lista |

En Frecuentación la ST aparece **como dato, no como campo**: se sigue editando en
su sección, que es donde tiene su formulario y su botón. La franja solo dice si
falta, porque sin ella ningún sitio tiene ÍETP aunque todos tengan su DET.

## De dónde salen los números

Del controlador, que ya sabe casi todo. Hoy calcula:

```php
'puedeValidar' => ! $confirmada && $user->esJefe() && $listaCompleta,
```

Ese `$listaCompleta` ya existe. Lo que falta es pasar **cuántos** faltan, no solo
si falta alguno.

Los modelos ya tienen `estaCompleto()` y el scope `incompletos()`, así que no hay
lógica nueva: solo un recuento donde antes había un booleano.

## El botón se mueve, no se duplica

Desaparece del final de las dos vistas. Un botón que hace lo mismo en dos sitios
es la duplicación que este proyecto lleva semanas quitando.

**El banner de lista validada se queda donde está.** Con la lista confirmada, la
franja muestra el recuento y ningún botón; que está cerrada ya lo dice ese
banner.

## Qué se prueba

- La franja da el recuento correcto en las dos vistas, con la lista **vacía**, **a
  medias** y **completa**.
- El botón de validar aparece **solo** si `puedeValidar`: solo al jefe, solo con
  la lista completa y sin confirmar.
- El equipo y el admin ven el recuento y **no** el botón.
- **No queda un segundo botón de validar al final.** Es el test que impide que la
  duplicación vuelva por descuido.
- En Frecuentación, la ST aparece en la franja como dato y **sigue siendo
  editable en su sección**.

## Restricciones

- Nada por debajo de 14 px salvo insignias. Sin `uppercase`.
- Clases de Tailwind completas, nunca construidas por concatenación.
- Comentarios en castellano explicando el *por qué*.
- Suite en verde. Hoy son **525 tests**.

## Fuera de alcance

- **La maquetación de las tablas**, el ancho de las vistas y la sección de ST: no
  se tocan.
- **Reutilizar `<x-barra-lateral-formulario>`.** Son dos componentes distintos
  porque significan cosas distintas; parecerse no es motivo para compartirse.
- **Una barra lateral para estas dos.** Descartada arriba, con el motivo escrito
  por si alguien la vuelve a proponer.
