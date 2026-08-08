# Estado del proyecto — traspaso entre máquinas

**Fecha:** 7 de agosto de 2026 (actualizado al terminar `guardado-parcial`)
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
ponderados hasta un resultado.

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

#### Un aviso pendiente, sin arreglar

Los mensajes de validación salen **en inglés y con el nombre de columna crudo**:
«The ep cambios tiempo field is required». Es de siempre —Laravel no trae
traducciones y la app no define `lang/es`—, pero antes casi no se veía: el
formulario mandaba todos los campos y el error era raro. Ahora «confirmar
cuando falta algo» es un camino normal, así que ese mensaje pasa a ser
frecuente. Arreglarlo es traducir `validation.php` entero y poner
`app.locale = es`; no entraba en esta rama.

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

### Deuda conocida, no de esta rama

El mensaje de formulario bloqueado dice «Solo el Jefe de Zona puede reabrir o
editar una matriz validada» **también cuando la matriz no está validada** y el
bloqueo es por rol —el caso del admin sobre un borrador—. Está igual en los
formularios de FIT y Percepción desde antes; con Irritación son tres. No es
grave, pero le dice al admin algo que no es cierto.

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

### Lo que sigue sin verificarse: la suite sobre PostgreSQL

Distinto asunto, y este queda abierto. Ejecutar la suite contra Postgres da 37
errores y 89 fallos, **pero cada test que se probó pasa en aislado**. Es un
problema de aislamiento entre tests, no de la aplicación: con SQLite
`:memory:` cada test arranca con una base genuinamente nueva, y contra una base
persistente eso deja de ser cierto.

No se llegó a la causa exacta. Lo que se sabe: en el segundo test y siguientes
la petición se rechaza antes de escribir, sin dejar ningún error SQL en el log,
y la base queda limpia entre tests (el rollback sí funciona).

Consecuencia práctica: **la suite no sirve hoy como red contra regresiones
específicas de Postgres.** Merece la pena arreglarlo antes de la próxima matriz,
porque es el único sitio donde se cazarían.

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

1. **Fusionar `guardado-parcial`**, en cuanto sus tres migraciones se hayan
   probado contra un PostgreSQL de verdad. El código está terminado y revisado.
2. ~~**Vistas de admin**~~ — hecho. El panel da cifras y avisa de las zonas sin
   jefe, y las dos listas tienen buscador, filtro por rol y las columnas que
   faltaban. Suite: **222 tests**. Quedan fuera, a propósito, los formularios
   de alta y edición (`users/form`, `lugares/form`, `zonas/form`): comparten
   los defectos de tipografía pero se usan de forma puntual.
3. **Dos matrices sin implementar**: Índice Espacial de Frecuentación e Índice
   de Concentración. Los ficheros están en `~/Downloads/fwdmatrices` de esta
   máquina, ya analizados. **Ninguna de las dos es un formulario de criterios**,
   y las dos están bloqueadas por algo que no es técnico:

   - **Frecuentación** es una lista variable de sitios con dos cifras cada uno;
     el índice es la suma de sus cocientes. Además su hoja original divide todas
     las filas por el ST de la *primera*, y tiene una celda «Superficie
     Territorial = 1» que no usa ninguna fórmula: **hay que aclarar la fórmula
     con el autor del instrumento antes de implementarla.**
   - **Concentración** cuenta recursos por categoría/tipo/subtipo. Se solapa con
     el módulo de Inventario que ya existe: o se profundiza su taxonomía —hoy
     son dos niveles y ocho categorías, frente a los tres niveles y ~77 subtipos
     del instrumento— y el índice se calcula solo, o es un formulario aparte que
     recuenta lo que ya está registrado. **Es una decisión de producto, no
     técnica.**
4. **Migrar FIT, FET, Percepción y Potencialidad** a los componentes de criterio
   nuevos (`criterio-escala`, `criterio-pildoras`). Siguen usando desplegables
   mientras Paisaje y Valoración Territorial ya usan tarjetas. Sin plan escrito.
5. **«Reabrir» una matriz validada**: está en el diseño, sin implementar.

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

Y para retomar el trabajo en curso:

```bash
git checkout guardado-parcial
```

Comprobar que la suite da **209 tests** antes de tocar nada. Si da menos, algo
no llegó; si fallan unos 57 de golpe, faltó el `npm run build` (ver §2).
