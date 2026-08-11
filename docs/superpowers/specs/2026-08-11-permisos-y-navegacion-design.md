# Permisos del admin y navegación entre formulario y resultados

**Fecha:** 2026-08-11
**Estado:** diseño aprobado, pendiente de plan de implementación

## Alcance

Cuatro cambios, en este orden de riesgo:

1. El admin y el equipo rellenan formularios y guardan borradores; solo el jefe
   de zona valida.
2. Pestañas entre formulario y resultados en cada matriz, con los resultados
   bloqueados hasta completarla.
3. Conmutador lista/tarjetas en las dos vistas de zonas.
4. Tipografía de los tres formularios de administración, y aviso de que guardar
   una matriz validada la reabre.

## Decisiones

| # | Decisión | Elegido |
|---|----------|---------|
| 1 | Alcance del permiso del admin | En todas las zonas, con rastro visible de quién editó |
| 2 | El admin ante una matriz validada | Se comporta como el equipo: no puede tocarla |
| 3 | Conmutador de vista | Cada vista gana el formato que le falta; el control se comparte |
| 4 | Preferencia de vista | Zonas tiene la suya, separada de la de inventario |
| 5 | Navegación formulario/resultados | Pestañas arriba; bloqueada = candado y cuánto falta |
| 6 | Aviso de reapertura | Texto junto al botón de guardar |

Decisiones tomadas por quien diseña y aprobadas explícitamente:

| Decisión | Motivo |
|---|---|
| El inventario sigue de solo lectura para el admin | El encargo hablaba de formularios de evaluación. Además contradiría la decisión previa de «ver en solo lectura» sobre esa misma pantalla |
| `puedeEditarEvaluaciones()` se parte en dos en vez de borrarse | Hace dos trabajos bajo un nombre; uno deja de hacer falta y el otro no |
| La paginación del admin se conserva en tarjetas | El conmutador cambia la maquetación, no la consulta |
| Involucrados también lleva pestañas | Si no, es la única matriz que navega distinto |
| El aviso de reapertura solo lo ve el jefe | Es el único que puede provocarla; para los demás sería ruido |

---

## 1. Permiso del admin

### Dónde vive hoy el bloqueo

En tres sitios, no en uno:

```php
// app/Http/Middleware/PerteneceAZona.php — el guardián real
abort_unless($request->isMethodSafe(), 403, 'El administrador puede consultar…');

// app/Models/User.php — consumido por 19 vistas
public function puedeEditarEvaluaciones(): bool { return ! $this->esAdmin(); }

// app/Http/Controllers/Operativo/EvaluacionZonaController::update()
if ($actual->estado === 'confirmado' && $user->esEquipo()) { … }
```

### El efecto colateral que hay que evitar

`PerteneceAZona` protege todo el grupo `operativo/zona/{zona}`, y dentro está
`Route::resource('inventarios')`. Levantar el bloqueo sin más daría al admin
**crear y borrar recursos del inventario**, que no es lo pedido.

**El inventario se queda de solo lectura para el admin.** El permiso nuevo se
limita a las rutas de evaluación.

### Los cuatro cambios

**1. El middleware distingue qué se escribe.** Pasa de «el admin no escribe
nada» a «el admin no escribe en el inventario».

La distinción se hace **por nombre de ruta**, no por método ni por URL: las
rutas del inventario son las únicas del grupo bajo `operativo.inventarios.`, y
el nombre es estable frente a cambios de prefijo o de verbo.

```php
if ($user->esAdmin()) {
    $esInventario = str_starts_with($request->route()->getName() ?? '', 'operativo.inventarios.');

    abort_if(
        $esInventario && ! $request->isMethodSafe(),
        403,
        'El administrador puede consultar el inventario, pero no modificarlo.'
    );

    return $next($request);
}
```

Una ruta nueva de escritura que no sea de inventario queda permitida al admin
por omisión. Es lo correcto para las evaluaciones, pero conviene saberlo: si
algún día se añade otro módulo que el admin no deba escribir, hay que ampliar
esta condición. El test de la sección de pruebas lo fija.

**2. `puedeEditarEvaluaciones()` se parte.** Hoy decide dos cosas distintas bajo
un solo nombre:

| Lo que decide | ¿Sigue haciendo falta? |
|---|---|
| ¿Puedes editar? | No — ahora todos pueden |
| ¿A dónde vuelve «Volver»? | Sí — el admin a `admin.zonas.index`, los demás a `mis-zonas` |

El gate de edición desaparece: el método se elimina y con él el `$readonly` de
las 19 vistas.

El destino de vuelta **no** se replica en 19 sitios —esa duplicación es
justamente lo que produjo los tres fallos de solo-lectura de la rama anterior—.
Se encapsula en `<x-boton-volver>`, que resuelve el destino por el rol y se usa
en las 19:

```blade
{{-- El destino se deriva del rol dentro del componente. Diecinueve copias de
     un ternario es como se pierde una de vista. --}}
<x-boton-volver />
```

**3. El bloqueo del formulario se simplifica.** Hoy:

```php
$bloqueado = ! $user->puedeEditarEvaluaciones() || ($estaConfirmado && ! $esJefe);
```

Como el admin se comporta como el equipo ante una matriz validada, y el admin no
es jefe, el segundo término ya lo cubre. Queda:

```php
$bloqueado = $estaConfirmado && ! $esJefe;
```

**4. El cierre de matriz validada pasa de `esEquipo()` a `! esJefe()`.** Hoy
frena al equipo por nombre; sin este cambio el admin podría reabrir una matriz
cerrada, que es justo lo que la decisión 2 evita.

`$estado = $user->esJefe() ? $request->input('accion_estado') : 'borrador'` **no
cambia**: el admin ya cae en la rama del borrador.

### El rastro visible

`user_id` y la fecha ya se guardan en cada evaluación, y la ficha de la zona ya
muestra «— Ana Pérez, hace 2 días». Se añade ese mismo dato **dentro de la
matriz**, en el formulario y en los resultados. Sin columnas nuevas ni
migración.

### Tests que cambian de sentido

Varios tests afirman que el admin recibe 403 al hacer POST. Se escribieron
cuando eso era el comportamiento correcto. Ahora expresan lo contrario de lo
pedido y hay que **reescribirlos, no borrarlos**:

- El admin guarda un borrador en una matriz de cualquier zona.
- El admin **no** puede validar: enviar `accion_estado=confirmado` guarda
  borrador igual.
- El admin **no** puede editar una matriz ya validada.
- El admin sigue **sin** poder crear ni borrar recursos del inventario.
- El equipo conserva su comportamiento exacto.
- El jefe conserva su comportamiento exacto, incluida la validación.
- **Un guardián sobre el middleware:** recorre las rutas del grupo
  `operativo.zona` y comprueba que las de escritura del inventario siguen dando
  403 al admin. Si mañana alguien añade una ruta de inventario con otro verbo,
  este test la cubre sin tocarlo.
- `<x-boton-volver>` lleva al admin a `admin.zonas.index` y a los demás a
  `mis-zonas`. Es lo único que sobrevive del antiguo `$readonly`, y sin test
  volvería a desincronizarse en silencio.

---

## 2. Pestañas entre formulario y resultados

### El problema

Ocho de los nueve formularios ya enlazan a sus resultados, pero son **nueve
enlaces escritos a mano**, cada uno con su texto y su sitio, y ninguno indica si
al otro lado hay algo que ver. Es el mismo patrón de conocimiento repetido que
dejó a Paisaje sin enlace en el admin.

### El componente

`<x-pestanas-matriz>` se coloca **arriba** de las dos vistas de cada matriz.
Recibe la clave de la matriz, la zona y el recuento de criterios respondidos.

```
┌──────────────┬──────────────────────────────┐
│ Formulario   │  🔒 Resultados               │
│  (activa)    │     faltan 4 de 34 criterios │
└──────────────┴──────────────────────────────┘
```

Completa, «Resultados» es una pestaña clicable. Incompleta, **no es un botón
gris**: es texto con candado y el motivo. Respeta la regla global de «sin
botones desactivados» y muestra un dato que hoy el formulario no da en ninguna
parte: cuánto llevas.

Rutas y nombres salen de `Registro::ENTRADAS`. Añadir la décima matriz no
obliga a tocar el componente.

### El recuento

`EstadoZona` ya cuenta criterios respondidos, pero para toda la zona y en un
método privado. El recuento de **una** matriz se extrae a algo pequeño que
consuman los dos: la ficha de la zona y las pestañas. Si cada uno contara por su
cuenta, habría dos formas de responder la misma pregunta.

### Involucrados

No es un formulario de criterios sino un CRUD de actores. «Completa» ahí no es
«34 de 34» sino que haya al menos un actor con sus campos. Lleva las mismas
pestañas, con su condición de desbloqueo definida por su propio modelo.

### Tests

- Las nueve matrices muestran las pestañas en las dos vistas.
- Con la matriz incompleta, la pestaña de Resultados no lleva enlace y el texto
  dice cuántos faltan.
- Al completarla, aparece el enlace.
- **El recuento de las pestañas coincide con el de la ficha de la zona.** Es el
  test que impide que las dos cuentas se separen.
- Un test que recorre `Registro::ENTRADAS` y falla si una matriz nueva no
  engancha sus pestañas.

---

## 3. Conmutador lista/tarjetas en zonas

### Lo que se extrae

Inventario ya tiene el conmutador, con la preferencia en `localStorage` y el
control escrito a mano dentro de la vista
(`resources/views/operativo/inventarios/index.blade.php`).

Se extrae a `<x-conmutador-vista>`, que pinta los dos botones y nada más. **No
sabe dónde se guarda la preferencia ni cómo se llama la variable**; eso lo pone
quien lo usa:

```blade
<div x-data="{ vista: localStorage.getItem('zonas_vista') || 'tarjetas' }"
     x-init="$watch('vista', v => localStorage.setItem('zonas_vista', v))">

    <x-conmutador-vista modelo="vista" />
```

Inventario conserva `inventario_vista`; zonas estrena `zonas_vista`.

### El formato que falta

| Vista | Tiene | Gana | Arranca en |
|---|---|---|---|
| `/mis-zonas` (jefe, equipo) | tarjetas | lista: nombre, lugar, progreso y sus dos botones | tarjetas |
| `/admin/zonas` | tabla | tarjetas: foto, nombre, lugar, jefe, miembros, progreso y sus tres botones | lista |

Cada una empieza en el formato que ya tenía, así que quien no toque nada no nota
el cambio.

### La paginación

La tabla del admin pagina de diez en diez; las tarjetas del operativo no
paginan. **El conmutador cambia la maquetación, no la consulta:** en tarjetas el
admin sigue viendo diez y sigue habiendo paginador. Cargarlas todas convertiría
un cambio visual en uno de rendimiento.

### Tests

Las dos maquetaciones se envían en el HTML —una oculta—, así que se puede
comprobar en servidor:

- Ambas contienen los mismos datos y enlaces. Si alguien añade un botón a solo
  una, salta.
- La del admin muestra jefe y número de miembros en los dos formatos.
- La paginación sigue funcionando con el parámetro de página.

**Fuera de cobertura, y se dice:** que el botón conmute de verdad y que la
preferencia sobreviva a una recarga es JavaScript en el navegador. Se verifica a
mano.

---

## 4. Detalles menores

### Tipografía

`users/form`, `lugares/form` y `zonas/form` se quedaron fuera del rediseño. Se
les aplica la escala ya fijada: nada por debajo de 14 px salvo insignias, sin
mayúsculas forzadas, etiquetas a 14 px y campos a 16.

### Aviso de reapertura

En los nueve formularios, cuando la matriz está validada y quien mira es el
jefe:

> **Esta matriz está validada.** Guardarla la devolverá a borrador y habrá que
> validarla de nuevo.

Solo al jefe: es el único que puede provocarlo. Para el equipo y el admin el
formulario está bloqueado en ese estado, así que el aviso sería ruido.

El texto se toma del que ya usa Involucrados, para no tener dos maneras de decir
lo mismo.

---

## Orden de ejecución

1. **Permiso del admin.** El más arriesgado, y del que dependen las pestañas y
   los avisos.
2. **Pestañas** formulario/resultados.
3. **Conmutador** lista/tarjetas en zonas.
4. **Detalles menores.**

Los pasos 3 y 4 son independientes de los dos primeros: si algo se tuerce
arriba, siguen siendo entregables por separado.

## Fuera de alcance

- **Dar al admin permiso sobre el inventario.** Decisión explícita, reversible
  si se pide.
- **La décima matriz**, el Índice Espacial de Frecuentación, que sigue bloqueada
  por una contradicción del instrumento original que hay que aclarar con su
  autor.
- **Un botón «Reabrir» explícito**, separado de guardar. Se descartó por añadir
  ruta y acción nuevas a algo que ya funciona.
