# Estado del proyecto — traspaso entre máquinas

**Fecha:** 13 de agosto de 2026 (actualizado al cerrar la Fase 2 del rediseño)
**Para:** continuar en otro ordenador sin perder contexto.

**Estado en una línea:** `main` subida a `origin`, árbol limpio, **632 tests
verdes** comprobados el 13 de agosto. No hay ninguna rama que retomar; sí hay
una deuda de revisión, el punto 17 de §6.

El último commit que toca **código** es `d0e03a2`, y es sobre él donde se
corrieron esos 632. Lo que haya encima en `main` son commits de este documento:
si el árbol está limpio y `git log --oneline -1` enseña un `docs(traspaso)`, la
cifra sigue valiendo sin volver a correr nada.

*(La cabecera decía «10 de agosto, al terminar `cabos-sueltos`» cuando el
documento ya contaba `permisos-y-navegacion`, `frecuentacion` y tres fases del
rediseño. La misma deriva que §8 lleva anotada para el recuento de tests: se
actualiza el cuerpo y se olvida la fecha.)*

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

### Rama `permisos-y-navegacion` — terminada, 11 de agosto

Cuatro cambios, 14 commits, suite de **394 → 444 tests**.

**1. El admin pasa de solo lectura a poder escribir.** Rellena formularios,
guarda borradores y gestiona inventario y actores en cualquier zona. **Lo único
que no puede es validar**, exclusivo del jefe, y tampoco editar una matriz ya
validada. `PerteneceAZona` dejó de restringirle: las guardas de rol viven ahora
**en los controladores**.

> **Si añades una ruta de escritura al grupo `operativo/zona`, será accesible al
> admin por omisión.** Si esa acción debe ser solo del jefe, guárdala en su
> controlador. `PermisosAdminTest::test_toda_ruta_de_escritura_esta_clasificada`
> recorre las rutas con lista blanca explícita y falla si aparece una sin
> clasificar. Ese test existe porque su versión anterior filtraba por el literal
> «validar» y dejó pasar un agujero real durante toda la rama.

`User::puedeEditarEvaluaciones()` **ya no existe**: hacía dos trabajos bajo un
nombre —si puedes editar, y a dónde vuelve el botón «Volver»—. El primero dejó
de aplicar; el segundo vive en `<x-boton-volver>`.

**2. Pestañas entre formulario y resultados** en los nueve instrumentos, vía
`<x-pestanas-matriz>`, alimentadas por `Registro::ENTRADAS`. Con la matriz
incompleta, «Resultados» no es un botón gris: es texto con candado y el recuento
de lo que falta. **El estado manda sobre el recuento**: una matriz validada
tiene resultados aunque no estén todos los criterios respondidos —Potencialidad
permite desactivar campos—.

**3. Conmutador lista/tarjetas** en las dos vistas de zonas, vía
`<x-conmutador-vista>`, extraído del que ya tenía Inventario. Zonas usa la clave
`zonas_vista`; Inventario conserva `inventario_vista`.

**4. Aviso de reapertura** (`<x-aviso-reapertura>`) y tipografía de los tres
formularios de administración.

**Lo que costó más de encontrar, por si vuelve a pasar:** la revisión final
descubrió que `EstadoZona` seguía dando `url: null` al admin, así que desde el
panel de zona —su única puerta de entrada— no tenía enlace a ningún formulario.
El plan cubrió las diecinueve vistas de instrumento y se olvidó del concentrador
que enlaza a todas. La funcionalidad estrella de la rama era inalcanzable desde
la interfaz mientras todas las revisiones por tarea daban verde.

**Cinco menores aplazados a propósito**, ninguno bloqueante. **Dos de los cinco
están cerrados desde el 13 de agosto de 2026** y quedan tachados aquí; los otros
tres siguen en pie con su motivo:

- ~~Seis formularios conservan `url()->previous()` en su botón «Regresar»; solo
  Paisaje usa `<x-boton-volver>`.~~ — **ya no existe en el código**: no queda un
  solo `url()->previous()` ni en `resources/views` ni en `app/`. Lo cerró la
  conversión de botones de `fundacion-visual`, que pasó los «Regresar» a
  `<x-boton-volver>`, pero **nadie tachó la entrada**. Era el que esta lista
  daba como «el que más merece cerrarse», y llevaba ramas hecho.
- `<x-pestanas-matriz>` consulta la base desde la vista Blade y duplica la carga
  que el controlador ya hizo. Arreglarlo bien exige pasar la evaluación por prop
  y tocar las 18 vistas.
- `EstadoZona` usa `self::` donde podría usar `static::`. En una `final class`
  da igual.
- `vtt/resultado.blade.php` no tiene test del texto ni el color de su botón.
- ~~`involucrados/index` enseña el aviso de reapertura también a quien no puede
  provocarla.~~ — **arreglado, y estaba en dos pantallas, no en una.** El
  párrafo «si la modificas, vuelve a borrador» del banner verde no miraba
  `$puedeEditar`, que con la lista validada solo es cierto para el jefe: al
  admin y al equipo se les anunciaba la consecuencia de una acción que
  `bloqueoSiCerrada()` les impide, y cuyos botones ni siquiera se les pintan.
  **`frecuentacion/index` tenía el mismo párrafo con el mismo hueco** —son las
  dos pantallas que comparten esta forma de banner— y esta lista solo nombraba
  una. Cuatro tests, dos por pantalla, cada negativo con su contraparte
  positiva para que borrar el aviso a todo el mundo no pase por arreglo.

**Sin verificación visual.** Todo lo comprobado es marcado y comportamiento. El
conmutador es Alpine puro: que el botón conmute y la preferencia sobreviva a una
recarga no lo cubre ningún test.

### Rama `frecuentacion` — décima y última matriz, terminada

Seis tareas, suite de **479 → 480 tests**. Con esta rama **las diez matrices del
sistema quedan implementadas** — el punto 3 de §6 ya no queda pendiente.

Añade el **Índice Espacial de Frecuentación Turística**: reparte la
frecuentación de una zona entre sus sitios, `ÍETP = DET ÷ ST` por sitio,
`ÍEFT = Σ ÍETP` para el territorio. La ST (Superficie Territorial) es **una
por zona, no por sitio**, y vive en `frecuentacion_config` junto al estado del
conjunto —el mismo sitio donde `InvolucradosConfig` guarda el estado, con una
columna de más—.

**Un tipo de entrada nuevo, `sitios`, no una reutilización de `actores`.**
`EstadoZona::filaActores()` y la rama `elseif($entrada['tipo'] === 'actores')`
de `pestanas-matriz.blade.php` tienen la relación `$zona->involucrados()`
escrita a mano; reutilizar `actores` habría hecho que la fila y la pestaña de
Frecuentación contaran actores de Involucrados, no sitios propios. `sitios` es
su hermano, con una condición de completitud a dos partes que `actores` no
tiene: la lista de sitios, **y** la ST.

**ST nula o cero → ningún ÍETP existe, para ningún sitio de la zona** —nunca un
`DivisionByZeroError` ni un número inventado—: `Frecuentacion::ietp()` devuelve
`null`, y la condición se resuelve **una sola vez, a nivel de página**
(`<x-matriz-sin-resultados>`), no fila por fila como el `Pi` de Concentración:
con la ST compartida por todos los sitios, si falta o es cero, ningún sitio
tiene ÍETP, no solo uno.

**Un bug real que la propia rama encontró y corrigió antes de esta revisión:**
`EstadoZona::filaSitios()` nació (Tarea 1) con el orden invertido respecto a su
hermana `filaActores()` —comprobaba `cuantos === 0` antes que `validada`—. Una
configuración confirmada con sitios habría aparecido como "sin empezar" en la
página de zona, contradiciendo a `validadas()` y `progresoDe()`, que ya la
cuentan como hecha. Se corrigió en el commit `bf73894` (Tarea 3), antes de que
llegara a verse en ninguna vista real —hasta entonces la entrada no existía de
verdad—. Esta revisión final añadió el test que faltaba para sujetar el orden
correcto: `EstadoZonaTest::test_una_entrada_de_sitios_confirmada_con_cero_sitios_es_validada`.

**Un hallazgo aparte, en un fichero que no es de esta rama.** Investigando un
reporte de fragilidad en `ConmutadorVistaTest` (de `permisos-y-navegacion`) se
reprodujo, con semilla y en dos corridas independientes de la suite en orden
aleatorio, que `test_el_admin_ve_jefe_y_miembros_en_las_dos_maquetaciones`
falla de verdad cuando el nombre que genera Faker (`locale` `en_US`, sin
configurar) lleva apóstrofe —`O'Kon`, `O'Keefe`...—: la vista lo pinta con
`{{ }}`, que lo escapa a `&#039;`, y el test comparaba con `substr_count()`
contra el nombre sin escapar. Mismo patrón que ya dejó anotado este documento
para los correos de Faker, aplicado esta vez a un nombre. Corregido comparando
contra `e($this->jefe->name)`. **No se confirmó una causa concreta para el test
que sí se reportó**, `ConmutadorVistaTest::test_inventario_conserva_su_propia_preferencia`
—no depende de ningún dato de Faker—: cientos de corridas en orden aleatorio no
lo reprodujeron. Ver el detalle en el informe de esta revisión.

**La migración contra PostgreSQL real y la suite completa contra Postgres —
pedidas por la Tarea 6— no se pudieron ejecutar en esta máquina**: Docker
Desktop no consigue levantar su backend (WSL2 se queda en `Stopped`, y el
servicio `com.docker.service` está detenido sin permisos para arrancarlo desde
esta sesión). Queda pendiente para quien tenga Docker operativo:
`docker run --rm -d --name frecuentacion-pg -e POSTGRES_PASSWORD=... -p 5433:5432 postgres:16`,
migrar, y comprobar que `st`/`det` quedan `numeric` nullable sin defecto y que
los modelos los devuelven como `float`. El recorrido manual con
`jefe@local.test` / `password` sobre SQLite sí se hizo completo —tres sitios,
un DET a medias, ST en 0 rechazada, ST válida, validar, resultados con ÍETP e
ÍEFT correctos, y la reapertura al editar un sitio ya validado— y se comportó
exactamente como describe el diseño.

### Rama `barra-lateral` — dashboard con panel de "siguiente paso" (Parte A) y barra lateral fija en ocho formularios de matriz (Parte B) — las dos fusionadas

> **Al día 12 de agosto de 2026:** esta sección se escribió con la Parte B aún
> sin fusionar y decía «en esta rama, sin fusionar» en el título. Ya no es
> cierto: las dos partes están en `main` (merge `d65604b`), y §8 lo daba por
> hecho mientras este título decía lo contrario. Corregido aquí. El resto de la
> sección describe el trabajo, que no ha cambiado.

Dos mejoras de interfaz independientes, ver el diseño
(`docs/superpowers/specs/2026-08-12-dashboard-y-formularios-design.md`) y su
plan. **Parte A ya está en `main`** —fusionada por el commit de merge
`4116787`, que este documento no había recogido hasta ahora—. **Parte B vive
solo en esta rama**, con sus nueve commits encima de esa fusión; no se ha
fusionado ni empujado.

Suite de esta rama: **524 tests**. El desglose, porque este documento lleva
meses insistiendo en no dejar un número suelto sin explicar de dónde sale:
483 antes de esta iniciativa (el número que este documento seguía dando,
desahogado) + 11 de la Parte A (ya en `main`, que hoy tiene **494**, no 483 —
la misma deriva documental que esta sección corrige de paso) + 30 de la
Parte B.

**Parte A — el dashboard deja de ser un índice.** `/mis-zonas` abre con un
panel de "lo siguiente que toca hacer": la última matriz que el usuario tocó
(`EstadoZona::ultimaTocadaPor()`, por `user_id`/`updated_at` sobre las ocho
matrices de tipo `matriz`) y la primera sin terminar
(`EstadoZona::siguientePendiente()`), fusionadas en una sola tarjeta cuando
coinciden. Reutiliza `<x-fila-matriz>`, que ahora acepta una prop `zona`
opcional para pintarse fuera de la página de una sola zona sin cambiar su
aspecto donde no se pasa. Sin actividad ni zonas asignadas, no se pinta
ningún panel —un "continuar" vacío sería peor que nada—. El admin no lo ve:
sigue redirigido a su propio dashboard, sin tocar.

Limitación conocida, documentada en el plan y dejada así a propósito: para
Involucrados y Frecuentación, "última tocada" no es fiable —sus filas de
detalle (`Involucrado`, `SitioFrecuentacion`) no tienen `user_id`, y la
configuración solo lo fija al validar o reabrir, no al editar en borrador—.
Se suma a las demás deudas de atribución que ya arrastra el registro; cerrarla
de verdad son dos migraciones + dos controladores + tests, fuera de este plan.

**Parte B — barra lateral fija en ocho formularios de matriz.** FIT, FET,
Paisaje, Valoración Territorial, Percepción, Irritación y Concentración
ensanchan a `max-w-7xl` (los que no lo estaban ya) y ganan
`<x-barra-lateral-formulario>`: recuento de cabecera, índice de bloques con
`✓`/fracción/color, y "Guardar Borrador" siempre a la vista, ligado al
formulario real vía `form="..."`. Oculta por debajo de `lg` (1024px): el
formulario vuelve a una sola columna, sin ningún cambio de comportamiento —
verificado en el navegador, no solo leído, ver más abajo.

El componente no deriva su índice de bloques —las diez matrices no comparten
una forma para ellos (`criterios` en FIT/FET/Paisaje, `items` en Percepción,
lista plana sin `array_keys()` en Irritación, dos niveles sin envoltorio en
Concentración, mapas planos sin agrupador en Valoración Territorial)—: cada
vista construye su propio array normalizado (`ancla`/`etiqueta`/`respondidos`/
`total`) y el componente solo lo pinta, mismo reparto de trabajo que ya usa
`<x-criterio-pildoras>`. El total de cabecera sí se deriva por defecto de
`Registro`/`EstadoZona::criteriosRespondidos()` —como `<x-pestanas-matriz>`—,
salvo que la vista pase `:total`/`:respondidos` explícitos (ver Potencialidad).

**Potencialidad entra también (Tarea 11 bis), migrando su barra propia.** Ya
tenía una barra lateral fija equivalente —`.pt-sidebar`, CSS en línea propio
con su propia media query a 900px—, y dejarla fuera habría dejado dos barras
distintas haciendo lo mismo. Se sustituyó por el componente compartido y se
borró el CSS entero de layout/sidebar (`.pt-layout`, `.pt-sidebar`, `.pt-card`,
`.pt-card-title`, `.pt-scale-row`, `.pt-scale-badge`, `.pt-nav-item`,
`.pt-nav-label`, `.pt-nav-sub` y la media query) — verificado en el navegador
que no queda ningún resto de esas clases en el DOM. El componente gana dos
props opcionales, `:total`/`:respondidos` —solo para este caso—, que
sustituyen el cálculo de cabecera por defecto: el Jefe de Zona elige qué
criterios aplican (`PotencialidadCamposActivos`), así que 156 deja de ser un
denominador honesto en cuanto se desactiva un solo campo. Se exige que los dos
props lleguen juntos o ninguno —nunca uno solo, para no mezclar un numerador
calculado con un denominador ajeno—, y revienta con una excepción clara si no,
en vez de adivinar. Un área que el Jefe deja sin ningún campo activo se omite
del índice en vez de fingir un "0 de 0 ✓".

**El hallazgo de la propia Tarea 11 bis, y lo que esta revisión final
corrigió.** `Str::between($html, '<aside', '</aside>')` —el patrón con el que
las ocho suites acotan sus aserciones al `<aside>` de la barra— devuelve la
cadena COMPLETA cuando el delimitador no existe, **no** una cadena vacía: si
la barra desapareciera de una vista, `assertNotEmpty($fragmento)` seguiría en
verde, porque `$fragmento` sería la página entera. Comprobado de verdad, no
por lectura: se quitó el `<aside>` de las ocho vistas, una por una, y se
volvió a correr cada suite. Resultado — siete de las ocho fallaron de todos
modos, pero por una coincidencia distinta cada vez (otra aserción de la misma
prueba, más específica, encontraba el hueco antes de necesitar el
`assertNotEmpty`); la octava no:
`PotencialidadTest::test_la_barra_lateral_no_ofrece_guardar_si_la_evaluacion_esta_confirmada_y_no_es_el_jefe`
**pasó igual sin ninguna barra**, porque una matriz confirmada vista por el
equipo tampoco pinta "Guardar Borrador" en el CUERPO del formulario —la misma
variable `$bloqueado` esconde las dos copias del botón—, así que la ausencia
era cierta con o sin barra: un test en verde por la razón equivocada, el mismo
patrón que esta serie de ramas lleva meses encontrando. Corregido en los ocho
ficheros de test: cada extracción del fragmento ahora empieza con
`assertStringContainsString('<aside', $html, ...)` sobre la página completa
—que sí falla si la barra no está—, antes de recortar con un delimitador que
podría no existir. Re-verificado tras el arreglo: con el `<aside>` quitado, la
prueba que antes pasaba en falso ahora falla, como debe.

**`PermisosAdminTest::test_el_admin_no_ve_el_boton_de_validar` se apretó, no
se relajó.** Comprobaba la ausencia de la cadena `accion_estado` en toda la
página como sustituto de "no hay botón de validar" —bastaba porque antes el
único emisor era el botón exclusivo del jefe—. Desde que
`<x-barra-lateral-formulario>` trae su propio "Guardar Borrador"
(`accion_estado=borrador`, visible para el admin igual que para el jefe en
una matriz en borrador), ese proxy dejó de ser exclusivo del botón de validar.
Se ajustó al valor concreto que sí sigue siéndolo:
`value="confirmado"`, el único que manda "Validar y Finalizar". Ya estaba
hecho y comentado en el propio commit de integración; esta revisión lo
confirma correcto y no encontró necesidad de tocarlo más.

**Recorrido manual, en el navegador, no solo por los tests —`jefe@local.test`
/ `password`—:**

- **1366px:** el panel del dashboard mostró "Sigue por aquí" (FET, validada) y
  "Todavía sin empezar" (Potencialidad) como dos tarjetas distintas, con datos
  reales de la zona semilla. En FIT, FET, Paisaje, Percepción, Irritación,
  Concentración, Valoración Territorial y Potencialidad la barra aparece a la
  derecha con su recuento correcto (verificado con `getComputedStyle`/DOM,
  no solo visualmente): Concentración mostró "Atractivos turísticos 0/77" y
  "Planta turística 0/36"; Potencialidad, seis áreas sumando 156. Los enlaces
  de ancla desplazan al bloque correcto (comprobado con `#ft` en FIT: el
  `scrollY` salta al bloque real). El "Guardar Borrador" de la barra de FIT
  reabrió una matriz validada exactamente igual que el de abajo —confirmado
  contra la base de datos, `estado` pasó a `borrador` y `updated_at` se
  actualizó—; se volvió a validar después para dejar la zona semilla como se
  encontró.
- **1024px (el límite exacto de `lg`):** la barra sigue visible, `display:
  block`, 256px de ancho.
- **1023px, un píxel por debajo:** la barra pasa a `display: none` en las
  cuatro matrices probadas (FIT, Concentración, Potencialidad), el documento
  no gana scroll horizontal (`scrollWidth === clientWidth`) y el formulario
  vuelve a una columna de ancho completo. **Se esconde, no se apila.**
- **768px (tablet):** mismo resultado, sin scroll horizontal.
- **Regla de las 14px y `uppercase`:** revisado con `getComputedStyle` sobre
  todos los nodos de texto de la barra en Irritación —`font-size` solo usa
  `14px`/`16px`, ningún `text-transform: uppercase`—, y confirmado además por
  `grep` que ningún `text-xs` ni `uppercase` se añadió en todo el diff de la
  Parte B.

**Lo que NO se tocó, a propósito.** Involucrados y Frecuentación: CRUD de
filas, sin bloques que indexar, fuera de alcance del diseño. Ningún
controlador, modelo ni migración cambia en toda la Parte B —el único fichero
de `app/` tocado es `EstadoZona::criteriosRespondidosDe()`, un contador de
solo lectura sobre un subconjunto de columnas ya conocido—: lo que se guarda,
se valida y se calcula es exactamente lo mismo que antes de esta rama.

### Rama `resumen-lista` — franja de resumen para Involucrados y Frecuentación, terminada y fusionada (merge `3516dde`, 12 de agosto)

Cierra lo que la rama anterior había dejado fuera **a propósito**. Diseño en
`docs/superpowers/specs/2026-08-12-resumen-lista-design.md`, plan en
`docs/superpowers/plans/2026-08-12-resumen-lista.md`.

Suite: **525 → 553**. La base eran 525, no las 524 de `barra-lateral`: entre
medias entró `ResultadosMuestranTodosLosCriteriosTest` (`7201b72`), el test que
vigila el hueco de la tabla de FIT. Desglose de las 28 nuevas: 11 del componente
en aislado + 4 de Involucrados + 7 de Frecuentación + 6 de la revisión final.

**Franja, no barra lateral, y la razón está escrita** por si alguien vuelve a
proponer la barra: la de las matrices existe porque un formulario de 156
criterios es kilométrico, y estas dos listas son de unas pocas filas. Una
columna fija costaría mucha maquetación para ahorrar poco scroll, y tentaría a
mover allí la Superficie Territorial, que sería **peor** que dejarla donde
está. Lo que de verdad faltaba —«cuánto me queda»— es una línea, no una
columna.

**La tercera razón del diseño anterior había caducado.** Aquel documento decía
que la ST era «un dato de zona perdido entre las filas»; se escribió **antes**
de implementar Frecuentación y hoy es falso: la ST tiene su propia sección, con
título, explicación, formulario y botón de guardar. La franja la **nombra** como
dato; no la sustituye.

**`<x-resumen-lista>` no deriva nada**, misma regla que `<x-barra-lateral-formulario>`:
recibe `total`, `incompletos` y los permisos ya resueltos. Las dos listas cuentan
cosas distintas —un actor incompleto puede serlo por varios campos, un sitio solo
por su DET— y forzar una forma común entre ellas es lo que produjo el tipo
`actores` con `zona->involucrados()` cableado dentro. La parte que de verdad
varía —la ST— va por una **ranura**, no por un prop `extra` genérico.

**El botón de validar se mueve, no se duplica**, y con él el aviso al equipo:
son el mismo mensaje para roles distintos. Verificado por `grep`: «Validar y
Cerrar la Lista» aparece **una sola vez** en todo `resources/`, dentro del
componente.

**Lo que encontró la revisión final, y que ninguna revisión por tarea podía
ver.** Con **todos los sitios completos y sin ST**, la franja de Frecuentación
decía «todos completos» en verde y ponía la ST en gris de dato neutro. Justo al
revés de lo que importa: la ST es la que **bloquea** la validación y `N sin DET`
la que no. Nadie lo cubría porque el test de «falta la ST» usaba la lista
**vacía**, donde esa rama ni se ejecuta. Arreglado en `163d0f6`: el componente
deja de fijar el color de su ranura y la vista pinta la ST en ámbar.

En el mismo estado, `<x-pestanas-matriz>` decía «🔒 Resultados — sin sitios
completos», que era **falso**. Defecto anterior a esta rama, arreglado aquí
porque la franja lo puso a tres líneas de su contradicción, en la misma
pantalla. Ahora las tres superficies —panel de zona, pestaña y franja— usan la
misma frase literal para el mismo hecho.

**Dos huecos de test cerrados.** El término `! $confirmada` de `puedeValidar`
era **borrable de los dos controladores con los 547 tests en verde**; verificado
por mutación antes de escribir los tests que ahora lo matan. Y dos de los tests
del propio arreglo **pasaban con el arreglo revertido** —uno afirmaba sobre la
página entera una frase que escriben dos sitios distintos de esa página, el otro
pasaba el total como entero contra un `(int)` recién puesto—; los cazó la
re-revisión y se apretaron con el rojo verificado.

**Menores anotados y no arreglados, con su motivo:** dos tests solo negativos
(cada uno tiene su positivo en la misma suite), uno redundante sobre la
superficie editable (la garantía real ya existe en dos tests con regex sobre
`disabled`), y un montaje de fixture repetido tres veces.

### Rama `fundacion-visual` — Fase 0 del rediseño de interfaz, terminada y fusionada (merge `f743a60`, 12 de agosto)

El encargo era un rediseño completo: «se siente vacía, rígida y desaprovecha
casi el 60 % del ancho; las tarjetas se ven planas». Pedía cinco cosas y
nombraba **React y shadcn/ui**.

Diseño en `docs/superpowers/specs/2026-08-12-fundacion-visual-design.md`, plan
en `docs/superpowers/plans/2026-08-12-fundacion-visual.md`. **Once tareas.**
Suite: **553 → 576.**

**Se midió antes de proponer nada**, y el resultado explica la rama entera:

| Medida | Antes |
|---|---|
| Ficheros con contenedor de página propio | **39**, en **9 anchos distintos** |
| Tarjetas escritas a mano | **52** copias de `bg-white shadow-sm sm:rounded-lg` |
| Variantes del mismo botón primario | **12** (`py-2 px-5`, `py-2 px-4`, `py-3 px-6`; `rounded`, `rounded-lg`, `rounded-md`; en cuatro colores) |
| Usos de `gray-*` / `slate-*` | **1056 / 0** |

**No faltaba diseño: faltaba sistema.** Cada vista había reinventado el suyo con
una variación mínima.

**React se descartó, con el motivo escrito.** shadcn es React puro y esto es
Blade + Alpine + Tailwind, sin una línea de React en `package.json`. Migrar
significa reescribir ~40 vistas y rehacer 553 tests que comprueban HTML del
servidor: un proyecto de semanas durante las cuales el sistema no se puede
usar, no un rediseño. **Se adoptó el lenguaje visual de shadcn en componentes
Blade.**

**Se descartó también, y sigue descartado:** el badge de notificaciones —no
existe sistema de notificaciones, solo el `Notifiable` de Breeze; es un dominio
nuevo, no maquetación— y el buscador `Cmd+K`.

**Lo que entró.** Un `<x-contenedor>` de 1440 fluido, en el layout, y fuera los
39 de las vistas. `gray` redefinido como alias de `slate` en
`tailwind.config.js` —los 1056 usos **no se tocan**, cambian todos a la vez, que
descarta por construcción el único riesgo real: que convivieran los dos grises—.
Tipografía Inter. Y cuatro primitivos: `<x-tarjeta>`, `<x-boton>`, `<x-badge>` y
el propio contenedor, todos con tests en aislado. El mapa de estado→color, que
estaba duplicado entre `<x-fila-matriz>` y el badge nuevo, vive ahora en
`EstadoZona::ESTILOS_ESTADO`.

**Ninguna vista cambió de estructura.** Breadcrumbs, KPIs, columnas y tablas
ordenables son las fases 1 a 4.

#### Tres trampas que esta rama descubrió y conviene no volver a pisar

**1. `@js(...)` no se compila dentro del atributo de un `<x-componente>`.**
Blade compila la etiqueta del componente **antes** que las directivas, así que
`@js()` queda como texto literal y **rompe la hidratación de Alpine**. Salió
como cuatro tests rojos al convertir las cajas de FET, FIT, Paisaje y Valoración
Territorial. Se sustituye por `{{ Illuminate\Support\Js::from(...) }}`, que es
literalmente lo que `@js()` usa por debajo —verificado byte a byte con comillas
simples, dobles, `<script>` y `&`—.

**2. Tailwind solo escanea lo que diga su `content`, y `app/` no estaba.** Desde
que el mapa de colores vive en `EstadoZona`, hay clases fuera de
`resources/views`. Sobrevivían **por casualidad**, porque esas mismas cadenas
aparecían en vistas sin relación (`border-amber-200`, en una sola). Añadido
`./app/**/*.php`. **Verificado por mutación**, no por argumento: sin esa línea se
purga `text-amber-600`, que no está en ningún otro sitio.

**3. Un `<x-contenedor>` dentro del que ya pone el layout duplica el padding.**
64 px de aire a cada lado en vez de 32, y **ningún test lo ve**. El contenedor de
una vista se **borra**, no se sustituye; solo se escribe uno cuando la vista debe
declararse más estrecha.

#### Lo que solo se vio mirando el conjunto

- **El ancho no lo probaba ninguna página.** Al quitar de seis tests la aserción
  de `max-w-7xl`, `ContenedorTest` quedó probando el componente en aislado y
  nadie el montaje: se podía **borrar `<x-contenedor>` del layout con los 575
  tests en verde** y toda la aplicación sin ancho ni márgenes. El test que lo
  cubre afirma sobre el `<main>` y no sobre la página, porque la barra y la
  cabecera llevan su propio contenedor y buscar la cadena suelta pasaba igual.
- **La pantalla de login se había quedado fuera del sistema.** Se convirtió
  `auth-card` —que **no lo usaba nadie**, cero referencias, ahora borrado—
  mientras `layouts/guest` seguía con `bg-gray-100` y su propia caja de
  `shadow-md` sin borde. Es la primera pantalla de la aplicación.
- **Las tarjetas de zona perdían la esquina:** la caja pasó a `rounded-xl`
  (12 px) y la cabecera de imagen seguía en `rounded-t-lg` (8 px), sin
  `overflow-hidden`. La imagen asomaba por encima del borde, en la vista **por
  defecto** del dashboard operativo.
- **«Regresar» era un cuarto sistema de botón:** sus clases eran
  `<x-boton variante="secundario">` letra por letra, y seis matrices lo
  repintaban de azul con `!important` mientras dos lo dejaban tal cual.

#### La regla que se rompió dos veces, y por qué estaba mal

El plan decía «los tests existentes siguen en verde **sin modificar ninguno**».
Chocó dos veces, y las dos porque el test afirmaba sobre la **implementación**:

- Seis comprobaban `assertStringContainsString('max-w-7xl')` como sustituto de
  «la página ensancha». Al mover el ancho al layout la página queda **más**
  ancha (1440 frente a 1280) pero esa cadena desaparece. Se quitó la aserción y
  se renombraron.
- Cuatro contaban `substr_count($html, 'disabled')`, que cuenta **la palabra, no
  el atributo**, y `<x-boton>` trae `disabled:opacity-50`. Se sustituyó por
  `Tests\TestCase::contarDeshabilitados()`, que **no afloja nada**: siguen
  exigiéndose 0, 72 y 36, y ahora significan lo que su nombre dice.

Las dos veces el implementador **paró y lo reportó** en vez de reescribir en
silencio, que es exactamente para lo que servía la regla.

#### Lo que quedó fuera de la Fase 0, anotado a propósito

**Todo esto se cerró en la rama `restos-fase-0`; se conserva la lista porque
la mitad de sus entradas resultaron estar mal contadas, y eso es lo que hay
que recordar. Ver `restos-fase-0` más abajo en esta misma sección.**

- **Breeze sigue vivo:** `primary-button` y `danger-button` los usan las cinco
  vistas de `auth/` y los tres parciales de `profile/`. Solapan con `primario` y
  `peligro`. El plan no los nombró.
- **Cuatro botones sueltos sin convertir:**
  `evaluacion_paisaje/ponderacion.blade.php:122`,
  `evaluacion_valoracion_territorial/ponderacion.blade.php:33` (el único
  amarillo del sistema), `inventarios/show.blade.php:13`, y los dos pequeños de
  `admin/zonas/index.blade.php`.
- **`<x-badge>` no se usa en ninguna parte.** Seis tests y cero adopción: la
  clave `insignia` y `NOMBRES_ESTADO` no llegan a ninguna pantalla.
- **Dos `<x-contenedor ancho="estrecho">` anidados** dentro del del layout, en
  `admin/lugares/form` y `operativo/frecuentacion/form`. **Lo prescribe el
  plan.** En escritorio el ancho útil sale idéntico; por debajo del tope el
  padding se aplica dos veces (311 px en vez de 343, a 375 px de ancho).
- **`evaluacion_potencialidad/form.blade.php` es el único fichero entero fuera
  del sistema:** cero `<x-tarjeta>`, un `<style>` en línea con `#e2e8f0` y
  `#1e293b` —`slate-200` y `slate-800` copiados a mano, fuera del único fichero
  que decide qué es gris— y `class="pt-area area-{{ $color }}"` construida por
  concatenación. Hoy no se purga porque `area-*` es CSS propio; **el día que ese
  bloque se migre a Tailwind, los seis colores desaparecen en silencio**.
- **La excepción de `<x-resumen-lista>` se apoyaba en una premisa falsa**: el
  plan decía que convertirla exigía mover su `flex` a un hijo, y no es cierto —
  `$attributes->merge` concatena sobre el mismo `<div>`—.

#### Dos riesgos del purgado que siguen abiertos

- **`storage/framework/views` está en el `content` de Tailwind.** Son cientos de
  vistas compiladas: una caché rancia mantiene viva en el CSS local una clase ya
  borrada del Blade, y el fallo no aparece hasta un despliegue limpio. La
  comprobación fiable es `php artisan view:clear` **antes** de `npm run build`.
- **`resources/js/**/*.js` no está en el `content`.** Hoy inofensivo —esos
  ficheros no tocan clases—, pero la primera que alguien escriba en JS se purga
  en silencio.

### Rama `restos-fase-0` — cierra el punto 15, terminada (13 de agosto)

El encargo era la lista de arriba: los seis restos que la revisión final de
`fundacion-visual` encontró y dejó sin arreglar con su motivo. **Sin plan
escrito**: seis cambios a código que ya existe y se puede leer, así que el
diseño se acordó en la conversación y la bitácora de `.superpowers/sdd/progress.md`
hizo de registro. Cinco tareas, una añadida a mitad y la revisión de rama.
Suite: **576 → 585.** Ninguna vista cambió de estructura, y **ningún test
anterior a la rama se modificó** —el único retocado es uno que escribió la
propia rama, y lo retocó su revisión: ver más abajo—.

**La lección de la rama no es lo que se arregló, sino que la lista heredada
estaba mal contada en las dos direcciones.** Es el motivo por el que la lista
original se conserva ahí arriba en vez de borrarse:

| Lo que decía el traspaso | Lo que había |
|---|---|
| «cuatro botones sueltos» | **Dos de ellos eran seis**, y de otra categoría; **faltaba uno**, el más visible |
| «colores copiados a mano» en Potencialidad | Eso, **más una tercera tipografía y un segundo fondo de página** |
| `<x-boton-volver>` arreglado en FV11 | El componente sí; **cuatro vistas seguían repintándolo con `!important`** |

**Los barridos fallaron por cómo se buscó, no por lo que había.** Dos veces:
`<x-matriz-sin-resultados>` —un componente compartido por **cinco** matrices—
tenía su botón escrito a mano y no aparecía porque la lista original salió de
mirar vistas, no componentes; y un quinto botón de la cabecera de
`potencialidad/form` llevaba `style=` en línea, así que ningún barrido por
`class` lo encontraba nunca.

#### El hallazgo que cambió el tamaño de la rama

`evaluacion_potencialidad/form.blade.php` tenía en su `<style>` un `@import`
remoto a **DM Sans** y un `background:#f0f4f8`. La matriz más grande —156
criterios— era **la única pantalla de la aplicación con otra letra y otro
fondo**, y **ningún test lo veía**, porque los tests miran HTML y no qué fuente
resuelve el navegador. FV4 puso Inter en todo el sistema y tuvo que corregir
`layouts/guest` justo por esto; esta vista se lo saltaba por otra puerta.

Lo cubre ahora `tests/Feature/TipografiaUnicaTest.php`, tres tests: dos sobre
el fuente de las 84 vistas —quitando antes los comentarios de Blade, porque si
no el guardián fallaría contra la explicación de su propio hallazgo— y uno
sobre el **HTML servido**, que es donde se comprueba el montaje y no solo el
fichero. Rojo verificado volviendo a meter el `@import`.

Los hexadecimales copiados no eran dos sino siete más seis degradados. Dos de
ellos, `#d1d5db` y `#374151`, eran `gray-300` y `gray-700` **de la paleta
anterior a FV4**: nadie los actualizó cuando `gray` pasó a ser alias de
`slate`, que es exactamente lo que le pasa a una copia. Los demás quedan
anotados con el token del que son copia.

#### Lo que se decidió no hacer, y está escrito donde se mira

- **`<x-badge>` sigue sin adoptarse, y ahora el motivo vive en el componente.**
  Sus dos candidatos —«Completo» / «A medias» en `frecuentacion/index` e
  `involucrados/index`— usan letra por letra el verde y el ámbar de `validada`
  y `borrador`. Parece adopción cantada y no lo es: esas píldoras dicen si una
  **fila** está completa, no el estado de una matriz. Un sitio «Completo»
  dentro de una lista en borrador no está validado, nadie lo validó. Ahorraría
  cuatro copias de color a cambio de que el código afirme algo falso — el
  precedente es `<x-insignia-clasificacion>`, que por llamarse genérico invitaba
  a pintar de verde el peor resultado. **Su primer consumidor natural son las
  tarjetas de zona de la Fase 2.**
- **Potencialidad se alineó, no se migró.** Fuera la tipografía y el fondo; las
  68 líneas de CSS del acordeón, el toggle y el grid se quedan como excepción
  documentada. Reescribirlas en una vista de 445 líneas con Alpine cambiaría el
  aspecto y arriesgaría regresiones que sus tests —que miran radios y campos
  activos, no maquetación— no verían.
- **Los dos contenedores anidados no se arreglaron borrándolos**, que es lo
  primero que parece al leer «contenedor anidado». Son estrechos a propósito y
  borrarlos los mandaría a 1440. Lo único que sobraba era el padding, así que
  `<x-contenedor>` ganó `:padding` — mismo prop, mismo nombre y misma razón que
  el `<x-tarjeta :padding="false">` que existe desde FV6. No se inventó un
  mecanismo nuevo para un problema que el sistema ya sabía resolver.
- **Los seis botones de `admin/zonas/index` se quedan.** `px-3 py-1.5 text-sm`
  con colores suaves en una tabla densa es la excepción de tamaño que FV10
  respetó a propósito; convertirlos exigiría un `tamano="pequeno"` en el
  primitivo, que es otra decisión. Igual que `<x-nav-link>` y
  `<x-responsive-nav-link>`: son navegación, no botones, y la Fase 1 rehace la
  navbar entera. *(Los dos quedaron borrados en `navbar-y-migas`, que es la
  Fase 1; el aplazamiento se cumplió.)*

#### El defecto que cazó un test escrito para cazarlo

`<x-secondary-button>` y `<x-boton>` tienen defaults **opuestos**: `button`
frente a `submit`. El «Cancelar» del diálogo de borrar la cuenta vive dentro
del `<form>` de borrado, así que la conversión directa lo convertía en **un
segundo botón de borrar**. Se hizo la conversión sin el `type` a propósito, el
test falló, y solo entonces se puso. **Ningún test de los que ya había lo
habría visto**: el HTML sigue siendo válido y la página responde 200.

`BotonesPerfilTest` afirma sobre la página servida y no sobre el componente en
aislado —lo que hay que garantizar no es que `<x-boton>` sepa recibir un
`type`, sino que esta vista se lo pase—, y lleva su contraparte positiva,
porque sin ella poner `type="button"` en los dos dejaría el primer test verde
y el diálogo sin forma de borrar.

Los tres componentes de Breeze (`primary-button`, `secondary-button`,
`danger-button`) quedaron **borrados** al no tener un solo uso vivo.
`<x-modal>`, `<x-input-label>`, `<x-text-input>` y `<x-input-error>` son
también de Breeze y **siguen ahí**: no son botones.

#### Lo que no se pudo verificar, y no se da por hecho

**La comprobación en navegador no se hizo:** Playwright no está instalado en
esta máquina y ponerlo con su Chromium es un cambio de entorno que no estaba en
el encargo. Lo que sí se verificó: el CSS construido declara **solo** `Inter`
para `font-sans` —ni rastro de DM Sans, googleapis o gstatic en `public/build`—
y el HTML servido de Potencialidad no contiene `@import` ni `font-family`
ajeno. Queda sin cubrir **qué fuente acaba dibujando el navegador**, que
depende de que bunny.net responda. La causa sí está atada: esa página ya no
pide ninguna familia.

#### Anotado para la Fase 1, no arreglado aquí

Las cinco páginas de resultados que tienen «← Volver al Formulario» al pie
llevan ya un `<x-pestanas-matriz>` arriba con esa misma navegación. **El botón
duplica la pestaña.** Es estructura de página, que es justo lo que la Fase 0 no
toca.

Y la bomba de `area-{{ $color }}` de Potencialidad **no se desactivó, se
documentó**: hoy no explota porque `area-*` es CSS propio y no de Tailwind.

#### Lo que encontró la revisión de la rama

Se repitió sobre la rama entera porque la sesión se cortó a mitad de T6 y no
quedaba registro de que hubiera corrido. **Ningún defecto bloqueante**, y dos
guardianes que afirmaban menos de lo que decía su docblock — que es la forma
que toma aquí el defecto que ningún test ve, porque el test *es* el defecto:

- **`ResumenListaTest` buscaba `flex` como subcadena, y `flex` está contenida
  en `flex-wrap`.** Pasaba por el motivo correcto, pero quitar `flex` dejando
  `flex-wrap` lo habría dejado en verde afirmando que hay contenedor flex
  donde no lo habría. Compara por token desde ahora.
- **`TipografiaUnicaTest` barría `resources/views` y dejaba abierta la puerta
  más ancha.** El sitio natural para añadir una fuente no es una vista: es
  `resources/css/app.css`, donde un `@import` no afecta a una pantalla sino a
  **toda** la aplicación. El guardián nacido para impedir una segunda
  tipografía no miraba el único sitio desde el que se impone a todas. Cubierto.

Los dos rojos verificados. **Y una premisa que se comprobó en vez de creerse:**
el `:padding="false"` de los dos contenedores anidados solo es correcto porque
`layouts/app` envuelve `$slot` en un `<x-contenedor>` que ya trae el padding
(línea 40). Si no lo trajera, esos dos formularios habrían quedado pegados al
borde de la pantalla en móvil y **ningún test lo vería**: afirman clases, no
maquetación.

**Mirado y no cambiado:** `potencialidad/form:29` conserva
`texto="← Volver a la Zona"` mientras las siete matrices hermanas usan
`texto="Regresar"`. Parece divergencia y no lo es del todo — ese botón vive en
el `header` y los otros siete en el cuerpo bajo `<x-pestanas-matriz>`, así que
igualar el texto sería inventar una regla— y además es anterior a esta rama.

### Rama `navbar-y-migas` — Fase 1 del rediseño de interfaz, terminada (13 de agosto)

Spec y plan en `docs/superpowers/`, ocho tareas. Suite: **589 → 608.** Objetivo:
una sola forma de saber dónde se está y de subir de nivel —migas—, y una barra
que sirva a los dos perfiles sin duplicar sus enlaces.

**`<x-boton-volver>` ya no existe.** 22 llamadas en 20 ficheros, el componente y
`BotonVolverTest` borrados; en su lugar, `<x-migas>` en 29 páginas. Las migas se
pusieron **antes** de quitar ningún botón, que era la única restricción de orden
del diseño: al revés, cada tarea intermedia dejaba páginas sin salida.

También se fueron `<x-nav-link>` y `<x-responsive-nav-link>`, los dos últimos
componentes de Breeze que la Fase 0 dejó anotados a propósito. **Del `Cmd+K`
aplazado dos veces se decidió que no se hace**: lo sustituye un selector de zona,
porque el salto que de verdad se repite en un árbol de tres niveles es cambiar
de zona, y un atajo de teclado no sirve en móvil.

**Cada vista declara su miga y no se deriva de la ruta**, siguiendo el patrón
que `<x-pestanas-matriz>` ya había establecido en 18 vistas. Las 20 migas de
matriz se insertaron **tomando la clave de la línea de `<x-pestanas-matriz>` que
tenían al lado**, no escribiéndolas a mano: las dos responden a «qué matriz es
esta», así que copiarla es lo que impide que discrepen.

#### Los huecos que aparecieron al usar lo que el plan daba por hecho

- **`vtt` no tiene `editar`.** Es de tipo `resultado` —se calcula a partir de FIT
  y FET— y el componente pedía `rutas['editar']` a secas, así que reventaba con
  «Undefined array key» ante una situación legítima. El arreglo obligó a separar
  dos cosas que eran la misma: **«es la hoja» y «no tiene destino»**.
  Confundirlas ponía `aria-current` en un tramo intermedio, o sea, anunciaba
  como página actual una que no lo es.
- **Dos «← Volver a la zona» más**, en `involucrados/index` y
  `frecuentacion/index`, escritos como `<x-boton>` y no como `<x-boton-volver>`.
  Ningún barrido del componente los encontraba. **Es la tercera vez en este
  repositorio que un barrido falla por cómo se buscó y no por lo que había.**
- **El guardián se escribió antes de tocar las vistas y se usó para
  encontrarlas.** Nombró siete; una de las siete —`operativo/dashboard`— no debe
  llevarlas, porque *es* «Mis Zonas», la raíz del rastro.

#### Lo que encontró la revisión de rama, y ningún test veía

- **El selector de zona no existía en móvil.** Iba dentro del grupo
  `hidden sm:flex`. Que «funciona en móvil, donde un atajo de teclado no sirve
  de nada» es medio argumento por el que sustituyó al `Cmd+K`, y estaba escrito
  en el componente, en el commit y en el docstring del test mientras el móvil se
  quedaba sin él. **Ningún test podía verlo: el elemento SÍ está en el HTML
  servido y lo esconde el CSS**, que es precisamente lo que los tests no miran.
- **Once migas enlazaban a la página en la que ya estabas.** La ruta `editar` de
  una matriz ES la del formulario, así que en los nueve formularios y en los
  índices de involucrados y frecuentación el tramo de la matriz recargaba lo que
  ya tenías delante. La regla estaba escrita en el componente pero solo se
  aplicaba a la hoja; ahora vale para todo el rastro.
- **Un test que no podía fallar por su motivo.**
  `test_sin_zonas_asignadas_no_se_pinta_el_selector` medía sobre «Mis Zonas», la
  única página donde el selector no se pinta nunca: pasaba aunque se borrara la
  guarda que decía vigilar. Movido a «Perfil», con contraparte.

**Y una que encontró mirar la barra de verdad**, no leer el diff: el selector
flotaba en mitad de la barra. El contenedor superior es `justify-between` y
estaba pensado para dos hijos; el selector entró como tercero.

#### Tres tests anteriores se pusieron rojos, y los tres tenían razón

Ninguno se relajó sin consultar, y ninguno era un falso positivo del que
deshacerse:

- `PaginaZonaTest` afirmaba `assertSame(1, ...)` sobre el nombre de la zona,
  cuando su propio docstring dice que vigila que no se pinte **una vez por
  fila**. El 1 era un atajo; ahora se compara contra el número de matrices.
- `ConmutadorVistaTest` contaba el enlace a la zona y encontró tres: fue lo que
  destapó que el selector sobraba en «Mis Zonas». Quedó intacto.
- El guardián de los destinos, escrito en T5, contaba `esAdmin()` y se puso rojo
  cuando el selector añadió el suyo —que decide si el selector APARECE, no a
  dónde lleva un enlace—. **Medía algo parecido a lo que importaba, y las cosas
  parecidas dan falsos positivos.** Ahora cuenta la lista y sus consumidores.

#### Los guardianes que deja

`NavegacionCompletaTest`, tres tests sobre el fuente: que **toda** página de
`operativo/` trae migas —así cubre también las que ninguna prueba renderiza—,
que los destinos del navbar se deciden en un solo sitio, y que las zonas del
selector también, ahora que escritorio y móvil las recorren los dos.

### Rama `dashboard-mis-zonas` — Fase 2 del rediseño, fusionada con la revisión pendiente (merge `d6f3516`, 13 de agosto)

**Se fusionó sin la Tarea 7, y eso hay que saberlo al leer lo que sigue.** La
sesión se cortó tras la Tarea 6; las seis tareas hechas tienen su revisión
limpia una por una —salvo la sexta, cuyo paquete quedó armado sin revisor que
volviera—, pero **la revisión de la rama entera y el paso de mirar la página
funcionando no llegaron a correr**. Se fusionó igualmente, por decisión
explícita, para no dejar la rama a medias entre dos máquinas. Lo que falta está
anotado como punto 17 de §6, con el guion exacto; no es un olvido, es una deuda
con fecha.

Por qué importa: en las cuatro fases anteriores ese paso encontró justo lo que
ningún test veía —una franja verde sobre un estado bloqueado, un selector
flotando en mitad de la barra, un contenedor que doblaba el padding—.

Spec y plan en `docs/superpowers/`, siete tareas. Suite: **608 → 632** (3973
aserciones, ~50 s; partida, 85 en `Unit` y 547 en `Feature`). Base de rama:
`0cd8ecf`.

| Tarea | Commit | Suite |
|---|---|---|
| T1 el desglose en el servicio | `7b3c247` | 611 |
| T2 la ordenación en el servidor | `7ffe6a6` | 619 |
| T3 la franja de cifras | `272453c` | 622 |
| T4 el desglose en las dos maquetaciones | `c0910ed` | 626 |
| T5 la tabla ordenable | `3fb0732` | 631 |
| T6 cero zonas | `1bcffd1` | 632 |
| T7 revisión y traspaso | — | **no hecha** (§6, punto 17) |

Las cinco primeras pasaron revisión limpia. **La de T6 no llegó a correr**: el
paquete quedó armado y la sesión murió antes de que volviera el revisor.

Qué hace la rama: `progresoDe()` pasa a devolver `hechas`, `borradores`,
`sin_empezar` y `total` —el estado de una zona es un desglose, no una
fracción—; el orden de «Mis Zonas» se pide por URL y **lo resuelve el
servidor** (Playwright no está instalado, así que con Alpine sería invisible
para la suite); una franja de cifras de conjunto arriba; la vista de lista pasa
a ser una tabla con cabeceras ordenables; y sin zonas no se pinta el conmutador
de maquetación, que no tendría nada que conmutar.

#### Lo que apareció al implementar y el plan no decía

- **Un barrido de mi plan se dejó dos consumidores.** El de `hechas / total`
  encontró `admin/zonas/index` y `ConmutadorVistaTest`, y no vio que
  `PaisajeTest` y `ValoracionTerritorialTest` afirmaban `assertSee('0 / 10')`
  contra el dashboard. **Es la cuarta vez en este repositorio que un barrido
  falla por cómo se buscó y no por lo que había:** busqué la clave `'hechas'`
  en el código y no la cadena renderizada en los tests. Los dos se actualizaron
  al dato que pasa a ser (`'10 sin empezar'`), no se relajaron —la zona es
  nueva, así que las diez sin empezar son un dato real que fallaría con el
  dashboard roto—, pero son dos tests afirmando sobre una cadena distinta de la
  que eligió su autor.
- **Una clase de Tailwind construida por concatenación**, que es justo lo que
  la restricción global de la rama prohíbe porque el purgado se las lleva.
  `<x-cabecera-ordenable>` interpolaba `'text-' . $alineacion` en una prop que
  no usaba nadie. Hoy no se rompía **de milagro**: `text-left` aparece literal
  en dos vistas de admin y el purgado la conservaba por ellas. Arreglado en T6.
- **La tabla de recuento del plan traía un error aritmético** —nueve tests en
  T2 donde el brief lista ocho—, así que las cifras esperadas de T3 en adelante
  iban una alta. El total real es 632, no 633. No falta ningún test.
- **`scripts/review-package` y `scripts/sdd-workspace` machacan
  `.superpowers/sdd/.gitignore`**, dejándolo en `*` y borrando los comentarios
  que explican qué de esa carpeta viaja con el repositorio. Pasó dos veces; se
  dejó de usar el script y los paquetes de revisión se arman a mano.

#### Restos que la rama deja escritos y no toca

- **Renombrar `hechas` a `validadas`**, cuando una fase entre de verdad en
  admin. Aquí no se toca ninguna pantalla de admin, por restricción global.
- **El `📍` de la tarjeta**, anterior a la Fase 0; se arregla donde se arreglen
  los demás.
- **Una premisa comprobada que no generó trabajo:** «Mis Zonas» elige zonas por
  rol y el selector de la barra hace la unión. Hoy coinciden porque
  `Admin\ZonaController` valida los roles; el día que esa validación se afloje,
  discreparán.

#### Dónde está el porqué

`.superpowers/sdd/2026-08-13-dashboard-mis-zonas/progress.md` es el detalle: los
rulings de cada tarea y los **seis menores aplazados** que la revisión de rama
tiene que ver. Los `task-N-report.md` de esa carpeta son el porqué de cada
commit. El guion de la T7 está en la Tarea 7 del plan.

**Esa carpeta no se borró al terminar, contra lo que dice la skill**, y es
deliberado: la regla 3 de `CLAUDE.md` manda que los informes viajen con el
repositorio. Tampoco se copiaron a la raíz de `sdd/`, donde ya viven los de la
Fase 1 y copiarlos encima destruiría justamente ese rastro.

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

### Frecuentación contra PostgreSQL — verificado (13 de agosto de 2026)

Era la única matriz cuya migración nunca se había probado contra la base de
producción: quedó pendiente porque en la máquina donde se implementó Docker no
levantaba su backend. Aquí sí, así que se cerró — mismo método que arriba,
`docker run --rm -d --name frecuentacion-pg`, PostgreSQL **16.14**, borrado por
nombre al terminar y sin tocar ningún contenedor ajeno.

- **La migración corre**, y el esquema queda como pedía el diseño:
  `frecuentacion_config.st` y `frecuentacion_sitios.det` son `numeric(14,4)`,
  **nullable y sin defecto**, comprobado en `information_schema`.
- **Los 36 tests de Frecuentación pasan contra Postgres**, no solo contra
  SQLite.

**Y el `float` de los modelos resultó no ser decoración**, que es lo que valía
la pena averiguar. Un viaje de ida y vuelta real por Postgres devuelve:

```
st  -> 1234.5678 (double)      <- por Eloquent, con el cast
crudo sin cast -> '1234.5678' (string)   <- lo que da el driver
```

**En PostgreSQL `numeric` llega como cadena**, no como número. En SQLite llega
como float, así que sin el cast `'st' => 'float'` la aritmética y las
comparaciones de `st`/`det` se comportarían **distinto en producción que en
desarrollo**, y ningún test de SQLite lo vería. El cast está puesto en los dos
modelos y por eso no hay defecto; queda escrito porque el día que alguien añada
otra columna `decimal` a estas tablas y se olvide del cast, esto explica por
qué falla solo en Render.

**Cómo se comprobó que los tests usaban Postgres de verdad** y no caían a
SQLite en silencio —`phpunit.xml` fija `sqlite`/`:memory:`, y las variables de
entorno solo ganan porque esas entradas no llevan `force="true"`—: se dejaron
filas por tinker en la base del contenedor y tras la corrida **habían
desaparecido**, porque `RefreshDatabase` remigró esa misma base. Con SQLite de
por medio seguirían ahí.

En esta máquina `pdo_pgsql` y `pgsql` están compiladas de forma nativa, así que
no hace falta el `php -d extension=...` que documenta el apartado siguiente:

```bash
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5433 DB_DATABASE=turismo \
  DB_USERNAME=postgres DB_PASSWORD=... php vendor/phpunit/phpunit/phpunit tests/Feature/FrecuentacionTest.php
```

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
| `docs/superpowers/specs/2026-08-11-permisos-y-navegacion-design.md` | Diseño de los permisos del admin y la navegación formulario/resultados |
| `docs/superpowers/specs/2026-08-12-dashboard-y-formularios-design.md` | Diseño del dashboard y la barra lateral de los ocho formularios de matriz |
| `docs/superpowers/specs/2026-08-12-resumen-lista-design.md` | Diseño de la franja de resumen, y por qué **no** es una barra lateral |
| `docs/superpowers/plans/2026-08-12-resumen-lista.md` | Su plan, ejecutado en la rama `resumen-lista` |
| `docs/superpowers/specs/2026-08-12-fundacion-visual-design.md` | Diseño de la Fase 0: por qué no es React, y qué es «el sistema» |
| `docs/superpowers/plans/2026-08-12-fundacion-visual.md` | Su plan, once tareas, ejecutado en la rama `fundacion-visual` |
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
3. ~~**Una matriz sin implementar**: el Índice Espacial de Frecuentación~~ —
   hecho, rama `frecuentacion` (§3): **las diez matrices del sistema quedan
   implementadas, ninguna pendiente**. `ÍETP = DET ÷ ST` por sitio, `ÍEFT = Σ ÍETP`
   para el territorio, con la ST como escalar de la zona en
   `frecuentacion_config`. Detalle completo, decisiones de diseño y el bug de
   orden que corrigió, en §3.
4. ~~**Migrar Percepción y Potencialidad**~~ — hecho. Percepción en la rama
   `percepcion-componentes`, Potencialidad en `potencialidad-componentes`
   (§3): 3 niveles, sin cuarto nivel real ni escala invertida en los 156
   criterios, verificado contra el instrumento original antes de asumirlo.
   No heredaba de `MatrizPonderadaController` y tenía la configuración de
   campos activos, que se quedó intacta -solo cambió el control de
   calificación-.
5. ~~**«Reabrir» una matriz validada**~~ — resuelto del todo. Funcionaba desde
   `reabrir-matriz`, y `permisos-y-navegacion` cerró lo que quedaba: el gesto ya
   es descubrible, con `<x-aviso-reapertura>` en los ocho formularios de matriz
   avisando al jefe —y solo a él— de que guardar la devolverá a borrador.
   Involucrados ya avisaba por su cuenta en `index.blade.php`.
6. ~~**Permisos del admin y navegación**~~ — hecho en `permisos-y-navegacion`
   (§3). El admin escribe, hay pestañas formulario/resultados y conmutador
   lista/tarjetas en zonas.
7. **Tres menores aplazados de esa rama** —eran cinco—. Detallados en §3. El 13
   de agosto de 2026 se cerraron dos, y **los dos por el mismo motivo: la lista
   estaba mal**. El `url()->previous()` que ella misma daba como «el que más
   merece cerrarse» llevaba ramas arreglado y nadie lo tachó; y el aviso de
   reapertura mostrado a quien no puede provocarla **estaba en dos pantallas**,
   `involucrados/index` y `frecuentacion/index`, no en la que la lista decía.

   Quedan **dos**, con su motivo intacto: `<x-pestanas-matriz>` consulta la
   base desde la vista y arreglarlo bien exige pasar la evaluación por prop y
   tocar 18 vistas; y `EstadoZona` usa `self::` donde podría usar `static::`,
   que en una `final class` da igual.

   El tercero —`vtt/resultado` sin test del texto ni el color de su botón— se
   cierra en `navbar-y-migas` **porque el botón dejó de existir**: se fue con
   `<x-boton-volver>` y lo sustituye la miga, que sí tiene tests. Se anota así
   y no se tacha a secas: no se cubrió lo que pedía, desapareció lo que había
   que cubrir.
8. **Verificación visual pendiente** de la rama `permisos-y-navegacion`: el
   conmutador lista/tarjetas es Alpine puro y ningún test comprueba que el botón
   conmute ni que la preferencia sobreviva a una recarga.

   **Auditado el 13 de agosto de 2026 y sigue siendo exacto**, que es lo único
   que se podía hacer sin cambiar el entorno. `ConmutadorVistaTest` tiene seis
   tests y todos miran lo mismo: que las dos maquetaciones están en el HTML
   servido y que cada pantalla usa su propia variable. El componente no guarda
   nada —`<x-conmutador-vista modelo="...">` solo emite dos `<button>` con
   `@click` de Alpine—, así que **conmutar y persistir solo ocurre en el
   navegador**. Cerrarlo exige Playwright, que no está instalado, y ponerlo con
   su Chromium es un cambio de entorno que merece decidirse aparte. **No es
   código: es la única entrada de esta lista que un test de PHP no puede
   cubrir.**
9. ~~**Dashboard vacío y ancho de los formularios**~~ — hecho en
   `dashboard-y-formularios` (Parte A) y `barra-lateral` (Parte B), las dos
   fusionadas. Ver §3.
10. ~~**Barra lateral para Involucrados y Frecuentación**~~ — hecho en
    `resumen-lista` (§3), y **acabó no siendo una barra lateral**. Al darle su
    propio diseño, dos de las tres razones que esta entrada daba se cayeron: la
    ST ya tiene su sección propia desde que se implementó Frecuentación —esta
    entrada la describía como «un dato de zona perdido entre las filas», que era
    una predicción escrita antes de implementarla—, y para unas pocas filas una
    columna fija cuesta mucha maquetación y ahorra poco scroll. Quedó en una
    **franja** sobre la tabla: «5 sitios · 2 sin DET», la ST como dato, y el
    botón de validar, que **se movió** desde el final de la página en vez de
    duplicarse. Lo que esta entrada sí acertó de lleno: no se empezó copiando el
    componente, y `<x-resumen-lista>` no comparte una línea con
    `<x-barra-lateral-formulario>`.
11. ~~**Etiquetas de FIT y FET**~~ — **cerrado del todo; el encabezado se quedó
    sin tachar cuando se resolvió la tercera el 12 de agosto de 2026.**
    Verificado contra el código el 13 de agosto: `app/Matrices/Fet.php` y
    `evaluacion_fet/ponderacion.blade.php` dicen los dos «Seguridad del Destino
    o Sitio de Visita», así que la contradicción que este punto describía —el
    mismo criterio con dos nombres según la pantalla— ya no existe. Lo que
    sigue debajo es el razonamiento, que se conserva porque la regla que fija
    —manda la mayoría, 2 de 3, cuando las hojas del instrumento se
    contradicen— vale para la próxima discrepancia.

    El texto original decía: dos de las tres resueltas, y una tercera cosa peor
    que salió al mirarlo.

    **Resueltas**, cotejadas contra `IMPLEMENTADA MATRIZ DE VOCACIÓN TURISTICA
    RURAL .xlsx`: no eran decisión de contenido, eran **palabras caídas al
    transcribir**. El instrumento dice «Grado de Apertura de la Comunidad
    **Local**» en los tres sitios donde aparece, y «Productos **Turísticos**
    Territoriales» siempre; nuestros formularios habían perdido una palabra en
    cada uno. Corregido en `app/Matrices/{Fit,Fet}.php`.

    ~~**Pendiente, y esta sí es tuya:** «Seguridad del Destino»~~ — **resuelto
    el 12 de agosto de 2026**, y al ir al fichero salió que **no era una
    discrepancia, eran tres**. Las tres hojas de FET no se ponen de acuerdo:

    | Criterio | Lista de criterios | Escala Valorativa | Ponderación |
    |---|---|---|---|
    | Flujos | Flujos Turísticos **Reales** `C6` | Flujos Turísticos `B6` | Flujos Turísticos `B6` |
    | Seguridad | Seguridad del Destino `C12` | …**o Sitio de Visita** `B30` | …**o Sitio de Visita** `B12` |
    | Imagen percibida | Imagen Percibida del Visitante `C13` | Imagen Percibida del Visitante `B34` | Imagen percibida de los **Visitantes** `B13` |

    **Nuestro código ya elegía, sin decirlo.** En Flujos y en Imagen se había
    quedado con la forma que dicen dos de las tres hojas. En Seguridad era donde
    se había copiado la contradicción: forma corta en el formulario, larga en la
    tabla de ponderación. **Mismo criterio, dos nombres según la pantalla.**

    Así que la pregunta no era «corta o larga» sino **qué hoja manda cuando el
    instrumento se contradice**, porque la respuesta arrastraba las tres.

    **Decidido: manda la mayoría, 2 de 3.** Hace explícita la regla que el
    código ya seguía, y deja la aclaración donde hace falta: la forma larga vive
    en la Escala Valorativa, la hoja que consulta quien está puntuando, y «o
    Sitio de Visita» le dice que el criterio cubre también el sitio concreto que
    se visita, no solo el destino entero. Un solo cambio, en
    `app/Matrices/Fet.php`, con el razonamiento en un comentario junto al
    criterio. Ningún número cambia: son etiquetas.

    La alternativa descartada era «manda la lista de criterios, que es la hoja
    que los nombra»; habría exigido dos cambios en direcciones opuestas —
    Seguridad a la corta en la tabla, y Flujos a «Flujos Turísticos **Reales**»,
    que hoy no decimos en ninguna parte.

    **Por qué no hay un test que vigile esto.** Sería el guardián natural —que
    el formulario y la tabla de ponderación digan lo mismo de cada criterio—,
    pero **FIT acorta sus etiquetas en la tabla a propósito**: «Básica» bajo el
    bloque «Infraestructura», frente al «Infraestructura Básica» del formulario.
    Un test de igualdad literal cubriría FET y fallaría en FIT, y medio guardián
    con nombre de guardián entero es peor que ninguno: invita a confiar en él.
    Queda anotado por si algún día se unifican esas dos tablas escritas a mano.

    **Lo que no era desalineación:** en Productos, el formulario nombra el
    *criterio* (plural) y la tabla el *bloque ponderado* «Producto Turístico
    Territorial (PTt)» (singular). El instrumento hace esa misma distinción.

12. ~~**La tabla de resultados de FIT enseñaba doce de sus dieciocho
    criterios**~~ — arreglado. Faltaban los seis de Facilidades Turísticas:
    recepción, centros de interpretación, senderos, estacionamientos,
    campamentos y miradores. **Contaban para `media_ft` y para la nota, pero no
    aparecían por ninguna parte**, así que nadie podía cuadrar su resultado con
    lo que había respondido. El `rowspan="8"` del bloque ya las contaba y solo
    había dos filas debajo.

    Nadie lo detectó porque **las tablas de FIT y FET están escritas a mano fila
    a fila**, mientras las demás matrices recorren su instrumento con un
    `@foreach`. `tests/Unit/ResultadosMuestranTodosLosCriteriosTest.php` lo
    vigila ahora para las cinco matrices con instrumento consultable.

    **Las ocho matrices con instrumento fijo están cubiertas.** Se añadieron
    Irritación, Potencialidad y Concentración; la razón que esta sección daba
    para dejarlas fuera —«su instrumento no expone `todos()`»— **era falsa** para
    las dos primeras, que sí lo tienen. Solo Concentración usa otra puerta,
    `campos()`, así que ahora cada matriz declara cómo se consulta la suya.

    Potencialidad entra aunque hoy no afirme nada: **su página de resultados no
    muestra ningún criterio suelto**, solo los 23 agregados por subgrupo. Es
    coherente —una tabla de 156 filas no se lee, y el formulario ya los enseña—,
    así que queda como hilo de alarma para el día en que alguien le añada un
    desglose por criterio y se deje la mitad.

    Fuera quedan **Involucrados y Frecuentación**, y con razón: son CRUD de filas
    de longitud variable, no formularios de criterios.

    **El límite del test, para que nadie lo dé por cubierto:** una vista que
    itera queda fuera de la comprobación, así que si su bucle recorriera un
    subconjunto del instrumento pasaría en verde igual. Se verificó que hoy no
    ocurre —los controladores pasan las constantes completas—, pero si algún día
    una vista recibe un recorte a propósito, eso necesita otro mecanismo.
13. ~~**La verificación contra PostgreSQL de Frecuentación**~~ — hecha el 13 de
    agosto de 2026, detallada en §4. Docker sí levanta en esta máquina, que era
    todo lo que faltaba. La migración corre contra **PostgreSQL 16.14**, `st` y
    `det` quedan `numeric(14,4)` nullable y sin defecto, y los 36 tests de
    Frecuentación pasan contra Postgres. **Ya no queda ninguna matriz sin
    probar contra la base de producción.**

    De paso quedó demostrado que el cast `'st' => 'float'` **no es
    decoración**: en Postgres `numeric` llega del driver como **cadena** y en
    SQLite como float, así que sin él la aritmética se comportaría distinto en
    producción que en desarrollo y ningún test de SQLite lo vería.
14. **Las fases 2 a 4 del rediseño de interfaz.** La Fase 0 —la fundación— y la
    Fase 1 —navbar y migas— están fusionadas (§3). Quedan tres, y **cada una
    necesita su propio diseño corto antes de tocar código**: el orden importa
    porque si una vista se rediseña antes de que exista el primitivo que
    necesita, inventa el suyo y aparece la segunda fuente de verdad de siempre.

    - ~~**Fase 1 — navbar y breadcrumbs.**~~ — hecha en `navbar-y-migas` (§3).
      Suite **589 → 608**. `<x-boton-volver>` borrado y sustituido por
      `<x-migas>` en 29 páginas; el navbar decide sus destinos en un solo sitio
      y los dos perfiles lo comparten. **El `Cmd+K` quedó descartado, no
      aplazado por tercera vez:** lo sustituye un selector de zona, porque el
      salto que de verdad se repite en un árbol de tres niveles es cambiar de
      zona —y funciona en móvil, donde un atajo de teclado no sirve de nada—.
    - ~~**Fase 2 — dashboard / Mis Zonas.**~~ — hecha en `dashboard-mis-zonas`
      (§3). Suite **608 → 632**. El estado de una zona pasa a ser un desglose y
      no una fracción, hay cifras de conjunto arriba, y la vista de lista es una
      tabla que ordena **en el servidor**, por parámetro de URL, porque
      Playwright no está instalado y con Alpine el orden sería invisible para la
      suite. **Se fusionó con la revisión de rama sin correr:** punto 17.
    - **Fase 3 — detalle de zona** en dos columnas, con panel lateral de
      información de la zona.
    - **Fase 4 — formularios.** Consolidar el banner de borrador y la escala de
      valoración en una sola franja compacta. **Ojo:** la barra lateral con
      índice, progreso y botón de guardar **ya existe** (`<x-barra-lateral-formulario>`,
      en los ocho formularios de matriz más Potencialidad), y las píldoras de
      criterio ya son un control segmentado (`<x-criterio-pildoras>`). El
      encargo original pedía las dos cosas como si no existieran.

    **Fuera de las cuatro fases, hasta que se diseñe como funcionalidad:** el
    badge de notificaciones. No hay sistema de notificaciones —solo el
    `Notifiable` de Breeze—, así que es un dominio nuevo, no maquetación.
15. ~~**Los restos de la Fase 0**~~ — hecho en `restos-fase-0` (§3). Suite
    **576 → 585**. Los botones de Breeze convertidos y sus tres componentes
    borrados; los «cuatro botones sueltos» resultaron ser otra cosa en las dos
    direcciones; `<x-contenedor>` ganó `:padding` y los dos anidados dejaron de
    doblarlo; `<x-resumen-lista>` pasó a `<x-tarjeta>`; y Potencialidad se
    alineó — donde apareció **una tercera tipografía y un segundo fondo de
    página** que el traspaso no mencionaba y ningún test veía.

    **Dos cosas siguen abiertas a propósito, y no son olvidos:**
    `<x-badge>` **sigue sin usarse** —el motivo está escrito en el propio
    componente, y su primer consumidor natural es la Fase 2— y los **seis**
    botones de `admin/zonas/index` se quedan pequeños hasta que exista un
    `tamano="pequeno"` en el primitivo.
16. **Tres menores aplazados de `resumen-lista`.** Auditados el 13 de agosto de
    2026: **uno arreglado, y los otros dos se quedan con el motivo afinado.**

    - ~~`test_el_campo_de_superficie_sigue_siendo_editable` comprueba que el
      campo está y no que no esté deshabilitado.~~ — **arreglado.** Era cierto
      y merecía cerrarse: el campo lleva `@disabled(! $puedeEditar)`, así que
      podía llegar deshabilitado con el test en verde afirmando por su nombre
      que se puede escribir en él. Ahora mira el atributo con el mismo recorte
      que ya usaban los dos tests del final del fichero —la clase
      `disabled:bg-gray-100` está SIEMPRE, así que solo la parte de la etiqueta
      anterior a `class=` puede llevar el atributo real—. Rojo verificado
      forzando el campo a deshabilitado.
    - Dos tests del componente son solo negativos. **Se quedan**: cada uno
      tiene su contraparte positiva ejercitando la misma rama, que es
      exactamente lo que hace que un negativo asegure algo.
    - Tres tests de Frecuentación repiten el montaje de «sitio con DET + ST».
      **Se quedan, y el motivo cambia respecto a lo que decía esta entrada.**
      Al mirarlos de cerca **no son el mismo montaje**: cada uno usa una ST y
      unos DET distintos (5.0, 12.5, 2.0, 4.0…) sobre los que ese test afirma
      después. Un ayudante genérico escondería justo el dato del que depende
      cada prueba, que es peor que la repetición. `dosSitiosUnoSinDet()` existe
      para el caso que sí se repite igual, y ahí ya se usa.
17. **La revisión de la Fase 2, que se fusionó sin correr.** Es lo primero de
    esta lista, no lo último: cada día que pase, el código revisado y el sin
    revisar se mezclan más. Son dos pasos, y ninguno necesita escribir código:

    - **Mirar «Mis Zonas» funcionando**, con un jefe de tres o más zonas en
      estados distintos. Seis comprobaciones: que la franja de cifras cuadre con
      lo que suman las tarjetas; que las tres insignias quepan sin desbordar la
      tarjeta ni partir una palabra; que cada cabecera ordene y la flecha señale
      el sentido correcto; que a ~375 px la tabla haga scroll **dentro de su
      tarjeta** y no empuje la página; que con una sola zona no haya franja y
      con ninguna solo el aviso; y que el panel de «siguiente paso» señale a la
      misma zona ordenes como ordenes.
    - **Revisar la rama entera**, `git diff 0cd8ecf..1bcffd1`. Dos cosas
      llamarán la atención de cualquier revisor y **son decisiones, no
      descuidos**: `hechas` no se renombra a `validadas` —eso entra el día que
      una fase toque admin de verdad— y el desglose no lleva insignia de «zona
      terminada», porque los colores de `ESTILOS_ESTADO` significan el estado de
      una MATRIZ. La respuesta está escrita en la spec; no se rehacen.

    Los **seis menores aplazados** que la revisión debe ver están en
    `.superpowers/sdd/2026-08-13-dashboard-mis-zonas/progress.md`, cada uno con
    su razón. El que más peso tiene: `PaisajeTest` y `ValoracionTerritorialTest`
    cambiaron su `assertSee('0 / 10')` por `'10 sin empezar'`. Fue obligado y no
    relaja nada —la zona es nueva, así que las diez sin empezar son un dato real
    que fallaría con el dashboard roto—, pero son dos tests afirmando sobre una
    cadena que no eligió su autor.

### Fuera de código, en Render

Ninguna depende de programar, y las tres están vivas:

- **Rotar la contraseña del admin.** `admin@turismo.com` / `password` sigue
  funcionando en producción.
- **Rellenar el SMTP.** Sin él, recuperar contraseña no envía nada.
- **Crear el bucket S3** y poner `AWS_*` y `FILESYSTEM_DISK=s3`. El código está
  hecho y verificado con MinIO; hasta configurarlo, **las fotos se pierden en
  cada redespliegue**.

## 7. Detalle suelto

`package-lock.json` **vuelve a aparecer modificado** en el árbol, a 12 de agosto
de 2026: se regenera al instalar en Windows, y el `npm ci` de la imagen de
producción usa el generado en Linux. Esta sección llegó a decir que el árbol
estaba limpio; duró lo que tardó el siguiente `npm install`. **No entra en
ningún commit** —se dejó fuera a propósito de los seis de `resumen-lista`—.
Revertirlo sigue siendo lo correcto:

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

**No hay ninguna rama que retomar: todo está fusionado en `main`**, incluida la
Fase 2 del rediseño. Lo que sí queda vivo es una **deuda de revisión** —la Fase 2
se fusionó sin la suya—, y es el punto 17 de §6.

**En el clon nuevo faltarán ramas locales, y no falta nada con ellas.** La
máquina donde se cerró la Fase 2 conserva ocho ramas viejas
—`restos-fase-0`, `navbar-y-migas`, `dashboard-mis-zonas`, `guardado-parcial`,
`aviso-reapertura`, `auditoria-pendientes`, `auditoria/correcciones-tecnicas` y
`feat/matriz-valoracion-territorial`—; cinco de ellas ni siquiera se subieron.
Comprobado una por una con `git merge-base --is-ancestor <rama> main`: **las
ocho son antepasadas de `main` y ninguna tiene un solo commit que `main` no
tenga**. Son punteros a trabajo ya fusionado, no trabajo perdido. Si alguna vez
hace falta el detalle de una, está en el historial de `main` y en su entrada de
§3.

En `main`, comprobar que la suite da **632 tests** antes de tocar nada. Si da
menos, algo no llegó; si fallan unos 57 de golpe, faltó el `npm run build`
(ver §2).

Si `php artisan test` muere con `Out of memory` o «el archivo de paginación es
demasiado pequeño», **no es el código**: es el *commit* de Windows, con RAM
física libre de sobra. Se sortea partiendo la suite, que baja el pico por
proceso y pasa entera:

```bash
php artisan test tests/Unit      # 85
php artisan test tests/Feature   # 547
```

**Los 632 se comprobaron así sobre `main` en `d0e03a2`, el 13 de agosto**: 85
en `Unit` (628 aserciones, 2,6 s) y 547 en `Feature` (3345 aserciones, 61,5 s).
No es una cifra copiada del último merge, es una ejecución sobre lo que hay
subido en `origin/main`.

*(Aquí había un segundo bloque con un `php artisan test` a secas, colgando bajo
el párrafo que explica cómo partir la suite **porque el comando entero muere**.
Contradecía a la línea anterior; se quita.)*

*(Historial de esta cifra, para que quede rastro de cuántas veces ha mentido:
decía 394 cuando `permisos-y-navegacion` ya declaraba 444. Luego 480 en
`frecuentacion`, 483 con `volver-a-la-zona`, y ahí se quedó mientras `main`
llegaba a 494 con la Parte A del dashboard. Luego 524, con la Parte B fusionada
-merge `d65604b`-, y otra vez se quedó atrás mientras `main` pasaba por 525.
Luego 553, con `resumen-lista` fusionada -merge `3516dde`-. Luego 576 con
`fundacion-visual` -merge `f743a60`-, y ahí volvió a quedarse mientras `main`
pasaba por 585 -`restos-fase-0`- y 608 -`navbar-y-migas`-. Ahora **632**, con
la Fase 2 fusionada.)*

**Y hay una cifra más que este documento nunca ha llevado y conviene que
lleve:** cuánto sale la aplicación en pantalla. Desde `fundacion-visual`, el
contenedor mide **1440 px** en un monitor de 1920 —antes 1280—, el fondo es
`#F8FAFC` y la tarjeta lleva `border-gray-200/80 rounded-xl shadow-sm`. Si algo
de eso no cuadra al abrir la aplicación, mira `tailwind.config.js` y
`resources/views/components/contenedor.blade.php`: los dos deciden por todas
las vistas a la vez.

**Este número hay que actualizarlo cada vez que se fusione algo**, o vuelve a
mentir como acaba de hacerlo. Es el mismo tipo de deriva que esta sesión
encontró varias veces en el propio código: documentación que se quedó atrás sin
que nada fallara.
