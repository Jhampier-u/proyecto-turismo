# Rediseño de la interfaz para todos los roles

**Fecha:** 2026-08-07
**Estado:** diseño aprobado, pendiente de plan de implementación

## Problema

La interfaz creció matriz a matriz sin que nadie revisara el conjunto. El resultado
es una tarjeta de zona que intenta ser el índice completo del trabajo y ya no cabe
en sí misma.

### Dos sistemas de navegación compitiendo

En `resources/views/operativo/dashboard.blade.php` conviven un desplegable
«Evaluación» y una fila de botones sueltos. Listan matrices distintas y, cuando
coinciden, llevan a sitios diferentes:

| Matriz                 | Desplegable   | Botón suelto | Destinos distintos |
|------------------------|---------------|--------------|--------------------|
| FIT                    | sí            | no           | —                  |
| FET                    | sí            | no           | —                  |
| Potencialidad          | sí → formulario | sí → resultados | **sí**         |
| Percepción             | sí → formulario | sí → resultados | **sí**         |
| Paisaje                | no            | sí           | —                  |
| Valoración Territorial | no            | sí           | —                  |

### Los botones no dicen qué son

El texto del botón colapsa al estado, no al nombre de la matriz. En una zona sin
evaluar, cuatro botones idénticos dicen «SIN EVALUAR» y solo un emoji los
distingue. La información que hace falta desaparece justo cuando más se necesita.

### Dependencias invisibles

`VttController::resultadoFinal` exige que FIT y FET estén confirmadas y, si no lo
están, expulsa al formulario FIT con un error. El botón «Vocación» parece
disponible siempre.

### El conocimiento está repartido

Añadir una matriz obliga a tocar cinco sitios: modelo, `DashboardController`,
vista del dashboard, tabla del admin y rutas. Nada lo comprueba.

**Consecuencia real:** la ruta `admin.zonas.paisaje` existe en `routes/web.php`
pero `resources/views/admin/zonas/index.blade.php` no la enlaza. El admin no
puede llegar a la Matriz de Paisaje desde la interfaz.

### No escala

Quedan cuatro matrices por implementar. Con el diseño actual, la fila de botones
pasa de siete a once.

## Decisiones

Tomadas con el usuario, una a una:

| # | Decisión | Elegido |
|---|----------|---------|
| 1 | Modelo de navegación | Página propia por zona |
| 2 | Agrupación de matrices | Por fase del estudio |
| 3 | Entrada de matriz | Fila con nombre visible y resultado |
| 4 | Guardado a medias | Permitido |
| 5 | Visibilidad del rol | Explícita, con identidad y «quién y cuándo» en cada ficha |
| 6a | Estructura de la página | Todo visible, sin plegar |
| 6b | Tarjeta del listado | «Abrir zona» más atajo a Inventario |
| — | Potencialidad | **Incluida** en el guardado parcial |

## Arquitectura

### Registro central de matrices

`app/Matrices/Registro.php` — array estático, única lista de matrices del
sistema. Cada entrada declara nombre, icono, grupo, modelo, número de criterios,
rutas y dependencias:

```php
'paisaje' => [
    'nombre'     => 'Paisaje',
    'icono'      => 'mountain',
    'grupo'      => 'valoracion',
    'modelo'     => EvaluacionPaisaje::class,
    'criterios'  => 34,
    'rutas'      => ['editar' => '…paisaje.edit', 'ver' => '…paisaje.ponderacion'],
    'depende_de' => [],
],
```

Añadir la matriz nueve pasa a ser una entrada aquí más un método en su modelo.

Los grupos, en orden metodológico:

1. **Base territorial** — Inventario de recursos
2. **Vocación turística** — FIT, FET, resultado VTT
3. **Valoración del territorio** — Potencialidad, Paisaje, Valoración Territorial
4. **Dimensión social** — Percepción, Irritación, Involucrados
5. **Presión y uso** — Concentración, Frecuentación espacial

### Servicio de estado

`app/Servicios/EstadoZona.php` — recibe una zona y un usuario, devuelve las filas
resueltas: estado, subtítulo, ruta destino, si está bloqueada y por qué. Toda la
lógica de «si está confirmada lleva a resultados, si no al formulario» vive aquí,
en vez de repetida en los `@php` de cada vista.

### Componente de fila

`resources/views/components/fila-matriz.blade.php` — recibe una fila y la pinta.
Sin lógica.

### Tarjeta del listado «Mis zonas»

Deja de ser el índice de la zona. Conserva foto, nombre, lugar, una barra de
progreso con «3 / 12 validadas» y dos botones: **Abrir zona** y un atajo a
**Inventario**, que es lo que más se usa a diario y se gana el sitio. Los siete
botones actuales desaparecen: viven dentro de la página de zona.

### Ruta única

`GET /operativo/zona/{zona}` → `ZonaPanelController@show`, dentro del grupo que ya
tiene el middleware `zona`.

`tieneRolOperativo()` incluye al admin (`app/Models/User.php:52`) y
`PerteneceAZona` ya le concede solo lectura en métodos seguros. Los tres roles
entran por la misma URL sin tocar los permisos.

### Lo que se elimina

- Rutas `admin.zonas.{potencialidad,percepcion,paisaje,valoracion_territorial}`
  y sus métodos en `Admin\ZonaController`.
- Las cuatro consultas manuales de `DashboardController`, sustituidas por el
  servicio.

Al no haber una segunda lista de enlaces por matriz, la clase de bug que dejó
Paisaje inaccesible desaparece de raíz.

## Guardado parcial

### Por qué hace falta

Paisaje tiene 34 criterios; Valoración Territorial, 21; Potencialidad, 156. Hoy,
rellenar 30 de 34 y cerrar la pestaña pierde todo el avance: la validación exige
`required` en cada criterio.

### El riesgo que hay que evitar

Las columnas están declaradas `tinyInteger(...)->default(0)`, es decir `NOT NULL`
con defecto 0. Y **0 es una puntuación con significado** — «Afectado»,
«Desfavorable». Permitir guardar a medias sin más convertiría cada criterio sin
contestar en la peor nota posible.

Por eso `nullable` no es un detalle de presentación: es lo que impide que
«no contestado» se confunda con «pésimo».

### Cambios

1. **Migración** — los criterios pasan a `nullable()` sin defecto. Ensanchamiento
   puro: las filas existentes ya tienen valor en todas las columnas. Probar en
   SQLite y en PostgreSQL por separado; `change()` recrea la tabla en SQLite.
2. **Validación por estado** — en `MatrizPonderadaController`:
   - guardando borrador → `nullable|integer|in:…`
   - confirmando → `required|integer|in:…`
3. **Totales honestos** — si falta algún criterio, promedios y total quedan en
   `null`. Una media de 21 de 34 criterios parece un resultado y miente. La ficha
   muestra «21 de 34 respondidos» y ningún valor.
4. **Formularios que distinguen el vacío** — `select-0-2`, `select-0-3` y
   `select-percepcion` estrenan opción «— sin responder —» como valor inicial, en
   vez de preseleccionar 0.

Cinco matrices heredan de `MatrizPonderadaController` — FIT, FET, Percepción,
Paisaje y Valoración Territorial — así que el cambio se hace una vez en la clase
base.

## Potencialidad

Cuelga de `EvaluacionZonaController` con lógica propia. Se incluye por decisión
del usuario, y con razón: **tiene el fallo vivo hoy.**

### El fallo

```php
$reglas[$campo] = 'integer|min:0|max:2';        // sin required

$valores[$campo] = in_array($campo, $camposActivos)
    ? (int) $request->input($campo, 0)          // ausente = 0
    : ($actual->$campo ?? 0);
```

El formulario preselecciona `0 - Nulo` en cada desplegable, y el promedio cuenta
el campo si `isset()` — un 0 está *set*. Un criterio que nadie miró puntúa como
«Nulo» y arrastra la media hacia abajo, sin aviso.

Con 156 campos eso no es un caso raro, es el caso normal. El sistema no puede
distinguir «no lo he revisado» de «lo he valorado y no hay nada». Son dos
afirmaciones muy distintas sobre un territorio.

### Los tres estados

`potencialidad_campos_activos` ya permite desactivar lo que no aplica a la zona
—no hay litoral en la sierra— y redistribuye pesos. Ese mecanismo se conserva.
Lo que falta es separar dos cosas que hoy se confunden:

| Estado          | Significa                  | Cuenta en la media           |
|-----------------|----------------------------|------------------------------|
| Desactivado     | No aplica a esta zona      | No, y redistribuye peso      |
| **Sin responder** | Todavía no lo he mirado  | **No, y bloquea confirmar**  |
| Respondido 0    | Lo miré, no hay nada       | **Sí, como 0**               |

### Cambios

1. Los 156 criterios y los `val_*` calculados pasan a `nullable` sin defecto.
2. `prepararDatos`: `$request->input($campo)` sin el `, 0`.
3. `calcular()`: el promedio pasa de `isset($v[$c])` a `$v[$c] !== null`. La
   lógica de «grupo que no cuenta» ya existe para campos desactivados; se extiende
   a grupos sin respuestas.
4. Al confirmar, todos los campos activos pasan a `required`.
5. Los desplegables arrancan en «— sin responder —».

### Riesgo y mitigación

`calcular()` son unas 120 líneas de redistribución de pesos con cuatro niveles de
anidamiento y **sin un solo test propio**: solo aparece de refilón en
`EvaluacionesTest` e `IntegridadDatosTest`. Es la parte peligrosa del lote.

**Mitigación:** primero se escriben tests de caracterización que fijen el
comportamiento actual (pesos por sección, redistribución al desactivar grupos,
totales FN y FX) y se comprueba que pasan sin tocar nada. Solo entonces se cambia
la lógica.

## Roles

La misma página, tres experiencias:

|                        | Equipo                    | Jefe de zona            | Admin                |
|------------------------|---------------------------|-------------------------|----------------------|
| Cabecera               | «Equipo · Ana Pérez»      | «Jefe de zona · …»      | franja «Modo consulta» |
| Fila sin empezar       | Empezar                   | Empezar                 | *(sin botón)*        |
| Fila en borrador       | Continuar                 | Continuar · **Validar** | Ver                  |
| Fila validada          | Ver                       | Ver · Reabrir           | Ver                  |
| Al terminar una matriz | «Lista para validar — avísale a …» | botón Validar  | —                    |

**Sin botones desactivados.** Donde una acción no corresponde, en su lugar va el
texto que dice quién la hace. Un botón gris no explica nada y se intenta pulsar
igual.

**«Reabrir»** devuelve una matriz validada al estado borrador para poder
corregirla. Solo el jefe de zona. Pide confirmación, porque tiene un efecto que no
se ve desde ahí: los resultados que dependían de ella vuelven a bloquearse —
reabrir FIT bloquea la Vocación del territorio hasta que se valide de nuevo. El
aviso lo dice con esas palabras, nombrando qué se bloquea.

Cada evaluación ya guarda `user_id` y fecha, pero no se muestran en ninguna
parte. Pasan a la ficha de cada matriz: «Ana Pérez, hace 2 días».

## Vistas de administración

### Lista de zonas

La fila pasa de seis botones a tres —**Abrir zona**, Editar, Eliminar— y gana una
columna de progreso («3 / 12»).

### Panel

Las tres tarjetas de texto fijo ganan el número que las hace útiles: usuarios por
rol, lugares, zonas y **zonas sin jefe asignado** —el único dato del panel sobre
el que un admin actúa.

### Usuarios y lugares

Siguen siendo tablas; una tabla está bien para esto. Cambia el tamaño de letra y
se añade lo que falta:

- **Usuarios**: buscador por nombre y correo, filtro por rol, y columna con las
  zonas de cada persona. Hoy, para saber dónde está asignada alguien, hay que
  abrir las zonas una por una.
- **Lugares**: buscador y columna con cuántas zonas usan ese lugar, que avisa
  antes de intentar borrar uno en uso.

## Lenguaje visual

### Tipografía

Las listas usan `text-xs` —12 px, en mayúsculas, con `tracking-widest`—, la peor
combinación posible para leer de un vistazo.

| Elemento             | Hoy                | Nuevo            |
|----------------------|--------------------|------------------|
| Cabecera de página   | 20 px              | 24 px            |
| Título de bloque     | 16 px              | 18 px            |
| Nombre de fila       | **12 px mayúsculas** | 16 px normal   |
| Texto secundario     | 12 px              | 14 px            |
| Insignias            | 12 px              | 12 px *(único sitio permitido)* |

Regla: nada por debajo de 14 px salvo insignias, y se acaban las mayúsculas
forzadas en botones.

### Color

Hoy conviven dos sistemas contradictorios: uno por matriz (morado Potencialidad,
esmeralda Percepción, lima Paisaje, verde azulado Valoración…) y otro por estado
(gris = sin evaluar). Siete colores que no significan nada más un octavo que sí.
Por eso los botones no se entienden: el color promete información y no la da.

**Regla única: el color solo dice el estado. La identidad la dan el icono y el
nombre.**

| Estado       | Color                        |
|--------------|------------------------------|
| Sin empezar  | neutro                       |
| En borrador  | ámbar                        |
| Validada     | verde                        |
| Bloqueada    | gris atenuado, con candado   |

Es el mismo código que ya usan `criterio-escala` y `criterio-pildoras` dentro de
los formularios.

**Restricción técnica:** las clases de Tailwind van siempre completas en un mapa,
nunca construidas por concatenación —el purgado se las come sin avisar.

### Iconos

Los emoji actuales (⭐🧭🏞️🗺️) se dibujan distinto en cada sistema operativo y los
lectores de pantalla los leen mal. Se sustituyen por un componente `<x-icono>` con
una docena de SVG en línea. Sin dependencias nuevas ni cambios de build.

## Pruebas

### El test que habría evitado el bug de Paisaje

```php
foreach (Registro::ENTRADAS as $clave => $entrada) {
    foreach ($entrada['rutas'] as $ruta) {
        $this->assertTrue(Route::has($ruta), "{$clave}: ruta {$ruta}");
    }
    $this->assertTrue(class_exists($entrada['modelo']), $clave);
}
```

Y su pareja: la página de zona muestra tantas filas como entradas tiene el
registro. Una matriz nueva mal enganchada rompe el build en vez de desaparecer de
la interfaz durante meses.

### Resto

- `EstadoZona` en pruebas unitarias: dadas unas evaluaciones, las filas esperadas.
  Sin HTTP ni vistas.
- Vocación aparece bloqueada hasta que FIT y FET están validadas.
- El admin ve «modo consulta» y ningún botón de edición; un POST sigue dando 403.
- Un usuario ajeno a la zona recibe 403.
- Los contadores de la tarjeta cuadran con las evaluaciones reales.
- Se guarda un borrador con criterios en blanco; los que faltan quedan `null`.
- Confirmar con un criterio en blanco se rechaza y no escribe nada.
- Un criterio contestado con 0 sobrevive como 0 y no se confunde con vacío.
  **Es el test que distingue el fallo del arreglo.**
- Con criterios pendientes, promedios y total son `null`.
- En Potencialidad, un campo sin responder no baja la media; un 0 explícito sí.
- En Potencialidad, un grupo entero sin responder no cuenta, igual que uno
  desactivado.
- Desactivar un campo ya respondido conserva su valor por si vuelve a activarse.

### Pruebas existentes que este cambio rompe

No las 118 sobreviven intactas. `PaisajeTest::test_la_tarjeta_aparece_en_el_dashboard`
comprueba `assertSee('🏞️')` y el enlace directo al formulario desde `/mis-zonas`;
con la tarjeta reducida ese enlace ya no está. Hay que reescribirla, junto con sus
equivalentes en `ValoracionTerritorialTest` y `EvaluacionesTest`, para que apunten
a la página de zona.

Es una consecuencia prevista del diseño, no una sorpresa de ejecución.

## Orden de ejecución

1. Registro, `EstadoZona` y sus tests unitarios — sin tocar ninguna vista.
2. Página de zona, componente de fila, tarjeta y lista del admin.
3. Tests de caracterización de `calcular()` en Potencialidad, en verde antes de
   tocar nada.
4. Guardado parcial: migración, validación por estado, formularios.
5. Panel de admin, usuarios y lugares.
6. Limpieza: rutas y métodos del admin que quedan huérfanos.

Los pasos 1–2 ya dejan la aplicación mejor y se pueden entregar aunque el resto se
detenga. El 3 va antes del 4 a propósito.

## Fuera de alcance

**Migrar FIT, FET, Percepción y Potencialidad a los componentes nuevos.** Siguen
usando desplegables (`select-0-2`, `select-0-3`, `select-percepcion`) mientras
Valoración Territorial y Paisaje ya usan tarjetas y píldoras. Es una incoherencia
visible y queda como el siguiente trabajo.

Este lote solo toca esos formularios en lo imprescindible para el guardado
parcial: la opción «— sin responder —».

**Las cuatro matrices pendientes** —Involucrados, Índice Espacial de Frecuentación,
Índice de Concentración, Índice de Irritación— no se implementan aquí. El registro
les deja el sitio hecho.
