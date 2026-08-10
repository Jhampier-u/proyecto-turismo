# Estado del proyecto — traspaso entre máquinas

**Fecha:** 10 de agosto de 2026 (actualizado al terminar `cabos-sueltos`)
**Para:** continuar en otro ordenador sin perder contexto.

Este documento reemplaza a `ESTADO-MATRICES.md`, que quedó desfasado.

---

## 1. Qué es el proyecto

Sistema Laravel 12 de gestión turística territorial. Un **admin** crea zonas y
asigna personal; un **jefe de zona** y su **equipo** rellenan matrices de
evaluación sobre cada zona. El jefe valida, el equipo solo rellena, el admin
solo consulta.

Cada matriz es un instrumento de un estudio de planificación turística: decenas
o centenares de criterios puntuados en una escala, agrupados por categoría y
ponderados hasta un resultado. **Hay nueve, y las dos últimas ya no encajan en
esa frase**: Involucrados puntúa una lista variable de actores en vez de un
formulario fijo, y Concentración cuenta cantidades sin escala acotada. Conviene
saberlo antes de suponer que la décima será igual que las siete primeras.

## 2. Entorno

- **PHP 8.2 y Composer, nativos.** `php artisan test` tarda unos 7 s. La versión
  exacta varía por máquina (8.2.33 en una, 8.2.12 de XAMPP en otra) y da igual
  mientras sea 8.2: `composer.json` fija ahí `config.platform.php`.
- **Node/npm nativos.** `npm run build` tarda unos 4 s.
- **Hay que construir los assets antes de correr los tests.** En un clon recién
  hecho, sin `public/build/manifest.json`, `@vite` revienta al renderizar y
  fallan de golpe unos 57 tests que no tienen nada roto. `npm run build` una vez
  y desaparecen.
- Base de datos: **SQLite** en desarrollo y tests, **PostgreSQL 16** en
  producción (Render). Las migraciones se comportan distinto en cada uno.
- Python está en `C:\Python314\python.exe`, accesible como `python` desde
  PowerShell pero **no** desde bash, donde `python3` es otro intérprete sin
  `openpyxl`. Importa para los generadores de `database/matrices/`.

**Docker: no se toca.** Hay contenedores de otros proyectos corriendo en la
máquina. Nunca `docker rm`, `docker stop`, `docker container prune` ni
`docker volume prune`. Si alguna vez hace falta un contenedor, solo
`docker run --rm`, y eliminarlo por nombre explícito.

### Credenciales locales

`jefe@local.test`, `equipo@local.test`, `admin@local.test` — contraseña
`password` en las tres. Servidor local: `php artisan serve`.

## 3. Dónde está el trabajo

### Rama `main` — fusionada y subida a GitHub

El **rediseño de la navegación** está terminado, revisado y en `origin/main`
(último commit `a325cb5`). Suite: 171 tests.

Qué cambió:

- `app/Matrices/Registro.php` — única lista de matrices del sistema. Añadir una
  matriz nueva es una entrada aquí más un método en su modelo.
- `app/Servicios/EstadoZona.php` — traduce zona + usuario a las filas de su
  página. Toda la lógica de «si está confirmada lleva a resultados, si no al
  formulario» vive aquí.
- `app/Http/Controllers/Operativo/ZonaPanelController.php` y
  `resources/views/operativo/zona/panel.blade.php` — la página de zona, servida
  a los tres roles por la misma URL.
- La tarjeta de `/mis-zonas` pasó de siete botones a dos más una barra de
  progreso.
- `User::puedeEditarEvaluaciones()` — el predicado único de solo-lectura.

**El bug que cerró:** la Matriz de Paisaje llevaba meses siendo inalcanzable
para el admin. Su ruta existía, ninguna vista la enlazaba, y nada lo detectaba
porque el conocimiento de «qué matrices existen» estaba repartido por varias
vistas. Ahora `RegistroMatricesTest` recorre el registro y falla si una ruta o
un modelo declarado no existe, y `PaginaZonaTest` comprueba que la página pinta
todas las entradas, como jefe **y** como admin.

### Rama `guardado-parcial` — TERMINADA y fusionada en `main`

Las ocho tareas están hechas y revisadas. Suite: **209 tests**.

| Tarea | Estado | Commit |
|---|---|---|
| GP1 — caracterización de Potencialidad | hecha y revisada | `87d9b3d`, `5b6d200` |
| GP2 — criterios a nullable | hecha y revisada | `f5ff57f` |
| GP3 — obligatoriedad por estado | hecha, revisada después | `aa80348`, `9a57d12` |
| GP4 — vistas de resultados con matriz incompleta | hecha | `356a2f9` |
| GP5 — guardado parcial en Potencialidad | hecha | `0e43326` |
| GP6 — formularios que distinguen el vacío | hecha | `cfb6aa8` |
| GP7 — progreso real en la página de zona | hecha | `94d485f` |
| GP8 — revisión final de rama | hecha | `ecb560a` |

Fusionada en `main` y subida a `origin`. Las tres migraciones **ya se probaron
contra un PostgreSQL 16.14 real**, reproduciendo el escenario de producción
(tablas con datos, no un `migrate:fresh`); ver §4.

Queda un punto abierto, de otra naturaleza: **la suite no se puede ejecutar
entera sobre Postgres** por un problema de aislamiento entre tests. También
en §4.

#### Lo que se apartó del plan, y por qué

- **GP4** usa un componente compartido (`x-matriz-sin-resultados`) en vez de
  cinco copias del mismo aviso. Valoración Territorial y Percepción conservan
  el suyo, que estaba mejor escrito; solo se ensancha su condición.
- **GP5** incluye la vista de resultados de Potencialidad, que el plan no
  mencionaba: hasta GP5 esa matriz no podía tener totales en null, y a partir
  de GP5 sí. Sin eso, GP5 habría reintroducido en Potencialidad justo el fallo
  que GP4 quitó de las otras cinco.
- **GP7** no usa el `min(respondidos, criterios)` del plan, que devuelve el
  número correcto tapando un filtro mal puesto. En su lugar el filtro es
  público y un test lo ata al esquema real de las seis tablas. De paso quedan
  verificados los criterios declarados en el registro para FIT, FET, Percepción
  y Potencialidad, que hasta ahora solo se habían comprobado a mano.

#### Tres defectos que encontró la revisión, no los tests

Los tres estaban tapados por tests que miraban al sitio equivocado. Conviene
tenerlos presentes al revisar lo que venga:

1. **El mensaje «Llevas 3 de 34 criterios» no lo veía nadie.** Ninguna vista de
   formulario de matriz pintaba `session('success')`. El test que lo cubría
   afirmaba sobre la sesión, no sobre la página. Arreglado con
   `x-flash-exito`; el test ahora exige verlo.
2. **Validar con un hueco borraba lo ya respondido.** Ningún formulario
   repoblaba desde `old()`, y confirmar sin completar no persiste nada: 33
   respuestas perdidas por olvidar una. Arreglado en los componentes.
3. **`test_cero_campos_activos_no_revienta_y_todo_da_cero` pasaba en falso.**
   `assertEqualsWithDelta(0.0, null)` compara iguales, así que el test seguía
   verde sin distinguir «no hay resultado» de «el resultado es cero» — la
   distinción entera de esta rama. Ahora afirma `assertNull`.

#### Un aviso que quedó pendiente aquí y se arregló después

Los mensajes de validación salían **en inglés y con el nombre de columna crudo**:
«The ep cambios tiempo field is required». Antes casi no se veía —el formulario
mandaba todos los campos y un error era raro—, pero con el guardado parcial
«confirmar cuando falta algo» pasó a ser un camino normal. Resuelto en la rama
`mensajes-validacion`; ver más abajo.

### Rama `irritacion-turistica` — séptima matriz, terminada

El **Índice de Irritación Turística**: dos bloques de seis atributos —visitantes
y localidad receptora—, dos resultados independientes y una clasificación Bajo /
Moderado / Crítico. Suite: **247 tests**.

Es la primera matriz del sistema con **escala inversa**: va de 0 a 10 y un 10 es
irritación crítica, no un sobresaliente. Las otras seis van al revés, y eso es lo
que más fácil se implementa mal. De ahí que tenga control de entrada propio
—`x-select-0-10`, con la clasificación en el texto de cada opción— en vez de
reutilizar los componentes de tarjeta y píldora, que colorean por posición dando
por hecho que más alto es mejor.

La definición del instrumento vive en `app/Matrices/Irritacion.php`: bloques,
etiquetas, umbrales, escala, tramos, interpretaciones y `clasificar()`. El
modelo, el controlador y las dos vistas beben de ahí y ninguno importa a los
otros. Se llegó a esa forma corrigiendo el diseño a mitad de camino: la versión
inicial descartaba esa clase contando solo los nombres de campo, y acabó con una
vista importando un modelo de Eloquent.

**Migración verificada contra PostgreSQL 16 real**, no solo SQLite: los doce
criterios quedan `smallint` nullable sin defecto y los dos promedios `numeric`
nullable.

Dos cosas que la revisión final dejó anotadas y conviene no perder:

- **Sus dos componentes llevan el instrumento en el nombre a propósito**
  (`select-irritacion`, `insignia-clasificacion-irritacion`). Se llamaban
  `select-0-10` e `insignia-clasificacion`, y ese nombre genérico era una
  trampa: la insignia fija Bajo→verde y Crítico→rojo, que solo es cierto con
  escala inversa. `EvaluacionPaisaje::lecturaDe()` devuelve `Alto/Medio/Bajo`
  donde Bajo es el peor resultado, así que la primera reutilización habría
  pintado de verde lo peor sin que nada saltara. Si aparece otra matriz 0-10
  —Frecuentación es candidata—, necesita su propio componente o que este reciba
  el clasificador por parámetro.
- **`decimal(5, 3)` va justo de dígitos.** 10.000 ocupa exactamente la precisión
  declarada. Hoy sobra, pero si alguna vez se ensancha la escala, PostgreSQL dará
  error donde SQLite calla.

### Deuda conocida, no de esta rama — RESUELTA en `deudas-registro`

El mensaje de formulario bloqueado decía «Solo el Jefe de Zona puede reabrir o
editar una matriz validada» **también cuando la matriz no estaba validada** y el
bloqueo era por rol —el caso del admin sobre un borrador—. Estaba igual en los
formularios de FIT y Percepción desde antes; con Irritación eran tres. Para
cuando se corrigió, con Concentración ya eran cuatro: ver §3, rama
`deudas-registro`.

### Rama `involucrados` — octava matriz, terminada

La **Matriz de Involucrados Turísticos Territoriales**, y la primera que **no es
un formulario de criterios**: puntúa una lista variable de actores, cada uno con
once criterios en tres atributos —poder, legitimidad y urgencia— y tres casillas
que marcan si los **posee**. De esas casillas sale su tipo de Mitchell. Suite:
**291 tests**.

**Obligó a ensanchar el registro con un cuarto tipo de entrada.** Hasta ahora
había `matriz` (estado sin lista), `inventario` (lista sin estado) y `resultado`
(derivado). Esta es una lista **con** estado, así que existe `actores` y
`EstadoZona` tiene una rama propia: su detalle es «5 actores, 2 sin completar»,
no «21 de 34 respondidos», porque no hay denominador fijo. Ese ensanchamiento se
hizo primero y solo, con la suite en verde antes de tocar la matriz.

**Dos propiedades que no tiene ninguna otra matriz, y que no hay que
«arreglar»:**

- **La normalización es relativa al conjunto.** Cada grado se divide por la suma
  de todos los actores, así que **añadir un actor cambia el resultado de los
  demás**. Es del instrumento y está decidido a conciencia; hay un test que fija
  esa propiedad a propósito para que nadie la corrija de buena fe.
- **Tocar una lista ya validada la devuelve a borrador.** Las otras siete
  recalculan el estado en cada guardado, así que reabren solas; aquí el CRUD de
  un actor no tocaba la configuración, y una lista «cerrada» que se movió
  invalida todos los valores normalizados.

**Migraciones verificadas contra PostgreSQL 16 real**: los once criterios quedan
`smallint` nullable sin defecto y los tres booleanos `not null` con `false`.

**Una decisión que conviene entender antes de tocarla:** cuando ningún actor
posee un atributo, la fórmula divide por cero y se devuelve `1.0`, que es el
neutro del producto —devolver `0.0` pondría la relevancia de todos a cero y
colapsaría el ranking entero—. Como `1.00` es también un valor legítimo («justo
en la media»), la pantalla marca esos atributos como que no diferencian, en vez
de pintar un número que parecería una medida.

### Rama `concentracion` — novena matriz, terminada

El **Índice de Concentración Turística**, y la primera que **cuenta cosas** en vez
de puntuar criterios en una escala acotada: 113 cantidades en dos bloques —77
atractivos, entre culturales y naturales, y 36 subcategorías de planta turística
repartidas en 10 sectores—. Suite: **337 tests**.

**Los 113 nombres se generan desde el Excel**, no se teclean:
`database/matrices/generar_concentracion.py` escribe `App\Matrices\Concentracion`.
Un nombre mal copiado no rompe ningún test, solo desvía un conteo en silencio.
El generador **aborta** si dos filas producen el mismo nombre —«Cuevas» aparece
en litoral y en montaña— o si alguno pasa de **63 bytes**, que es el límite de
identificador de PostgreSQL: cuatro lo pasaban, el más largo medía 82, y Postgres
los habría truncado **sin avisar**. Las abreviaturas que los acortan están a la
vista en un diccionario del propio generador.

**El cálculo vive aparte, en `App\Matrices\ConcentracionCalculo`**, y no es
manía de orden: `Concentracion.php` lo reescribe entero el generador, así que un
cálculo escrito dentro no sobreviviría a una regeneración.

**Tres cosas propias que no hay que «arreglar»:**

- **La escala no tiene tope.** Una zona puede tener tres hoteles o cuatrocientos,
  así que el controlador sobreescribe `reglaValor()` a `integer|min:0`. `escala()`
  existe porque es abstracto y **no se usa**; está dicho en su comentario.
- **`Pi = 100/√Sia` baja cuando el sector crece.** Un sector con más
  establecimientos tiene un `Pi` **menor**. Es del instrumento, es
  contraintuitivo, y la pantalla lo explica al pie para que nadie lo lea como un
  error.
- **`Pi` no existe para un sector vacío**, no vale cero: la pantalla lo marca
  como que no aplica. El `ICT` de ese sector sí vale 0, y ese 0 es correcto —«no
  aporta nada al reparto»—. Lo normal es que una zona rural tenga varios sectores
  a cero.

**Es la primera matriz sin ninguna columna calculada.** Porcentajes, `Sia`, `Pi`
e `ICT` se derivan siempre; guardarlos sería una segunda fuente de verdad. Un
`calcular()` que devuelve `[]` no incomoda a la clase base.

**Los atractivos no se derivan del Inventario, y no es por coste.** El
denominador del índice es *todo lo que tiene el territorio*; el del inventario es
*lo que el equipo ha llegado a registrar*. Derivar uno del otro convertiría
«cuánto trabajo de campo llevamos» en «qué hay», en silencio. Lo que lo haría
correcto: que un inventario pudiera declararse **cerrado por zona**, como
Involucrados cierra su lista de actores. Ese día merece la pena; el diseño no
cierra la puerta.

**Verificado contra PostgreSQL 16 real**: la migración corre, las 113 columnas
quedan `integer` nullable y sin defecto —un defecto de 0 convertiría «no lo he
contado» en «no hay ninguno»—, los nombres coinciden byte a byte con
`Concentracion::campos()`, y la suite entera da los mismos 337 verdes que en
SQLite.

### Rama `mensajes-validacion` — terminada

Los errores de validación ya salen **en castellano y nombrando el campo como lo
ve el evaluador**, no como columna. Suite: **347 tests**.

**La decisión que importa:** las etiquetas **no se copian** a
`lang/es/validation.php`. Serían centenares de campos duplicados, y en cuanto
alguien cambiara una etiqueta en su instrumento el mensaje de error seguiría
diciendo la vieja **sin que nada fallara** — el mismo patrón de segunda fuente de
verdad que este proyecto lleva meses quitando. En su lugar,
`MatrizPonderadaController` tiene un hook `etiquetas()` que cada matriz
sobreescribe apuntando a donde sus etiquetas **ya viven**, y se pasa como tercer
argumento de `validate()`.

`app.locale` se fija **en la configuración, no en `.env`**: el `.env` no viaja a
Render. El `fallback_locale` se deja en inglés a propósito, para que una regla sin
traducir se note en vez de romper.

**Siete de las nueve matrices** dan la etiqueta del instrumento al escribir esta
rama. **FIT y FET no**, y no era un olvido: sus controladores solo declaraban
`dimensión => [campos]`, sin texto, y los nombres que ve el evaluador existían
**únicamente como literales sueltos en sus dos vistas Blade**. Derivarlos exigía
copiarlos a mano —la segunda fuente de verdad que este cambio evita— o
refactorizar los dos formularios en producción. Se quedaron con el mensaje en
castellano y el campo legible («recursos culturales»), hasta que la rama
`fit-fet-componentes` (ver §3) les dio una clase en `App\Matrices` igual que a
las otras: ahora dan las nueve de nueve.

**Una limitación del lenguaje, no del diseño:** el test ideal cambiaría una
etiqueta del instrumento y comprobaría que el mensaje cambia con ella. Cinco de
las siete las declaran como `public const`, que PHP no deja reasignar ni por
Reflection. Para esas, el test **lee** la etiqueta vigente y construye el mensaje
esperado con ella; detecta la deriva, que es el riesgo real. Percepción sí las
tiene en una propiedad estática, y ahí el test hace la mutación de verdad.

### Rama `deudas-registro` — dos deudas pequeñas, terminada

Dos correcciones puntuales, agrupadas porque las dos son la misma familia de
bug: **el sistema afirmando algo que no es cierto**. Suite: **356 tests**.

**1. Guardián inverso de rutas.** `RegistroMatricesTest` solo comprobaba que
cada ruta *declarada en el registro* existiera; le faltaba la dirección
contraria —que toda ruta de matriz *declarada en `routes/web.php`* tuviera una
entrada en el registro—. Sin ella, alguien puede añadir un controlador y sus
rutas, olvidarse de `Registro::ENTRADAS`, y la suite queda verde con la matriz
invisible desde la página de zona: el bug de Paisaje, entrando por la puerta de
atrás. Los planes de Involucrados y Concentración ya lo habían anotado como
guardián pendiente sin implementar.

El nuevo `test_toda_ruta_de_matriz_pertenece_a_una_entrada_del_registro`
compara por **espacio de nombres** de ruta (`operativo.evaluacion_fit.edit` →
`evaluacion_fit`), no por ruta exacta ni por patrón de texto: el registro solo
guarda dos rutas por entrada («editar» y «ver»), nunca el POST de guardado ni
las rutas internas de un CRUD como Involucrados, así que exigir una
coincidencia exacta habría fallado hoy mismo con el sistema sano. Dos
excepciones explícitas y comentadas (`operativo.dashboard`,
`operativo.zona.panel`): son puntos de navegación hacia las matrices, no
matrices. Verificado a mano que el test falla si se agrega una ruta de matriz
sin su entrada.

**2. El aviso de formulario bloqueado mentía sobre su motivo.** Documentado
arriba como deuda de FIT, Percepción e Irritación; con Concentración ya eran
**cuatro**. El bloqueo tiene dos causas distintas —el admin nunca edita
evaluaciones (por rol), el equipo deja de poder hacerlo en cuanto se confirma
(por estado)— y las cuatro vistas mostraban la frase de la segunda causa
también en la primera. `$bloqueadoPorRol` separa el motivo en la vista, y
`x-aviso-bloqueo-matriz` centraliza las dos frases correctas en un componente
—como ya hacían `x-matriz-sin-resultados` y `x-flash-exito`— para que no
queden cuatro copias ligeramente distintas divergiendo con el tiempo.

Paisaje y Valoración Territorial no se tocaron: cuando el bloqueo no es por
validación, simplemente no pintan ningún aviso —no dicen nada falso, aunque
tampoco expliquen el motivo—. Potencialidad e Involucrados ya distinguían bien
las dos causas. FET quedó igual: su «Evaluación FET cerrada» es vago pero no
atribuye una causa incorrecta, así que no es esta deuda; queda anotado por si
alguien quiere unificarlo el día que se toque ese formulario.

*(Ese día llegó en `cabos-sueltos`, cabo 2: FET también usa
`x-aviso-bloqueo-matriz` ahora. Ver esa rama en §3.)*

### Rama `reabrir-matriz` — premisa falsa, cerrada sin código nuevo

El encargo era «reabrir una matriz validada», partiendo de que este documento
la daba como «en el diseño, sin implementar» y de que el aviso de bloqueo
promete justo esa capacidad al Jefe de Zona («Solo el Jefe de Zona puede
reabrir o editar una matriz validada»). **La premisa era falsa: ya estaba
implementada, en las ocho matrices de formulario.** Suite: **362 tests**.

`EvaluacionZonaController::update()` decide el estado ANTES de validar los
criterios, y solo bloquea la escritura de `$user->esEquipo()` sobre una
matriz confirmada:

```php
if ($actual && $actual->estado === 'confirmado' && $user->esEquipo()) {
    return back()->with('error', $this->mensajeCerrada());
}
```

Nada ahí bloquea al jefe. Y en la vista, `$bloqueado` en las ocho formas de
`evaluacion_*/form.blade.php` es
`! puedeEditarEvaluaciones() || ($estaConfirmado && !$esJefe)`: para el jefe,
el segundo término es siempre falso, así que el formulario no se deshabilita
y el botón «Guardar Borrador» —que manda `accion_estado=borrador`— sigue
visible aunque la matriz esté confirmada. Enviarlo pone `estado='borrador'`
conservando los valores ya respondidos, que es exactamente «reabrir». El
admin nunca llega: `PerteneceAZona` corta con 403 cualquier método no seguro
suyo antes de que la petición toque el controlador.

Verificado con un test antes de escribir nada (`tests/Feature/ReabrirMatrizTest.php`,
seis tests, incluida Potencialidad para confirmar que el mecanismo no depende
de heredar `MatrizPonderadaController`), no solo leyendo el código:

1. El jefe reabre y los valores quedan intactos —incluidas las columnas
   calculadas, que se recomputan sobre los mismos datos—.
2. El equipo no puede: mismo bloqueo que ya tenía, sin cambios.
3. El admin no puede: 403 del middleware si insiste por POST directo.
4. **Los dependientes ya se defendían solos.** `Registro::ENTRADAS['vtt']`
   depende de `fit` y `fet`; tanto `EstadoZona::filaResultado()` como
   `VttController::resultadoFinal()` comprueban en vivo, en cada petición, que
   las dos sigan confirmadas —no memorizan «disponible» en ningún sitio—.
   Reabrir FIT hace que la fila de Vocación del Territorio en el panel de zona
   pase a `bloqueada` con el mismo mensaje que ve cualquiera que nunca llegó a
   validarlas («Se desbloquea al validar: Factores intrínsecos (FIT)»), y que
   `/resultado-vtt` redirija al formulario de FIT en vez de enseñar nada. La
   instantánea guardada en `vocacion_turistica_territorio` —la que escribe
   `VocacionTuristicaTerritorio::registrar()` al confirmar— se queda desfasada
   mientras tanto, pero nadie la lee directamente: `resultadoFinal()` la
   recalcula siempre a partir del FIT y el FET vigentes antes de mostrarla, así
   que al volver a confirmar FIT con un valor distinto, Vocación refleja el
   valor nuevo, no el viejo. No hizo falta decidir nada sobre cascada ni
   bloqueo explícito: la regla ya existente —recalcular en vivo, nunca fiarse
   de una instantánea— ya cubre el caso.
5. El aviso de bloqueo (`x-aviso-bloqueo-matriz`) sigue diciendo la verdad
   después de reabrir: la frase de «validada» desaparece en cuanto la matriz
   deja de estarlo, y el equipo recupera el botón de guardar.

**No se tocó ningún controlador ni vista.** El botón ya existía y el
documento era lo único que mentía; corregirlo —y fijar el comportamiento con
tests permanentes— era todo el trabajo real. Involucrados no se tocó: ya tenía
su propio mecanismo de reapertura (`InvolucradosController::reabrirSiConfirmada()`),
anterior a esta rama y con su propio aviso explícito en el mensaje de éxito,
que esta rama no necesitaba replicar en las ocho matrices de formulario —ahí
el mecanismo es transparente (el propio botón de guardar) en vez de una
advertencia aparte, y ningún test pedía unificarlos—.

### Rama `fit-fet-componentes` — FIT y FET migrados, terminada

Las dos matrices más pequeñas —FIT (18 criterios) y FET (9)— pasaron de
`<select>` a los componentes de tarjeta nuevos. Quedan Percepción y
Potencialidad, que siguen en desplegables; ver el punto 4 de §6 más abajo.

**Antes de tocar nada se comprobó que los componentes encajaban de verdad**,
no se asumió: `criterio-escala` y `criterio-pildoras` colorean por POSICIÓN en
la escala dando por hecho que el valor más alto es el mejor. Se repasaron los
27 nombres de campo de FIT y FET uno a uno —son «presencia/calidad de X»:
recursos, prestadores, infraestructura, imagen, seguridad...— y ninguno está
formulado al revés, así que la dirección es la esperada.

Lo que **no** encajaba de fábrica: los dos componentes están escritos para
exactamente 3 niveles (un array `$estilos`/`$colores` con 3 posiciones fijas),
y FIT/FET usan una escala genérica de **4** (Nulo/Bajo/Medio/Alto, igual para
los 27 criterios, a diferencia de Paisaje o Valoración Territorial, donde cada
criterio describe su propio nivel con texto distinto). Se resolvió añadiendo
una paleta de 4 colores (rojo→naranja→ámbar→verde) elegida por
`count($niveles)` en `criterio-pildoras.blade.php` y en `leyenda-escala.blade.php`,
dejando la paleta de 3 niveles bit a bit igual —verificado con la suite de
Paisaje y Valoración Territorial en verde sin cambios—. `criterio-escala.blade.php`
no se tocó: esta rama no lo usa.

**Las definiciones viven en `App\Matrices\Fit` y `App\Matrices\Fet`**, con la
misma forma que `Paisaje`/`ValoracionTerritorial`: `BLOQUES` (bloque => nombre,
peso, criterios) y `todos()`. El peso de cada bloque se movió ahí también —antes
vivía en una `private const PESOS` aparte de cada controlador— para no dejar dos
listas del mismo número que se pudieran desincronizar.

**La mitad del valor de esta rama, y la que no era solo un cambio visual**: FIT
y FET ya dan la etiqueta del instrumento en sus mensajes de validación, con el
mismo hook `etiquetas()` que las otras siete matrices. Antes de esta rama eran
las dos únicas sin etiqueta —ver más arriba, rama `mensajes-validacion`—, porque
sus nombres de campo solo existían como literales en el Blade. Ahora
`EvaluacionFitController::etiquetas()`/`EvaluacionFetController::etiquetas()`
leen `Fit::todos()`/`Fet::todos()`, igual que Paisaje lee `Paisaje::todos()`: una
sola fuente, sin copiar nada.

**Tres hallazgos de etiquetas que no casaban, reportados y no corregidos en
silencio** —el encargo no pedía tocar `ponderacion.blade.php`, solo el
formulario y el mensaje de error—:

- FET: el formulario dice «Grado de Apertura de la Comunidad» y «Seguridad del
  Destino»; la tabla de ponderación dice «...de la Comunidad **Local**» y
  «Seguridad del Destino **o Sitio de Visita**».
- FIT: el formulario dice «Productos Territoriales»; la ponderación dice
  «Producto Turístico Territorial».
- FET conserva su aviso de formulario bloqueado sin cambios («Evaluación FET
  cerrada.») en vez de unificarlo con `x-aviso-bloqueo-matriz` como ya usa FIT:
  es la deuda que `deudas-registro` dejó anotada a propósito para «el día que se
  toque ese formulario» (ver arriba). Tocar ese aviso no era parte de esta
  tarea —etiquetas y componentes de criterio—, así que se dejó igual.

**Dos tests quedaron rotos por el cambio de `<select>` a `<input type="radio">`,
y se actualizaron en el sitio en vez de dejarlos caer**: los dos
`test_el_admin_recibe_el_formulario_fit/fet_bloqueado_aunque_este_en_borrador`
de `EvaluacionesTest.php` comprobaban el literal `disabled>`, que ya no puede
aparecer con la nueva estructura de atributos; ahora cuentan los radios
deshabilitados (72 en FIT, 36 en FET), que es la misma comprobación que ya pedía
esta tarea para el test nuevo. Y `GuardadoParcialTest::test_un_error_al_validar_no_borra_lo_ya_respondido`
comprobaba `<option value="3" selected>` en el HTML; con Alpine.js la selección
vive en el `x-data`, no en un atributo del HTML servido, así que pasó a leer ese
JSON con el mismo método que ya usa `ValoracionTerritorialTest::estadoInicial()`.
El comportamiento que fijan los tres —el formulario queda bloqueado del todo; una
respuesta no se pierde al fallar la validación— no cambió; cambió cómo se observa
desde fuera. `MensajesValidacionTest::test_sin_etiqueta_el_campo_al_menos_se_ve_legible_y_en_castellano`
se sustituyó por su espejo (`test_fit/fet_nombra_el_campo_con_la_etiqueta_del_instrumento`):
fijaba justo la ausencia de etiqueta que esta rama cierra.

Ningún otro test de FIT o FET se tocó: los de cálculo, guardado parcial,
bloqueo por rol/estado, mensajes de cierre y el resto de `EvaluacionesTest.php`,
`IntegridadDatosTest.php`, `ReabrirMatrizTest.php` y `EstadoZonaTest.php`
siguen verdes sin ninguna edición, porque no cambió qué se guarda ni qué se
valida —solo dónde viven las definiciones y cómo se pintan—.

Suite: **376 tests** (365 en `main` + 10 en `FitTest.php`/`FetTest.php` + 1 neto
en `MensajesValidacionTest.php`, que cambió un test por dos).

### Rama `percepcion-componentes` — Percepción migrada, terminada

La **Matriz de Percepción de la Localidad** (16 criterios en 4 categorías:
Dimensión Social, Percepción Local, Percepción Económica, Nivel de
Organización) pasó de `<select>` a `<x-criterio-pildoras>`, igual que FIT y
FET en `fit-fet-componentes`. Suite: **383 tests**.

**Antes de tocar nada se comprobó la dirección de la escala, criterio por
criterio, no se asumió** —era el punto de parada explícito del encargo—.
Percepción usa una escala de acuerdo Negativo(1)/Neutral(2)/Positivo(3), y al
menos un atributo suena formulado en negativo: NO4, «Presencia de conflictos
entre actores y grupos sociales». Se comprobó contra la hoja original
(`Documentación/IMPLEMENTADA MATRIZ PERCEPCIÓN DE LA LOCALIDAD.xlsx`): la
escala Positivo=3/Neutral=2/Negativo=1 es **única para los 16 atributos**, no
una cantidad por atributo —no pregunta «cuántos conflictos hay», pregunta si
esa situación es favorable o no para la localidad—. Bajo esa lectura, 3 es
siempre el mejor valor posible en los 16, incluido NO4, así que la dirección
es consistente y `<x-criterio-pildoras>` —que colorea por posición asumiendo
que más alto es mejor— encaja sin necesidad de un control propio como el que
sí necesitó Irritación.

**Los componentes no necesitaron ningún cambio.** A diferencia de FIT/FET,
que necesitaron una paleta de 4 niveles nueva, Percepción usa 3 niveles
(Negativo/Neutral/Positivo) y `criterio-pildoras`/`leyenda-escala` ya cubrían
3 desde antes de `fit-fet-componentes` —es la misma paleta que usan Paisaje y
Valoración Territorial—.

**Las etiquetas se movieron a `App\Matrices\Percepcion::$categorias`**, que
antes vivían en `EvaluacionPercepcionController::$categorias`. A diferencia de
FIT/FET, Percepción ya daba su etiqueta en los mensajes de validación desde
antes de esta rama —no era el problema que resolvía `fit-fet-componentes`—;
lo que cambia es solo dónde vive la definición, por la misma razón que llevó a
mover las de FIT/FET: que el formulario la recorra sin tenerla tecleada, y que
el controlador no sea a la vez la fuente y uno de sus consumidores.

**Se mantuvo como propiedad estática mutable, no `const`, a propósito.**
`Fit::BLOQUES`, `Fet::BLOQUES`, `Paisaje::CATEGORIAS` y compañía son todas
`public const`; `Percepcion::$categorias` no. Es la única forma de conservar
`MensajesValidacionTest::test_la_etiqueta_de_percepcion_sale_del_instrumento_y_no_de_una_copia()`,
que muta una etiqueta en caliente y comprueba que el mensaje de error cambia
con ella —PHP no permite reasignar una class constant ni por Reflection—.
Convertirla en `const` habría degradado esa prueba a «leer el valor vigente»,
como las demás matrices, perdiendo la única cobertura de la suite que detecta
una copia en vez de una lectura dinámica. El test se adaptó para apuntar a
`Percepcion::$categorias` en vez de `EvaluacionPercepcionController::$categorias`,
sin cambiar qué prueba ni cómo.

**Un test quedó atado a marcado que dejó de existir, y se hizo más estricto,
no más laxo.** `EvaluacionesTest::test_el_admin_recibe_el_formulario_percepcion_bloqueado_aunque_este_en_borrador`
comprobaba el literal `disabled>`, que ya no aparece con `<input type="radio">`.
A diferencia de FIT/FET, un `substr_count($contenido, 'disabled')` a secas no
bastaba aquí: el formulario de Percepción tiene un `<textarea>` con la clase
Tailwind `disabled:bg-gray-100` —contiene la palabra "disabled" en su nombre
siempre, esté o no bloqueado— y su propio atributo `disabled` condicional, así
que el conteo real daba 50, no 48. Se reemplazó por un conteo que solo cuenta
dentro de `<input type="radio">`: primero que existan 48 (16 criterios × 3
niveles), después que los 48 estén deshabilitados. Es más preciso que el
`substr_count` de FIT/FET, no una versión relajada de la misma prueba.

**Dos tests nuevos, siguiendo el patrón de FIT/FET:**
`PercepcionTest.php` -análogo a `FitTest.php`- fija que el instrumento declara
16 criterios en 4 categorías con pesos que suman 1.0, que los 3 niveles son
genéricos, que el formulario los recorre desde `Percepcion::todos()` -no
tecleados- y que el guardado parcial sigue funcionando tras el cambio de
control. `GuardadoParcialTest::test_un_error_al_validar_no_borra_lo_ya_respondido_en_percepcion`
-análogo al de FIT- confirma que `old()` sigue ganando sobre lo guardado
cuando falta un criterio al confirmar, leyendo el `x-data` de Alpine con el
mismo `estadoInicial()` que ya usaba FIT: Percepción tiene cuatro bloques
`x-data` (uno por categoría) en vez de uno solo, y el helper los funde sin
cambios porque recorre todas las coincidencias de `JSON.parse(...)` de la
página.

`RegistroMatricesTest::test_los_criterios_declarados_coinciden_con_el_instrumento`
se amplió con `'percepcion' => App\Matrices\Percepcion::class`, igual que se
amplió con FIT/FET en la rama anterior.

Ningún test de cálculo, guardado parcial a nivel de base de datos, bloqueo por
rol/estado o mensajes de cierre se tocó: no cambió qué se guarda ni qué se
valida, solo dónde vive la definición y cómo se pinta.

### Rama `potencialidad-componentes` — Potencialidad migrada, cuarta y última, terminada

La **Matriz de Potencialidad Turística** (156 criterios, la más grande del
sistema) pasó de `<select>` a `<x-criterio-pildoras>`, igual que FIT, FET y
Percepción. Suite: **389 tests**.

**Es la migración difícil de las cuatro, y no encajaba en el molde de las
otras tres sin más**: Potencialidad no hereda de `MatrizPonderadaController`
—tiene su propio `EvaluacionZonaController` con `prepararDatos()`,
`calcular()` y `estaCompleta()` propios—, y tiene una funcionalidad que
ninguna otra matriz tiene, la **configuración de campos activos**
(`PotencialidadCamposActivos`): el Jefe elige qué criterios aplican a su zona
y el cálculo se hace solo sobre esos, redistribuyendo pesos en cuatro niveles
de anidamiento. Nada de eso se tocó; la migración fue estrictamente el
control de calificación, campo por campo.

**Antes de tocar nada se comprobaron los tres puntos de parada del
encargo, contra el instrumento original**
(`Documentación/IMPLEMENTADA MATRIZ DE POTENCIALIDAD TURÍSTICA TUR.xlsx`), no
se asumieron:

- **Dirección de la escala.** Las 17 hojas de criterios fijan la misma
  leyenda ("Rojo o Ausencia"=0, "Amarillo o Fragilidad"=1, "Verde o
  Aprovechable"=2) y, revisando las 156 descripciones, ninguna puntúa al
  revés —incluidas las dos que suenan ambiguas a primera vista,
  "Explotaciones Mineras" y "Complejos Industriales" (RC — Expresiones
  Contemporáneas): no miden contaminación, miden si el sitio está abierto y
  acondicionado para recibir visitantes, así que 2 sigue siendo el mejor
  valor—. `<x-criterio-pildoras>`/`<x-leyenda-escala>`, que colorean por
  posición asumiendo que más alto es mejor, encajan sin necesidad de un
  control propio como el que sí necesitó Irritación.
- **Número de niveles.** 3 (Ausencia/Fragilidad/Aprovechable), no 4: la
  paleta que `fit-fet-componentes` añadió no hizo falta. Es la misma paleta
  de 3 que ya usan Paisaje, Valoración Territorial y Percepción, sin tocar
  ningún componente compartido.
- **Una errata del propio instrumento, verificada y descartada.** La hoja
  "TT — Productos Turísticos" marca un 3 en la fila "Turismo Cultural" en vez
  de un 2 —único caso en las 17 hojas—, pero la descripción de esa fila es la
  de "Aprovechable", igual que las demás filas de la misma hoja: es un
  tecleo suelto, no un cuarto nivel real, y el sistema ya validaba 0-2 para
  los 156 por igual desde antes de esta rama.

**La definición vive ahora en `App\Matrices\Potencialidad`**, con
`SECCIONES` (título de sección => [campo => etiqueta]) y `NIVELES`, movida
tal cual desde `EvaluacionPotencialidadController::$secciones`. A
diferencia de `Fit::BLOQUES`, `SECCIONES` **no declara pesos**: el cálculo de
Potencialidad tiene cuatro niveles de anidamiento que ya viven, comentados y
cubiertos por `PotencialidadCalculoTest`, dentro de
`EvaluacionPotencialidadController::calcular()`; forzarlos a una forma
"bloque => peso, criterios" habría significado reescribir ese cálculo para
leer de una estructura nueva, justo el riesgo de comportamiento que esta
migración —de presentación, no de cálculo— tenía que evitar.
`EvaluacionPotencialidadController::$secciones` se conserva como propiedad
estática pública, delegada a `Potencialidad::SECCIONES` (`public static
array $secciones = Potencialidad::SECCIONES;`, una expresión constante válida
en PHP), en vez de sustituir cada referencia interna: `PotencialidadCalculoTest`
y `MensajesValidacionTest` ya la usaban como
`EvaluacionPotencialidadController::$secciones`, y ninguna de las dos tenía
que tocarse por un cambio de presentación.

**Lo que no encajaba, y no se forzó:**

- **El toggle de activar/desactivar un campo ya no deshabilita el control en
  vivo.** El `<select>` anterior se deshabilitaba con Alpine (`:disabled`,
  reactivo); `<x-criterio-pildoras>` deshabilita con `@disabled($bloqueado)`,
  fijado en el servidor al renderizar. Añadir una segunda vía de
  deshabilitado reactivo al componente compartido —usado también por FIT,
  FET y Percepción, con sus propios tests contando `disabled`— no valía el
  riesgo para una garantía que ya está cubierta de otro modo: el toggle sigue
  aplicando opacidad reactiva a toda la fila (`:class="{'pt-inactive': ...}"`,
  sin cambios), y el servidor descarta la calificación de un campo inactivo
  sin importar qué se envíe (`prepararDatos()` conserva el valor guardado en
  vez de leer la petición). Un pildora clicable en un campo inactivo no
  corrompe nada; solo deja de fruncirse sola sin recargar la página.
  Verificado a mano en el navegador: el toggle sigue fundiendo la fila a
  opacidad 0.55 al desactivar un campo.
- **La insignia de solo lectura se sustituyó por píldoras deshabilitadas**,
  siguiendo el mismo patrón que FIT/FET/Percepción, en vez de mantener el
  `<span class="pt-ro-badge">` anterior. Como consecuencia, el único test que
  afirmaba sobre el `<select>` anterior —`EvaluacionesTest::test_el_admin_recibe_
  el_formulario_potencialidad_bloqueado_aunque_este_en_borrador`, que
  comprobaba la AUSENCIA de cualquier `name="rn_agua_lagos"` en modo
  lectura— se volvió falso con el cambio de control: los radios sí llevan
  `name="..."`, deshabilitados. Se sustituyó por una comprobación más
  estricta, no más laxa: cuenta los radios reales (`<input type="radio">`) y
  exige que los 9 de los 3 campos activos estén deshabilitados, evitando el
  error ya documentado de Percepción (`assertSee('disabled', false)` cuenta
  clases `disabled:` de Tailwind que no significan nada deshabilitado).
- **La leyenda de la barra lateral no se sustituyó por `<x-leyenda-escala>`.**
  Potencialidad ya tenía su propia tarjeta "Escala de calificación" en el
  sidebar, con una descripción por nivel ("No existe o es inexistente",
  "Existe pero es débil / incipiente", "Consolidado y funcional") más
  detallada que la leyenda genérica. Sustituirla habría sido peor, no mejor
  presentación; se deja igual.

**Efecto colateral, no buscado pero correcto: se corrigió un "hueco que se
enviaba como cero".** El `<select>` anterior preseleccionaba con
`$val = $evaluacion->$campo ?? 0`: un criterio activo que nadie llegó a
tocar no tenía una opción "sin responder" —el `<select>` solo ofrecía
0/1/2— así que el navegador lo enviaba como `0` ("Ausencia") aunque nunca se
hubiera mirado. `<x-criterio-pildoras>` usa radios sin ninguno marcado por
defecto más un botón "Borrar respuesta", así que un criterio sin tocar viaja
como `null` de verdad. `GuardadoParcialTest::test_un_error_al_validar_no_borra_
lo_ya_respondido_en_potencialidad` fija este comportamiento a través del
formulario real -no de una petición fabricada a mano- y habría fallado
contra la versión anterior del `<select>`.

**Lo que no se tocó, verificado con la suite en verde sin editar ningún
test de comportamiento:** `PotencialidadCalculoTest.php` completo -sus 20
tests, incluidos los dos hallazgos de auditoría que el encargo pedía
proteger explícitamente-:

- **M6** (`test_una_validacion_fallida_no_deja_la_configuracion_de_campos_a_
  medio_cambiar`): el orden validar-antes-de-persistir `campos_activos` en
  `prepararDatos()` no se movió.
- **La distinción hueco/cero** (`test_cero_campos_activos_no_revienta_y_no_
  hay_totales`, que afirma `assertNull` en vez de `assertEqualsWithDelta`):
  el cálculo de `fn_total`/`fx_total` no se tocó.

**Dos tests nuevos, siguiendo el patrón de FIT/FET/Percepción:**
`PotencialidadTest.php` fija que el instrumento declara 156 criterios en sus
secciones, que los 3 niveles son genéricos, que el formulario del Jefe los
recorre desde `Potencialidad::SECCIONES` -no tecleados- contando los 468
radios reales (156×3), y que el equipo solo ve píldoras de los campos
activos (contando 15 radios para un subconjunto de 5 campos activos, ni uno
más). `RegistroMatricesTest::test_los_criterios_declarados_coinciden_con_el_
instrumento` se amplió con `'potencialidad' => App\Matrices\Potencialidad::class`,
cerrando el último hueco de ese guardián -las nueve matrices con `todos()`
quedan verificadas contra el registro-.

Ningún test de cálculo, guardado parcial a nivel de base de datos, bloqueo
por rol/estado o mensajes de cierre se tocó, salvo el de la insignia de
solo lectura ya descrito: no cambió qué se guarda ni qué se valida, solo
dónde vive la definición y cómo se pinta.

### Rama `cabos-sueltos` — cinco cabos pequeños, terminada

Cinco correcciones independientes de `main`, cada una en su propio commit
para que cualquiera se pueda revertir sola. Suite: **394 tests**.

1. **El README decía «cinco matrices».** Son nueve, y de paso contaba
   Inventarios como una de ellas —no lo es: `Registro::ENTRADAS` la declara
   tipo `inventario`, no `matriz`/`actores`—. Corregido el recuento y la
   lista de nombres. El resto del fichero —tabla de roles, credenciales de
   prueba, variables de Render, ficheros que enlaza— se repasó contra este
   documento y sigue siendo cierto, así que no se tocó.

2. **FET no distinguía su motivo de bloqueo.** «Evaluación FET cerrada.» no
   mentía, pero cubría dos causas —matriz validada, o rol de consulta— con
   la misma frase. Ahora usa `$bloqueadoPorRol` + `x-aviso-bloqueo-matriz`,
   igual que FIT, Percepción, Irritación y Concentración (ver §3,
   `deudas-registro`). Paisaje y Valoración Territorial se revisaron de
   paso: cuando el bloqueo es por rol siguen sin pintar ningún aviso
   (`@unless($bloqueado)` sin rama `@else`); no dicen nada falso, así que
   quedan fuera de esta corrección a propósito.

3. **`generar_valoracion_territorial.py` escribía con CRLF en Windows.**
   Le faltaba `newline="\n"` en el `write_text()` final —`generar_concentracion.py`,
   más reciente, ya lo tenía—. Arreglado el script. No se regeneró el `.php`
   dando por hecho que haría falta: `.gitattributes` fija `eol=lf` para todo
   el repositorio, así que regenerar con el generador roto (CRLF) o con el
   ya arreglado (LF) produce, tras `git add`, el mismo blob que ya está en
   HEAD —comprobado con `git hash-object`, no supuesto—. El arreglo es
   higiene del script para quien lo ejecute fuera del alcance de git (o si
   el `.gitattributes` cambiara el día de mañana), no la corrección de un
   fichero versionado que estuviera mal.

4. **`select-0-2`, `select-0-3` y `select-percepcion` ya no los usaba
   ninguna vista.** Los reemplazó `<x-criterio-pildoras>` en
   `fit-fet-componentes`, `percepcion-componentes` y `potencialidad-componentes`.
   Borrados los tres, tras confirmar que ningún `<x-...>` los invocaba y que
   `select-irritacion`/`select-involucrados` no dependían de ellos —solo los
   mencionaban en un comentario, actualizado para no dejar una referencia
   colgante a un fichero borrado—.
   `GuardadoParcialTest::test_los_desplegables_distinguen_el_hueco_del_cero`
   los renderizaba para proteger la distinción hueco/cero; esa protección
   para FIT, Potencialidad y Percepción ya vivía, aparte, en pruebas de ida
   y vuelta por HTTP que leen el `x-data` de Alpine
   (`test_un_error_al_validar_no_borra_lo_ya_respondido*`). FET no tenía una
   propia —su única cobertura era la genérica de `select-0-3`, compartida
   con FIT y nunca específica de ninguna de las dos— así que se añadió
   `test_un_error_al_validar_no_borra_lo_ya_respondido_en_fet` antes de
   borrar el componente que la sostenía.

5. **Dos decisiones de `potencialidad-componentes` (§3), revisadas por
   pedido del responsable del proyecto:**

   - **(a) El toggle de campos activos ahora deshabilita el control en
     vivo.** `<x-criterio-pildoras>` gana un prop opcional `:activo-expr`
     (default `null`), aditivo de verdad: sin él —Paisaje, FIT, FET y
     Percepción, que nunca lo pasan— el HTML no cambia ni un atributo.
     `PotencialidadTest::test_sin_activo_expr_el_componente_no_cambia` lo
     fija sobre el componente solo, para que la garantía cubra a cualquier
     consumidor futuro que tampoco lo pase. Solo Potencialidad lo usa,
     pasando `states['campo']` —la misma variable Alpine que ya gobierna el
     toggle— como `x-bind:disabled` en el radio y en el botón «Borrar
     respuesta». Verificado en el navegador con Alpine y DevTools, no solo
     leído: al desactivar un campo sus 3 radios pasan a `disabled=true` de
     verdad y un clic sobre ellos no cambia la selección; al reactivarlo,
     vuelven a responder. Nota de alcance encontrada al mirar: el
     componente lo usan **cinco** matrices, no cuatro —Paisaje también,
     desde antes de esta migración—; las cinco se verificaron con sus
     suites en verde sin tocarlas.
   - **(b) La tarjeta de escala propia de Potencialidad se queda, no se
     sustituye por `<x-leyenda-escala>`.** Comparadas las dos: la
     compartida solo pinta número + etiqueta por nivel; la propia de
     Potencialidad añade una descripción («No existe o es inexistente»,
     «Existe pero es débil / incipiente», «Consolidado y funcional») que la
     compartida no tiene dónde mostrar. Sustituirla perdería información,
     así que se deja igual —tal como pedía el encargo si se confirmaba la
     pérdida—.

Ficheros: `README.md`; `resources/views/operativo/evaluacion_fet/form.blade.php`;
`database/matrices/generar_valoracion_territorial.py`;
`resources/views/components/select-0-2.blade.php`,
`select-0-3.blade.php`, `select-percepcion.blade.php` (borrados),
`select-involucrados.blade.php`, `select-irritacion.blade.php`,
`criterio-pildoras.blade.php`;
`resources/views/operativo/evaluacion_potencialidad/form.blade.php`;
`tests/Feature/EvaluacionesTest.php`, `GuardadoParcialTest.php`, `PotencialidadTest.php`.

## 4. Lo que hay que saber para continuar

### GP3 ya está revisado

El commit `aa80348` se había encontrado ya implementado y sin commitear en el
árbol, de una ejecución anterior del plan, y no pasó la revisión que sí tuvieron
GP1 y GP2. Revisado en `9a57d12`: la implementación es correcta —verificado
además que las 37 columnas calculadas de las cinco matrices ponderadas admiten
`null` en el esquema, que es lo que escribe `columnasCalculadasVacias()`— y solo
le faltaba cobertura por dos caminos, que ese commit añade.

Lo que hace: `prepararDatos()` recibe el estado destino y `update()` lo calcula
antes de validar. En borrador los criterios son `nullable`; al confirmar,
`required`. Si falta alguno, los totales quedan en `null` en vez de promediar
sobre los respondidos, y se vuelve al formulario en vez de a unos resultados
vacíos.

### PostgreSQL — verificado

Las tres migraciones (`2026_08_08_000001`, `000002` y `000003`) se probaron
contra un **PostgreSQL 16.14** real, en un contenedor desechable
(`docker run --rm`, borrado por nombre; los contenedores ajenos de la máquina no
se tocaron).

Se reprodujo el escenario de producción, no un `migrate:fresh`: primero todas
las migraciones **menos** esas tres, luego los seeders y una evaluación por
matriz, y solo entonces las tres. Alterar tablas vacías no habría probado nada.

Resultado: las tres corren sin error y el esquema queda como debía —254 columnas
de criterio en `smallint`, 60 calculadas en `numeric`, todas nullable y sin
defecto, y las filas que ya existían intactas en las seis tablas—. Los criterios
por tabla cuadran con lo que declara el registro.

Se comprobó también la única discrepancia de tipos que había: Percepción creó
sus 16 criterios como `unsignedTinyInteger` y la migración los redeclara como
`tinyInteger`. En Postgres da igual —`PostgresGrammar::typeTinyInteger()`
delega en `typeSmallInteger()` y no existe `modifyUnsigned`, así que ambos son
`smallint`—. En MySQL sí habría quitado el `UNSIGNED`, pero aquí no se usa.

### La suite sobre PostgreSQL — RESUELTO (rama `aislamiento-postgres`)

Lo que antes quedaba como asunto abierto ("ejecutar la suite contra Postgres da
37 errores y 89 fallos, pero cada test pasa en aislado") tenía una causa raíz
concreta, ya arreglada.

`User::ROL_ADMIN`/`ROL_JEFE`/`ROL_EQUIPO` son constantes con id fijo (1, 2, 3)
y `esAdmin()`/`esJefe()`/`esEquipo()`/`tieneRolOperativo()` comparan `role_id`
contra ellas. `SystemSeeder::sembrarRoles()` insertaba los tres roles por
nombre, **sin fijar el id**, y casi todos los tests siembran con
`RefreshDatabase` + `$this->seed(SystemSeeder::class)`.

En PostgreSQL **las secuencias no se revierten con la transacción**:
`RefreshDatabase` limpia los datos entre tests, pero la secuencia de `roles`
sigue avanzando. El primer test sembraba los roles con ids 1, 2 y 3; el
segundo, con 4, 5 y 6; el tercero, con 7, 8 y 9. Desde el segundo test
`role_id` no coincidía con ninguna constante, `esAdmin()` y compañía devolvían
`false` para todo el mundo, y el middleware cortaba la petición antes de
escribir — de ahí que no quedara ningún error SQL en el log. Con SQLite no
pasaba porque, sin `AUTOINCREMENT`, el rowid de una tabla vacía vuelve a
empezar en 1 tras el rollback.

Efecto secundario que costó ver: en una tanda de `RedireccionDashboardTest`,
los tests que comprobaban que jefe y equipo caen en el dashboard operativo
**pasaban**, pero por el camino equivocado — sin rol, todo el mundo cae en el
`else` de esa redirección.

**Arreglo:** `sembrarRoles()` ahora fija el id explícitamente para cada rol al
crearlo (las constantes de `User` son ciertas por construcción, no por orden
de inserción) y solo actualiza la descripción si el rol ya existe, para no
reasignar ids en producción. En Postgres, además, sincroniza la secuencia de
`roles` con `setval()` tras sembrar, por si algún día algo más inserta ahí sin
id explícito.

**Medido antes/después contra el mismo PostgreSQL 16 desechable** (contenedor
`docker run --rm`, borrado por nombre al terminar): antes del arreglo, 295
tests dieron 46 errores y 129 fallos; después, **295 tests, 0 errores, 0
fallos**, dos ejecuciones seguidas. La cifra exacta de antes no coincide con la
de la revisión anterior (37/89) porque es un bug de arrastre acumulado por
orden de ejecución — varía de una corrida a otra — pero la causa raíz es la
misma y queda cerrada por construcción, no por casualidad de orden.

SQLite sigue en 295 tests verdes, sin cambios.

Para reproducirlo, en una sola invocación (XAMPP trae las DLL de pgsql
comentadas, y `php artisan test` lanza PHPUnit en otro proceso al que no viajan
los `-d`):

```bash
php -d extension=pdo_pgsql -d extension=pgsql vendor/phpunit/phpunit/phpunit
```

**Vigilar el despliegue en Render** cuando esta rama llegue a producción, o
verificarlo antes en una máquina que sí tenga Postgres:

```bash
php artisan migrate:fresh --database=pgsql --force
```

### Un fallo del plan que ya se corrigió, por si reaparece

El plan de guardado parcial traía un `esCriterio()` que filtraba columnas por
`str_starts_with($col, 'ds_')`. Los criterios de Percepción llevan dígito
—`ds1_posicion_turistica`, `pl3_conoc_motivo_visita`, `no4_conflictos_sociales`—
así que capturaba **0 de 16 columnas** y la migración habría dejado esa matriz
fuera en silencio. Corregido en `c6acd7c` con una regex de dígito opcional.

Lección aplicable a lo que queda: **los nombres de columna no siguen un único
patrón entre matrices.** Verificar conteos contra el esquema real antes de
aplicar cualquier filtro por prefijo.

### El fallo que arregló GP5, y la regla que dejó

Era la razón de ser de todo el plan. La Matriz de Potencialidad tiene 156
criterios; su validación no exigía ninguno, el formulario preseleccionaba
«0 - Nulo» en todos, y `prepararDatos()` metía 0 en los ausentes. El promedio
los contaba porque miraba `isset()`, y un 0 está *set*. Un criterio que nadie
miró bajaba la media de su grupo, en silencio.

Arreglado en `0e43326`. La regla que dejó, y que conviene respetar en las
matrices que vengan: **un hueco no entra en ningún promedio, y tampoco en
ningún total**. Se aplica en las tres capas del cálculo —criterio, grupo y
total ponderado—, porque un total al que le falta un sumando miente igual que
una media a la que le falta un criterio. Descontar el factor que falta daría un
número que se lee como el resultado del instrumento y está medido sobre otra
escala.

Ojo con una distinción que es fácil de romper: en `calcular()`, `hasCampos()`
significa «tiene campos activos», **no** «tiene respuestas». Un grupo activo que
nadie respondió no debe caerse de la ponderación como si no existiera; tiene que
dejar el total sin calcular.

`tests/Feature/PotencialidadCalculoTest.php` sigue siendo la red: sus diez tests
de caracterización congelan la redistribución de pesos —120 líneas con cuatro
niveles de anidamiento— y ninguno se movió con el cambio.

## 5. Documentos de referencia

| Documento | Qué contiene |
|---|---|
| `docs/superpowers/specs/2026-08-07-rediseno-interfaz-roles-design.md` | Diseño del rediseño: decisiones, arquitectura, lenguaje visual |
| `docs/superpowers/plans/2026-08-07-pagina-de-zona.md` | Plan ejecutado y fusionado |
| `docs/superpowers/plans/2026-08-07-guardado-parcial.md` | Plan ejecutado entero. Lo que se apartó de él está arriba |
| `docs/superpowers/plans/2026-08-07-vistas-admin.md` | Plan ejecutado entero, en `main` |
| `docs/superpowers/specs/2026-08-07-indice-irritacion-turistica-design.md` | Diseño de la séptima matriz |
| `docs/superpowers/plans/2026-08-07-indice-irritacion-turistica.md` | Su plan, ejecutado en la rama `irritacion-turistica` |
| `AUDITORIA.md` | Auditoría de seguridad y calidad, con lo ya corregido marcado |

## 6. Lo que queda, por orden

### En código

1. ~~**Fusionar `guardado-parcial`**~~ — hecho hace tiempo, y sus migraciones
   probadas contra PostgreSQL 16 real. Desde entonces la suite entera corre
   contra Postgres; ver §4.
2. ~~**Vistas de admin**~~ — hecho. El panel da cifras y avisa de las zonas sin
   jefe, y las dos listas tienen buscador, filtro por rol y las columnas que
   faltaban. Suite: **222 tests**. Quedan fuera, a propósito, los formularios
   de alta y edición (`users/form`, `lugares/form`, `zonas/form`): comparten
   los defectos de tipografía pero se usan de forma puntual.
3. **Una matriz sin implementar**: el **Índice Espacial de Frecuentación**, la
   última. Su fichero está en `Documentación/Índice Espacial de Frecuentación
   Turística.xlsx` —**dentro del repositorio**, desde hoy: antes vivía solo en
   `~/Downloads` de una máquina, así que en cualquier otra la única matriz
   pendiente no se podía ni abrir—. Es una lista variable de sitios con dos cifras cada uno y el índice
   es la suma de sus cocientes, así que se parecería a Involucrados más que a un
   formulario de criterios. **Está bloqueada por algo que no es técnico:** su
   hoja original divide todas las filas por el ST de la *primera*, y tiene una
   celda «Superficie Territorial = 1» que no usa ninguna fórmula. La hoja se
   contradice a sí misma; **hay que aclarar la fórmula con el autor del
   instrumento antes de implementarla**, y ninguna interpretación que elijamos
   por nuestra cuenta sería defendible.

   El **Índice de Concentración** ya está hecho —ver la rama `concentracion`—.
   El solapamiento con el módulo de Inventario, que era la duda que lo tenía
   parado, se resolvió a propósito **no** derivando uno del otro; el motivo está
   arriba y en el diseño.
4. ~~**Migrar Percepción y Potencialidad**~~ — hecho. Percepción en la rama
   `percepcion-componentes`, Potencialidad en `potencialidad-componentes`
   (§3): 3 niveles, sin cuarto nivel real ni escala invertida en los 156
   criterios, verificado contra el instrumento original antes de asumirlo.
   No heredaba de `MatrizPonderadaController` y tenía la configuración de
   campos activos, que se quedó intacta -solo cambió el control de
   calificación-.
5. ~~**«Reabrir» una matriz validada**~~ — resuelto en `reabrir-matriz`: no
   estaba «en el diseño, sin implementar» como decía esta lista, ya
   funcionaba end-to-end en las ocho matrices de formulario. Ver esa rama en
   §3. Queda de verdad pendiente, si alguien lo echa de menos, hacer el gesto
   más *descubrible* —hoy reabrir es un efecto secundario silencioso de
   «Guardar Borrador», sin el aviso explícito que sí tiene Involucrados
   («... vuelve a borrador: hay que validarla de nuevo»)—, pero ningún test
   de esta rama lo exigía y no se inventó ese trabajo.

### Fuera de código, en Render

Ninguna depende de programar, y las tres están vivas:

- **Rotar la contraseña del admin.** `admin@turismo.com` / `password` sigue
  funcionando en producción.
- **Rellenar el SMTP.** Sin él, recuperar contraseña no envía nada.
- **Crear el bucket S3** y poner `AWS_*` y `FILESYSTEM_DISK=s3`. El código está
  hecho y verificado con MinIO; hasta configurarlo, **las fotos se pierden en
  cada redespliegue**.

## 7. Detalle suelto

`package-lock.json` aparece modificado en el árbol y **no está commiteado a
propósito**. Se regeneró al instalar en Windows; el `npm ci` de la imagen de
producción usa el generado en Linux. La recomendación es revertirlo:

```bash
git checkout -- package-lock.json
```

## 8. Primer comando en la máquina nueva

```bash
git clone https://github.com/Jhampier-u/proyecto-turismo.git
```

Después: `composer install`, `npm install`, `cp .env.example .env`,
`php artisan key:generate`, `php artisan migrate --seed`, `npm run build`.

La base es SQLite y el fichero **no viaja en el repositorio**: hay que crear
`database/database.sqlite` vacío antes del `migrate`, o Laravel no encuentra la
conexión.

**No hay ninguna rama que retomar: todo está fusionado en `main`.** Esta sección
decía durante meses «`git checkout guardado-parcial`, comprueba que da 209
tests», y era la instrucción más dañina del documento — quien la siguiera se
llevaría una rama antigua y creería que el clon salió mal.

Comprobar que la suite da **394 tests** antes de tocar nada. Si da menos, algo
no llegó; si fallan unos 57 de golpe, faltó el `npm run build` (ver §2).

```bash
php artisan test
```

**Este número hay que actualizarlo cada vez que se fusione algo**, o vuelve a
mentir como acaba de hacerlo. Es el mismo tipo de deriva que esta sesión
encontró cuatro veces en el propio código: documentación que se quedó atrás sin
que nada fallara.
