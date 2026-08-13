# Tarea 7 — Adoptar `<x-tarjeta>` en vistas operativas — Informe

## Estado: DONE

## Commit

- `df26f85` — `refactor(operativo): las cajas blancas pasan a x-tarjeta`
  (30 ficheros, `resources/views/operativo/` y `resources/views/components/`; `package-lock.json` quedó fuera del commit).

## Resultado de `php artisan test`

```
Tests:    568 passed (3302 assertions)
Duration: 131.35s
```

Ningún test existente se modificó. Los 568 coinciden con lo esperado por el brief (562 + 4 de `TarjetaTest`, ya presentes desde la Tarea 6, ya que esta tarea no añade ninguno).

## Cajas convertidas

**48 cajas** en **29 vistas** pasaron de `<div>`/`<section>` escritos a mano a `<x-tarjeta>`:

- `operativo/dashboard.blade.php` (4)
- `operativo/evaluacion_concentracion/{form,ponderacion}.blade.php` (3)
- `operativo/evaluacion_fet/{form,ponderacion}.blade.php` (2)
- `operativo/evaluacion_fit/{form,ponderacion}.blade.php` (2)
- `operativo/evaluacion_irritacion/{form,ponderacion}.blade.php` (2)
- `operativo/evaluacion_paisaje/{form,ponderacion}.blade.php` (5)
- `operativo/evaluacion_percepcion/{form,ponderacion}.blade.php` (2)
- `operativo/evaluacion_potencialidad/ponderacion.blade.php` (3)
- `operativo/evaluacion_valoracion_territorial/{form,ponderacion}.blade.php` (5)
- `operativo/frecuentacion/{form,index,resultados}.blade.php` (4)
- `operativo/inventarios/{create,edit,index,show}.blade.php` (7)
- `operativo/involucrados/{form,index,resultados}.blade.php` (4)
- `operativo/vtt/resultado.blade.php` (1)
- `operativo/zona/panel.blade.php` (2)
- `components/auth-card.blade.php` (1)
- `components/barra-lateral-formulario.blade.php` (1)

Regla de padding aplicada de forma consistente: cuando el padding original de la caja era exactamente `p-6` (el valor por defecto del componente), se soltó y quedó implícito; en cualquier otro caso —`p-4`, `p-8`, `p-10`, padding asimétrico como `px-6 pt-5 pb-2`, o ninguno (cajas que envuelven una tabla a sangre o una lista con `divide-y`)— se usó `:padding="false"` y el padding original se conservó como clase suelta, para no cambiar el espaciado visual sin que la tarea lo pidiera.

## Hallazgo no anticipado por el brief: `@js()` no se compila dentro de `<x-componente>`

Blade compila las etiquetas de componente (`<x-tarjeta ...>`) **antes** que las `@directivas` personalizadas. Cuando `@js(...)` vive dentro de un atributo de una etiqueta de componente (p. ej. `x-data="{ valores: @js(...) }"`), queda como texto literal en el HTML final en vez de convertirse en `JSON.parse('...')` — la hidratación de Alpine se rompe en silencio (el contador de respondidos y la media se quedan en 0, `old()` deja de repoblar el formulario tras un error de validación).

Esto no era un riesgo teórico: al convertir los cinco bloques que llevan `x-data` con `@js()` en el mismo tag (FET, FIT, Paisaje, y los dos bloques RTT/UC de Valoración Territorial), `ValoracionTerritorialTest` y `GuardadoParcialTest` se pusieron en rojo de inmediato (4 tests, detectando exactamente esto). Verifiqué con un script de aislamiento (`Blade::render()` sobre un fragmento mínimo) que `{{ Illuminate\Support\Js::from(...) }}` produce el mismo `JSON.parse('...')` que `@js()` —es literalmente lo que usa por debajo— y sí se compila correctamente dentro de la etiqueta de componente. Con ese cambio los 4 tests volvieron a verde sin tocar ninguna aserción.

Se documentó con un comentario en castellano en cada uno de los cinco sitios afectados.

## Cajas que quedaron sin convertir

| Sitio | Motivo |
|---|---|
| `components/resumen-lista.blade.php:48` | Excepción nombrada explícitamente en el brief: su `flex flex-wrap items-center justify-between gap-4` es maquetación interna: convertirla exige mover ese `flex` a un hijo, cambiando la estructura de un componente que se acaba de revisar en otra tarea de esta rama. |
| `components/modal.blade.php:68` | **Juicio propio, no venía en el brief.** Es la superficie de un diálogo modal (`x-show`/`x-transition`, overlay con `bg-gray-500 opacity-75`), no una caja de contenido de página. Su `shadow-xl` y la ausencia de borde son deliberados para elevarla sobre su propio overlay oscuro; forzar `border-gray-200/80` + `shadow-sm` de `<x-tarjeta>` reduciría esa elevación de forma visible. El brief no la nombra como excepción, así que la dejo fuera y lo marco como duda en vez de decidirlo yo solo. |
| `components/secondary-button.blade.php:1` | Falso positivo del grep de detección: es un `<button>`, no una caja. |
| `operativo/inventarios/show.blade.php:89` | Falso positivo del grep de detección: es un `<a>` pequeño superpuesto sobre una miniatura ("Ver Original" al hover), no una tarjeta de contenido. |
| `components/tarjeta.blade.php:19` | Es el propio código fuente de `<x-tarjeta>`, no un candidato. |

## Dudas

1. **`components/modal.blade.php`** — ¿corresponde a esta tarea o a una posterior? Es la única caja que dejé sin convertir por criterio propio (no por instrucción del brief ni por ser un falso positivo del grep). Si se considera que sí debía adoptar `<x-tarjeta>`, el cambio es sencillo pero implica aceptar una reducción de `shadow-xl` a `shadow-sm` y añadir un borde a un panel que hoy flota sin él sobre su overlay.
2. La regla que usé para decidir cuándo usar `:padding="false"` con el padding original conservado (en vez de forzar todo a `p-6`) no está en el brief ni en las pistas dadas; la inferí del propio texto de `<x-tarjeta>` ("cada una con su variación mínima de... espaciado") y de la necesidad de evitar clases de padding duplicadas/en conflicto dentro de la misma cadena `class`. Si la intención real de la Fase 0 era homogeneizar también el espaciado a `p-6` en todos los casos, faltaría un paso adicional deliberado (fuera del alcance "sin cambiar la estructura ni el aspecto sin que se pida").
