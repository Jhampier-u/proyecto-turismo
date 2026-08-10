# Auditoría técnica — UDAExplore / Gestión Turística

**Fecha:** 6 de agosto de 2026
**Alcance:** Laravel 12.39.0, PHP 8.2, PostgreSQL en producción (Render), Docker.
~1.400 líneas de controladores, 57 vistas Blade, 15 migraciones, 21 commits.
**Método:** análisis estático del código. No fue posible ejecutar la suite de tests
(no hay PHP ni `vendor/` instalados en la máquina de auditoría).

---

> **Estado:** corregidos los cuatro críticos (C1–C4), los altos A2–A8, los 16
> medios (M1–M16) y los 9 bajos (B1–B9). Además se actualizaron las dependencias,
> que acumulaban 31 avisos de seguridad.
>
> **A1 sigue pendiente.** El refactor de código está hecho y verificado, pero el
> despliegue mantiene `FILESYSTEM_DISK=public`: mientras no se dé de alta un
> bucket y se rellenen las variables `AWS_*`, **las fotos se siguen perdiendo en
> cada redespliegue**. Ver el procedimiento en el README.
>
> **Suite verificada en verde: 67 tests, 169 aserciones** (PHP 8.2, PHPUnit 11.5).
> Las migraciones nuevas se probaron además contra **PostgreSQL 16 real**, no solo
> contra el SQLite de los tests, y se verificó el esquema resultante
> (`confdeltype = n` en las claves foráneas, `unique` presente en las cuatro tablas).
> Un `migrate:fresh --seed` en modo producción deja únicamente el administrador
> configurado por variables de entorno y cero usuarios de demostración.
>
> Se comprobó también que los tests detectan los fallos: al retirar el middleware
> `PerteneceAZona`, 4 de los 7 tests de autorización fallan.
>
> Se verificó además el arranque real de la imagen Docker de producción: las 15
> migraciones se aplican, las clases de paginación sobreviven a la purga de
> Tailwind (M14), el seeder puede ejecutarse dos veces sin duplicar catálogos (M4)
> y ni `Documentación/` ni `entregables/` entran ya en la imagen (B1).
>
> La ruta de almacenamiento externo se probó de extremo a extremo contra un
> servidor S3 real (MinIO): el mismo código produce `/storage/…` con el disco
> local y la URL del bucket con `FILESYSTEM_DISK=s3`. Queda solo configurarlo.
>
> **Pendiente:** A1 (dar de alta el bucket y definir las variables `AWS_*`),
> rotar la contraseña del administrador en la instancia desplegada y rellenar
> las credenciales SMTP.
>
> Falta **rotar manualmente la contraseña del administrador en la instancia ya
> desplegada**: el arreglo del seeder protege despliegues futuros, no el actual.
> El resto de hallazgos altos, medios y bajos siguen pendientes.

> ## Verificación 2026-08-09 — contra código real, no contra este texto
>
> El bloque de arriba es el estado que se escribió el 6 de agosto. Desde
> entonces el código siguió moviéndose (nueve matrices en vez de cinco, un
> refactor completo de los controladores de evaluación) y ese texto dejó de
> actualizarse al mismo ritmo. Esta pasada verifica **cada uno de los 37
> hallazgos** contra el código y la suite actual, uno por uno, con una
> petición de test donde el hallazgo es de permisos — no releyendo el `if`.
>
> **Resultado: no hay ningún hallazgo de seguridad vivo.** C1–C4 y A1–A8
> están resueltos en código, con test que lo sujeta, excepto A1 (y el
> equivalente de correo, M16), que son despliegue, no código: el refactor de
> almacenamiento está hecho y probado, pero `render.yaml` sigue con
> `FILESYSTEM_DISK=public` y sin bucket dado de alta. Detalle y evidencia en
> cada sección de abajo, marcado como **Estado:**.
>
> **El caso que motivó esta revisión —A8— sí estaba obsoleto**, pero al
> revés de lo que parecía: la sección de A8 nunca se marcó como resuelta,
> aunque el resumen de arriba ya decía "corregidos ... los altos A2–A8" desde
> el 6 de agosto. Quien leyera solo la sección de A8 (como se lee un hallazgo
> por su número, no el resumen entero) se encontraba una `Consecuencia:` y
> una `Corrección:` sin ninguna nota de que ya estaba hecho. Ver A8 abajo.
>
> **Corrección al recuento:** "10 altos" (línea de abajo) nunca coincidió con
> los hallazgos documentados — solo hay A1–A8, ocho. Se corrige a 8 aquí y en
> el recuento de la sección siguiente.
>
> Un hallazgo (M6, la persistencia de `campos_activos` antes de validar)
> estaba resuelto en código pero sin ningún test que lo sujetara: se añadió
> uno. Lo mismo con M9 (imagen nueva + quitar imagen a la vez) y M11 (casts a
> float). Los tres se verificaron reintroduciendo temporalmente el bug
> original y confirmando que el test nuevo lo detecta, no solo que pasa en
> verde. Detalle en A1/M6/M9/M11 abajo.
>
> Suite: **365 tests, todos en verde** (362 ya existentes + 3 nuevos de esta
> verificación). Commit y fecha exactos, al final del documento.

## Resumen ejecutivo

El proyecto está bien construido en lo que respecta a **higiene básica de Laravel**:
no hay inyección SQL, no hay XSS, todos los formularios llevan `@csrf`, las contraseñas
se hashean correctamente y el framework está en su última versión. Los cálculos
ponderados de las matrices (FIT, FET, Percepción, Potencialidad, VTT) son
aritméticamente consistentes con las matrices de origen.

El problema no está en la escritura del código, sino en **el modelo de autorización y
en el despliegue**. Hay tres fallas que comprometen el sistema en producción hoy:

1. Cualquiera puede entrar como administrador con credenciales conocidas.
2. Cualquier usuario autenticado puede leer, editar y borrar los datos de cualquier zona.
3. Las fotos que suben los usuarios se pierden en cada redespliegue.

A esas tres se suma un **XSS almacenado** que permite a un estudiante ejecutar código
en el navegador del administrador.

Recuento: **4 críticos, 8 altos, 16 medios, 9 bajos** (corregido de "10 altos":
ver la nota de verificación de 2026-08-09 arriba — nunca hubo más de ocho
documentados).

---

## CRÍTICOS

### C1 — Credenciales de administrador conocidas, activas en producción

> **Estado: RESUELTO.** `DatabaseSeeder` ya no crea usuarios con contraseña
> fija: llama a `AdminSeeder` (lee `ADMIN_EMAIL`/`ADMIN_PASSWORD` de config, no
> crea nada si faltan, no toca la cuenta si ya existe) y a `DemoSeeder` solo
> bajo `app()->environment('local')`. Ver `database/seeders/DatabaseSeeder.php`,
> `database/seeders/AdminSeeder.php`. Sujeto por
> `tests/Feature/SeedersTest.php` (4 tests: cero usuarios fuera de local, el
> admin sale de la config, no se recrea si ya existe).
> Pendiente real, no de código: rotar la contraseña en la instancia ya
> desplegada con las credenciales viejas (esto no lo arregla ningún commit).

**Dónde (histórico, ya no vigente):** `database/seeders/DatabaseSeeder.php:19-41`, `docker/entrypoint.sh:34-39`, `render.yaml`

El seeder crea tres usuarios con la contraseña literal `password`:

```php
User::create([
    'name'     => 'Admin Principal',
    'email'    => 'admin@turismo.com',
    'password' => 'password',
    'role_id'  => 1, // Admin
```

Y el entrypoint de Docker lo ejecuta automáticamente en producción:

```bash
if [ "${USERS_COUNT}" = "0" ]; then
    echo "▶ DB vacía, ejecutando seeders iniciales..."
    php artisan db:seed --force || true
fi
```

Además `DatabaseSeeder.php:44` incluye producción de forma explícita:
`if (app()->environment(['local', 'production']))`.

**Consecuencia:** cualquiera que vea este repositorio entra a la aplicación desplegada
como administrador con `admin@turismo.com` / `password`. Control total del sistema.

**Corrección:** separar el seeder de datos demo del de catálogos. `SystemSeeder`
(regiones, provincias, categorías) sí debe correr en producción; los usuarios de prueba,
solo en `local`. Para el admin inicial en producción, un comando artisan que lea la
contraseña de una variable de entorno. Y **cambiar la contraseña del admin desplegado hoy**.

---

### C2 — IDOR: cualquier usuario accede a cualquier zona

> **Estado: RESUELTO.** Middleware `zona` → `app/Http/Middleware/PerteneceAZona.php`,
> registrado en `bootstrap/app.php:17` y aplicado a todo el grupo
> `operativo/zona/{zona}` en `routes/web.php:57`. Comprueba jefe o miembro de
> equipo de *esa* zona; al admin lo deja consultar (métodos seguros) pero
> aborta 403 en cualquier escritura. La confirmación ya no depende de
> `role_id == 2` sino de `$user->esJefe()` combinado con el propio middleware
> (que ya exige ser el jefe de *esa* zona, no de cualquiera).
> Sujeto por petición real, no por lectura del código:
> `tests/Feature/AutorizacionZonaTest.php::test_un_miembro_de_equipo_no_accede_a_una_zona_ajena`,
> `::test_un_jefe_no_accede_a_una_zona_que_no_dirige`,
> `::test_un_jefe_no_puede_confirmar_evaluaciones_de_zonas_ajenas` (POST con
> `accion_estado=confirmado` a una zona ajena → 403),
> `::test_el_admin_puede_consultar_pero_no_escribir`.

**Dónde (histórico, ya no vigente):** `app/Http/Middleware/isPersonal.php:19` + todos los controladores de `app/Http/Controllers/Operativo/`

El middleware `personal` solo comprueba que el usuario tenga algún rol:

```php
if (!auth()->check() || !in_array(auth()->user()->role_id, [1, 2, 3])) {
    abort(403, 'No tienes permisos operativos en esta zona.');
}
```

Nunca verifica que el usuario esté asignado a la zona de la URL. Después, cada
controlador hace `Zona::findOrFail($zonaId)` sin más comprobación
(`EvaluacionFetController.php:15`, `EvaluacionFitController.php:15`,
`EvaluacionPercepcionController.php:59`, `EvaluacionPotencialidadController.php:218`,
`VttController.php:16`).

`DashboardController` sí filtra las zonas que se muestran (`zonasComoJefe()` /
`zonasComoEquipo()`), pero es una restricción puramente visual: escribir la URL a mano la salta.

**Consecuencia:** un estudiante asignado a la zona 5 abre `/operativo/zona/9/evaluacion-fit`
y lee y edita las evaluaciones de la zona 9. Peor: un Jefe de cualquier zona puede
**confirmar y cerrar** evaluaciones de zonas ajenas, porque el único requisito para
confirmar es `role_id == 2` (`EvaluacionFetController.php:58`), nunca "ser el jefe de *esta* zona".

**Corrección:** un middleware `perteneceAZona` aplicado al grupo `operativo/zona/{zona}`:

```php
$zona = Zona::findOrFail($request->route('zona'));
$user = $request->user();
$permitido = $user->role_id === 1
    || $zona->jefe_user_id === $user->id
    || $zona->equipo()->where('user_id', $user->id)->exists();
abort_unless($permitido, 403);
```

Y cambiar los checks de confirmación de `role_id == 2` a `$zona->jefe_user_id === $user->id`.

---

### C3 — Borrado y edición de inventarios sin ninguna restricción

> **Estado: RESUELTO.** `destroy()`, `show()`, `edit()` y `update()` en
> `app/Http/Controllers/Operativo/InventarioController.php` escopan con
> `Inventario::where('zona_id', $zonaId)->findOrFail($inventarioId)`.
> Sujeto por petición con el ID de un inventario ajeno, no por lectura:
> `tests/Feature/AutorizacionZonaTest.php::test_no_se_puede_borrar_un_inventario_de_otra_zona`
> (`DELETE` a un inventario de otra zona → 404, `assertDatabaseHas` confirma
> que sigue existiendo) y `::test_no_se_puede_ver_un_inventario_de_otra_zona`.

**Dónde (histórico, ya no vigente):** `app/Http/Controllers/Operativo/InventarioController.php:100-111`, `:113-121`, `:123-139`, `:141-191`

El parámetro `$zonaId` de la ruta anidada se ignora por completo:

```php
public function destroy($zonaId, $inventarioId)
{
    $inventario = Inventario::findOrFail($inventarioId);

    foreach ($inventario->imagenes as $img) {
        Storage::disk('public')->delete($img->ruta_archivo);
    }

    $inventario->delete();
```

No hay `where('zona_id', $zonaId)`, ni verificación de `creado_por_user_id`, ni de rol.

**Consecuencia:** cualquier usuario operativo envía
`DELETE /operativo/zona/SU_ZONA/inventarios/123` con el ID de una ficha de otra zona
y la borra permanentemente, **junto con sus fotos del disco**. Combinado con C2,
un solo usuario puede vaciar el inventario completo del sistema.

**Corrección:** en los cuatro métodos, escopar la consulta:
`Inventario::where('zona_id', $zonaId)->findOrFail($inventarioId)`.
Idealmente usar *scoped route bindings* de Laravel:
`Route::resource('inventarios', ...)->scoped()`.

---

### C4 — XSS almacenado: un estudiante ejecuta código como administrador

> **Estado: RESUELTO.** El nombre ya no viaja dentro de un atributo `on*`.
> `resources/views/admin/users/index.blade.php` lo pone en `data-nombre` y lo
> lee un listener JS (`form.dataset.nombre`); `admin/lugares/index.blade.php:90-93`
> usa `Js::from()`, que codifica correctamente para contexto JavaScript.
> Sujeto por `tests/Feature/AdminUsuariosTest.php::test_el_nombre_de_usuario_no_puede_inyectar_javascript`:
> crea un usuario con nombre `'); alert(1); //`, verifica que nunca aparece
> sin escapar, que aparece dentro de `data-nombre="&#039;); alert(1); //"` y
> que ya no queda ningún `onsubmit=` en la página.

**Dónde (histórico, ya no vigente):** `resources/views/admin/users/index.blade.php:75`

```blade
<form action="{{ route('admin.users.destroy', $user) }}" method="POST"
      onsubmit="return confirm('¿Estás seguro de que deseas eliminar a {{ $user->name }}? ...');">
```

Aquí `{{ }}` **no** protege. Blade escapa la comilla simple a `&#039;`, pero el valor está
dentro de un **atributo de evento HTML**: el parser del navegador decodifica las entidades
*antes* de entregar el contenido al motor de JavaScript. `&#039;` vuelve a ser `'` y rompe
el literal de la cadena.

Cualquier usuario autenticado puede cambiar su propio nombre sin restricciones
(`app/Http/Requests/ProfileUpdateRequest.php:19` → solo `required|string|max:255`).

**Consecuencia:** un usuario de rol 3 se pone como nombre

```
'); fetch('/admin/users/1',{method:'DELETE'}); //
```

y ese código se ejecuta **en el navegador del administrador, con su sesión**, en cuanto
abre `/admin/users`. Escalada de privilegios completa: robo de sesión, creación de un
admin nuevo, borrado masivo.

Los `confirm()` de `admin/lugares/index.blade.php:46` y `admin/zonas/index.blade.php:68`
usan texto constante, así que no son explotables.

**Corrección:** sacar el dato del atributo. Por ejemplo con `data-*` y un listener:

```blade
<form ... data-nombre="{{ $user->name }}" class="js-eliminar-usuario">
```

o usar la directiva `@js()` de Laravel, que codifica correctamente para contexto JavaScript.

---

## ALTOS

### A1 — Las fotos subidas se pierden en cada redespliegue

> **Estado: VIVO (código resuelto, despliegue pendiente).** El código ya no
> tiene ninguna ruta fija a `disk('public')`: todo pasa por
> `config('filesystems.default')` / `Storage::url()`, así que cambiar
> `FILESYSTEM_DISK=s3` en el entorno basta, sin tocar código. Sujeto por
> `tests/Feature/AlmacenamientoImagenesTest.php` (fake del disco *por
> defecto*, no de `'public'` fijo; sube, borra, sirve vía `Storage::url()`).
> Pero **`render.yaml` sigue con `FILESYSTEM_DISK=public`** y las variables
> `AWS_*` en `sync: false` sin valor: hasta que alguien dé de alta un bucket y
> las rellene en el dashboard de Render, las fotos siguen perdiéndose en cada
> redespliegue. Esto no lo cierra ningún commit — es un paso de despliegue.

**Dónde (histórico, ya no vigente):** `render.yaml` (bloque `disk:` comentado), `Dockerfile`, `docker/entrypoint.sh:14-21`

`render.yaml` lo dice explícitamente: *"discos persistentes NO están disponibles en el
plan free"*. El entrypoint prevé `/var/data` pero ese directorio no existe en el plan free,
así que `FILESYSTEM_DISK=public` escribe dentro del contenedor, que es efímero.

**Consecuencia:** todas las fotos de zonas e inventarios desaparecen en cada deploy,
reinicio o suspensión por inactividad (el plan free de Render duerme los servicios).
Las filas de `inventario_imagenes` quedan apuntando a archivos que ya no existen.

**Corrección:** almacenamiento externo (Cloudinary tiene plan gratuito generoso, o S3 /
Backblaze B2). Es un cambio de driver de filesystem, no de código de aplicación.
Alternativa: subir al plan Starter de Render y descomentar el bloque `disk:`.

---

### A2 — La suite de tests no arranca: `User` no usa `HasFactory`

> **Estado: RESUELTO.** `app/Models/User.php` usa `HasFactory, Notifiable`.
> `Inventario` (el otro caso citado) también: `app/Models/Inventario.php` usa
> `HasFactory` y `database/factories/InventarioFactory.php` existe. No hace
> falta un test dedicado: la mayoría de los 365 tests de la suite llaman a
> `User::factory()->create()` en su `setUp()`, así que si el trait se
> quitara la suite entera fallaría en cadena — es la red de seguridad más
> ancha posible.

**Dónde (histórico, ya no vigente):** `app/Models/User.php:11`

```php
class User extends Authenticatable
{
    use Notifiable;
```

Falta `HasFactory`. `database/factories/UserFactory.php` existe, pero sin el trait el
método estático `User::factory()` no existe. Lo invocan 19 tests
(`tests/Feature/ProfileTest.php`, `tests/Feature/Auth/*`).

**Consecuencia:** `php artisan test` falla en cadena con
`BadMethodCallException: Call to undefined method App\Models\User::factory()`.
Toda la cobertura existente está inutilizada. (Lo mismo con `Inventario` e `InventarioFactory`.)

**Corrección:** `use HasFactory, Notifiable;` más el `use Illuminate\Database\Eloquent\Factories\HasFactory;`.

---

### A3 — Cobertura de tests nula sobre la lógica de negocio

> **Estado: RESUELTO** (esta nota de "parcialmente cubierto" ya estaba
> desactualizada: quedó escrita cuando solo existían `AutorizacionZonaTest`,
> `AdminUsuariosTest` y `SeedersTest`, y no se tocó cuando el resto llegó).
> Hoy hay 34 ficheros de test, 365 tests. Los cinco cálculos ponderados que
> esta nota daba como pendientes tienen test dedicado:
> `EvaluacionesTest::test_fit_promedia_por_bloque_y_no_por_campo` (FIT),
> `::test_fet_pondera_demanda_superestructura_e_imagen` (FET),
> `::test_el_vtt_se_guarda_al_confirmar_y_no_al_consultarlo` (VTT),
> `::test_percepcion_normaliza_el_total_entre_cero_y_uno` (Percepción),
> `PotencialidadCalculoTest` (20 tests, Potencialidad). Además las cinco
> matrices añadidas después de esta auditoría (Paisaje, Valoración
> Territorial, Irritación, Concentración, Involucrados) tienen su propio
> fichero de test.

**Dónde:** `tests/`

Solo existen los tests por defecto de Breeze (auth y perfil) — y están rotos por A2.
No hay un solo test sobre lo que hace único a este sistema: los cálculos ponderados
FIT/FET/VTT/Potencialidad/Percepción, el flujo borrador→confirmado, ni las reglas de acceso.

**Consecuencia:** cualquier cambio en las fórmulas puede romper resultados sin que nadie
lo note. Las matrices son el corazón del proyecto y no tienen red de seguridad.

**Corrección prioritaria:** tests de los cinco cálculos ponderados (son funciones puras,
fáciles de testear) y tests de autorización que verifiquen que un usuario de la zona A
recibe 403 en la zona B — estos últimos servirían además como verificación de la corrección de C2.

---

### A4 — Cuatro modelos apuntan a tablas que no existen

> **Estado: RESUELTO.** Los cuatro modelos (`Evaluacion`, `EvaluacionValor`,
> `MatrizCriterio`, `MatrizVariable`) ya no existen en `app/Models/`.
> `Zona::evaluaciones()` tampoco existe. El `down()` de
> `2025_12_02_045117_create_turismo_schema.php` ya no dropea esas tablas
> fantasma. Verificado por ausencia (grep sobre `app/` y las migraciones): no
> hace falta test, es código que ya no está.

**Dónde (histórico, ya no vigente):** `app/Models/Evaluacion.php:9`, `EvaluacionValor.php:9`, `MatrizCriterio.php:9`, `MatrizVariable.php:9`

Ninguna migración crea `evaluaciones`, `evaluacion_valores`, `matriz_criterios` ni
`matriz_variables`. Sin embargo el `down()` de
`database/migrations/2025_12_02_045117_create_turismo_schema.php:165-168` las dropea
— evidencia de que su creación se eliminó del `up()` y quedó el residuo.

Peor: `app/Models/Zona.php:14` mantiene viva la relación
`public function evaluaciones() { return $this->hasMany(Evaluacion::class); }`.

**Consecuencia:** hoy es código muerto, pero un `with('evaluaciones')` futuro produce
`SQLSTATE[42P01] relation "evaluaciones" does not exist`. Es una trampa esperando.

**Corrección:** borrar los cuatro modelos, la relación de `Zona` y las cuatro líneas del `down()`.

---

### A5 — Falta `unique` en `zona_id` de cuatro tablas tratadas como 1:1

> **Estado: RESUELTO.** Migración
> `database/migrations/2026_08_06_000001_add_unique_zona_id_to_evaluaciones.php`
> consolida duplicados existentes (conserva la fila de mayor id) y añade
> `unique('zona_id')` a las cuatro tablas exactas de este hallazgo. Sujeto por
> `tests/Feature/IntegridadDatosTest.php::test_no_se_admiten_dos_filas_para_la_misma_zona`
> (data provider con las cuatro tablas: inserta dos filas con el mismo
> `zona_id` y espera `QueryException` en la segunda).

**Dónde (histórico, ya no vigente):** `evaluaciones_fit`, `evaluaciones_fet`, `evaluaciones_potencialidad`, `potencialidad_campos_activos`

Todo el código asume una fila por zona (`firstOrNew(['zona_id' => $zonaId])`,
`updateOrCreate(['zona_id' => $zonaId], ...)`), pero la base de datos no lo garantiza.
Contraste revelador: `vocacion_turistica_territorio` y `evaluaciones_percepcion` **sí**
tienen el `unique` — media base protegida y media no.

**Consecuencia:** dos peticiones concurrentes (doble clic, o el jefe y el estudiante
guardando a la vez) crean filas duplicadas. A partir de ahí `first()` devuelve una
arbitraria y los resultados mostrados dependen de cuál se lea.

**Corrección:** migración que añada `unique('zona_id')` a las cuatro tablas,
previa limpieza de duplicados si los hubiera.

---

### A6 — Borrar un usuario con actividad produce error 500

> **Estado: RESUELTO.** Migración
> `database/migrations/2026_08_06_000002_fix_user_foreign_keys_on_delete.php`
> pasa `inventarios.creado_por_user_id`, `evaluaciones_fit/fet/potencialidad.user_id`
> y `vocacion_turistica_territorio.user_id` a `nullOnDelete()`.
> `Admin/UserController::destroy()` además tiene try/catch sobre
> `QueryException` como defensa adicional. `ProfileController::destroy()`
> invirtió el orden: borra primero, y usa `Auth::forgetUser()` en vez de
> `Auth::logout()` (que resucitaría al usuario ya borrado al guardar su
> "remember token"). Sujeto por
> `tests/Feature/IntegridadDatosTest.php::test_borrar_un_usuario_con_actividad_no_falla_y_conserva_los_datos`,
> `::test_el_admin_recibe_un_mensaje_en_vez_de_un_error_500`,
> `::test_borrar_la_propia_cuenta_no_resucita_al_usuario`.

**Dónde (histórico, ya no vigente):** `app/Http/Controllers/Admin/UserController.php:69-79`, `app/Http/Controllers/ProfileController.php:43-59`

Las claves foráneas `inventarios.creado_por_user_id`, `evaluaciones_fit.user_id`,
`evaluaciones_fet.user_id`, `evaluaciones_potencialidad.user_id` y
`vocacion_turistica_territorio.user_id` se declararon `constrained('users')` **sin**
`nullOnDelete()`, es decir con RESTRICT. `$user->delete()` no tiene try/catch.

(`evaluaciones_percepcion` sí usa `nullOnDelete()`, confirmando que es un descuido.)

**Consecuencia:** el admin borra a un jefe que guardó una evaluación y recibe una
pantalla de error 500 sin explicación.

Y en `ProfileController::destroy` el orden agrava el problema:

```php
Auth::logout();      // línea 51
$user->delete();     // línea 53  ← si falla, ya está deslogueado
```

Si el delete falla, el usuario queda fuera de sesión, su cuenta sigue existiendo, y cree
que se borró.

**Corrección:** migración que cambie esas FKs a `nullOnDelete()`, y try/catch con mensaje
claro en ambos controladores. Invertir el orden en `ProfileController`.

---

### A7 — `accion_estado` llega a la base de datos sin validar

> **Estado: RESUELTO.** Los cuatro controladores originales (y todos los que
> se añadieron después: Paisaje, Valoración Territorial, Irritación,
> Concentración) ahora heredan de `EvaluacionZonaController`, cuyo
> `update()` centraliza `$request->validate(['accion_estado' => 'nullable|in:borrador,confirmado'])`
> antes de decidir el estado — la validación que faltaba, en un solo sitio en
> vez de cuatro copias. Sujeto por
> `tests/Feature/EvaluacionesTest.php::test_un_accion_estado_invalido_se_rechaza_con_validacion`
> (envía `accion_estado => 'no-existe'`, espera `assertSessionHasErrors('accion_estado')`
> y `assertDatabaseCount('evaluaciones_fet', 0)` — antes esto llegaba a la
> columna enum y tiraba 500).

**Dónde (histórico, ya no vigente):** `EvaluacionFetController.php:58-60`, `EvaluacionFitController.php:77-79`, `EvaluacionPercepcionController.php:87-89`, `EvaluacionPotencialidadController.php:281-283`

```php
$estado = ($user->role_id == 2)
    ? $request->input('accion_estado', 'borrador')
    : 'borrador';
```

El valor nunca pasa por `validate()` con `in:borrador,confirmado`.

**Consecuencia:** en `evaluaciones_fit/fet/potencialidad` la columna es un enum, así que
un valor arbitrario provoca `QueryException` → error 500. En `evaluaciones_percepcion`
la columna es `string(20)`, así que se persiste cualquier basura; y como el bloqueo de
solo-lectura compara `=== 'confirmado'`, un estado corrupto (por ejemplo `"Confirmado"`
con mayúscula) rompe silenciosamente el flujo de validación jefe/equipo.

**Corrección:** añadir `'accion_estado' => 'nullable|in:borrador,confirmado'` a las reglas
de validación en los cuatro controladores.

---

### A8 — Un admin degrada evaluaciones confirmadas a borrador

> **Estado: RESUELTO** (era el hallazgo que motivó esta verificación de
> 2026-08-09 — el resumen de arriba ya decía "corregidos ... los altos
> A2–A8" desde el 6 de agosto, pero esta sección nunca se marcó, así que
> leída sola parecía un hallazgo abierto). Dos capas independientes lo
> cierran, no solo una:
> 1. `app/Http/Middleware/PerteneceAZona.php:34-41` — al admin lo deja pasar
>    en métodos seguros (`GET`) y aborta 403 en cualquier escritura sobre
>    *cualquier* ruta `operativo/zona/{zona}/...`, evaluaciones incluidas. El
>    admin no llega a `update()` en absoluto.
> 2. Aunque llegara: `EvaluacionZonaController::update()` fija
>    `$estado = $user->esJefe() ? ... : 'borrador'` — solo el Jefe puede
>    escribir `confirmado`; y el bloqueo de escritura sobre una evaluación ya
>    confirmada (`$user->esEquipo()`) tampoco distingue admin porque el admin
>    nunca llega aquí.
> Sujeto por petición real, con roles admin y jefe intentándolo:
> `tests/Feature/AutorizacionZonaTest.php::test_el_admin_puede_consultar_pero_no_escribir`
> (POST con `accion_estado=confirmado` como admin → 403) y
> `tests/Feature/ReabrirMatrizTest.php::test_el_admin_no_puede_reabrir_una_matriz_confirmada`
> (confirma como jefe, luego el admin intenta reabrir con `accion_estado=borrador`
> → 403, y la evaluación sigue `confirmado` con los valores del jefe).

**Dónde (histórico, ya no vigente):** `EvaluacionFetController.php:26` y equivalentes en los otros tres controladores

El bloqueo de edición solo cubre al rol 3:

```php
if ($evaluacionActual && $evaluacionActual->estado === 'confirmado' && $user->role_id == 3) {
```

El admin (rol 1) pasa el middleware `personal` (que lo incluye) y no está bloqueado.
Luego `$estado = ($user->role_id == 2) ? ... : 'borrador'` fuerza `borrador` para
cualquier rol distinto de 2.

**Consecuencia:** un admin que guarda una evaluación ya validada la reabre como borrador
y además sobrescribe `user_id` con el suyo. Se pierde la trazabilidad de quién validó.

**Corrección:** bloquear la edición de cualquier evaluación confirmada salvo una acción
explícita de "reabrir", y no sobrescribir `user_id` cuando quien guarda no es el autor.

---

## MEDIOS

| # | Hallazgo | Ubicación (histórica) | Estado (verificado 2026-08-09) |
|---|---|---|---|
| M1 | Endpoint `/__bootstrap` ejecuta `migrate` + `db:seed` desde el navegador, con token en query string y comparación `!==`. | `routes/web.php:22-34` | **RESUELTO.** La ruta ya no existe en `routes/web.php`. Sujeto por `tests/Feature/AdminZonasTest.php::test_la_ruta_de_bootstrap_remoto_ya_no_existe` (`GET /__bootstrap?token=...` → 404). |
| M2 | Registro público abierto: cualquiera crea cuentas con `role_id = NULL`. | `routes/auth.php:15-18` | **RESUELTO.** `routes/auth.php` ya no define ninguna ruta `register` (comentario explícito: "el registro público está deshabilitado a propósito"). Sujeto por `tests/Feature/AdminZonasTest.php::test_el_registro_publico_ya_no_existe` (`GET`/`POST /register` → 404). |
| M3 | `VttController` escribe en base de datos en una petición `GET`. | `VttController.php:44-53`, `routes/web.php:88` | **RESUELTO.** `app/Http/Controllers/Operativo/VttController.php` quedó con un único método, `resultadoFinal()`, de solo lectura; la instantánea se guarda al confirmar FIT/FET (`despuesDeGuardar()`), no al abrir la página. Sujeto por `tests/Feature/EvaluacionesTest.php::test_el_vtt_se_guarda_al_confirmar_y_no_al_consultarlo`. |
| M4 | Catálogos sin `unique`: el seeder duplica regiones/provincias/categorías en cada re-ejecución. | `SystemSeeder.php:45,54` y migración principal | **RESUELTO.** Migración `2026_08_06_000003_add_unique_to_catalogos.php` consolida duplicados y añade `unique` a `regiones`, `provincias`, `lugares`, `tipos_propietario`, `categorias_recurso`; `SystemSeeder` reescrito con `idDe()` (busca antes de insertar). Sujeto por `tests/Feature/AdminZonasTest.php` (líneas 127-133: siembra dos veces y compara conteos). |
| M5 | Índices de FK ausentes (PostgreSQL no los crea automáticamente). | migraciones | **RESUELTO, sin test dedicado.** Migración `2026_08_06_000004_add_foreign_key_indexes.php` añade índice a las columnas exactas listadas en el hallazgo. No hay test que confirme el índice en el esquema (requeriría introspección específica del driver); es una mejora de rendimiento, no de corrección funcional, así que su ausencia no se detectaría por un test que fallara — solo por un `EXPLAIN` en producción. |
| M6 | Potencialidad persiste `campos_activos` antes de validar los criterios; `campos[]` sin lista blanca. | `EvaluacionPotencialidadController.php:245-264` | **RESUELTO.** `prepararDatos()` ahora valida `campos.*` contra `Rule::in($this->getAllCampos())` y valida los criterios (`$request->validate($reglas, ...)`) **antes** de llamar a `PotencialidadCamposActivos::updateOrCreate()`. Esta verificación encontró el hallazgo **resuelto pero sin ningún test que lo sujetara** — se añadió `tests/Feature/PotencialidadCalculoTest.php::test_una_validacion_fallida_no_deja_la_configuracion_de_campos_a_medio_cambiar`, verificado además reintroduciendo temporalmente el orden viejo y confirmando que el test nuevo lo detecta. |
| M7 | Fotos de inventario sin límite de cantidad en el array. | `InventarioController.php:58,158` | **RESUELTO.** `store()` y `update()` validan `'fotos' => 'nullable|array|max:15'` / `'nuevas_fotos' => 'nullable|array|max:15'`. Sujeto por `tests/Feature/AlmacenamientoImagenesTest.php::test_no_se_admiten_mas_de_quince_fotos_por_peticion`. |
| M8 | Borrar una zona deja huérfanas las fotos de sus inventarios. | `ZonaController.php:107-114` | **RESUELTO.** `ZonaController::destroy()` recoge las rutas de `InventarioImagen` (y la imagen propia de la zona) antes del `delete()` y las borra del disco después. Sujeto por `tests/Feature/AlmacenamientoImagenesTest.php::test_borrar_una_zona_borra_las_fotos_de_sus_inventarios`. |
| M9 | Imagen nueva + "quitar imagen" a la vez: se guarda el archivo y luego se anula la referencia — huérfano y cambio perdido. | `ZonaController.php:87-99` | **RESUELTO.** El código ahora es `if (hasFile) { borra la vieja; guarda la nueva } elseif (quitar_imagen) { borra; null }` — mutuamente excluyente, gana la imagen nueva (comentario explícito en el código). Esta verificación encontró el hallazgo **resuelto pero sin ningún test que lo sujetara** — se añadió `tests/Feature/AlmacenamientoImagenesTest.php::test_subir_imagen_nueva_y_marcar_quitar_imagen_a_la_vez_conserva_la_nueva`, verificado reintroduciendo temporalmente el orden secuencial viejo y confirmando que el test nuevo falla con él. |
| M10 | `role_id` en `$fillable` de `User`; lo mismo con `user_id`/`estado` en los modelos de evaluación. | `User.php:14` | **PARCIAL.** `role_id` ya **no** está en `$fillable` de `app/Models/User.php` (comentario explícito: "es un campo de privilegio y no debe poder asignarse en masa"); lo asigna `UserController` de forma explícita. `user_id`/`estado` siguen en `$fillable` de los modelos de evaluación porque `EvaluacionZonaController::update()` los necesita para el `updateOrCreate()` centralizado — sigue sin ser explotable hoy (verificado: no hay ningún `$request->all()` en `app/`, `grep` sin resultados), exactamente la misma salvedad que ya hacía el hallazgo original. |
| M11 | Casts numéricos ausentes en los campos `decimal`: PostgreSQL los devuelve como *string*. | modelos de evaluación | **RESUELTO.** Todos los totales calculados (`fit`, `fet`, `percepcion_total`, `fn_total`, `fx_total`, `vtt` y sus componentes) tienen cast `'float'` explícito, con el mismo comentario en cada modelo. Esta verificación encontró el hallazgo **resuelto pero sin test que lo sujetara** (SQLite no expone la diferencia de driver que motivó el hallazgo) — se añadió `tests/Feature/IntegridadDatosTest.php::test_los_totales_calculados_se_castean_a_float`, que fija la declaración del cast en sí (`getCasts()`) en vez de depender de un driver concreto. |
| M12 | `SESSION_SECURE_COOKIE` no definida en `render.yaml`. | `render.yaml`, `config/session.php:172` | **RESUELTO.** `render.yaml` define `SESSION_SECURE_COOKIE: "true"`. Es configuración de despliegue: no hay (ni tiene sentido que haya) test de PHPUnit para un valor de `render.yaml`. |
| M13 | Asignación de jefe/equipo sin validar el rol. | `ZonaController.php:33,74` | **RESUELTO.** `ZonaController::reglas()` usa `Rule::exists('users','id')->where('role_id', ...)` para `jefe_user_id` y `equipo.*`. Sujeto por `tests/Feature/AdminZonasTest.php::test_no_se_puede_nombrar_jefe_a_quien_no_tiene_ese_rol` y `::test_no_se_puede_poner_en_el_equipo_a_un_jefe`. |
| M14 | Paginación sin estilos en producción: `vendor/` no existe en la etapa de build de assets. | `Dockerfile:7-9`, `tailwind.config.js:7` | **RESUELTO** (no cubierto por PHPUnit — es comportamiento de build de Docker). `Dockerfile` ahora tiene una etapa `vendor` (Composer) que copia las vistas de paginación de Laravel a la etapa `assets` antes de `npm run build`, con el razonamiento del hallazgo citado en el propio comentario del Dockerfile. |
| M15 | Clases Tailwind dinámicas (`bg-{{ $color }}-50`) purgadas. | `admin/zonas/potencialidad.blade.php:89-92` | **YA NO APLICA.** El fichero no existe (confirmado: no hay ningún `bg-{{` en `resources/views/`); ya lo decía la propia fila original. |
| M16 | Recuperación de contraseña no funciona: sin `MAIL_*`, cae a `log`/`stderr`. | `render.yaml`, `auth/forgot-password.blade.php` | **VIVO (código listo, despliegue pendiente)**, igual que A1. `render.yaml` ya define `MAIL_MAILER: smtp`, pero `MAIL_HOST`/`MAIL_USERNAME`/`MAIL_PASSWORD` siguen en `sync: false` sin valor: hasta que alguien configure un proveedor SMTP real en el dashboard de Render, el correo sigue sin salir. |

---

## BAJOS

| # | Hallazgo | Ubicación (histórica) | Estado (verificado 2026-08-09) |
|---|---|---|---|
| B1 | 19 MB de archivos no-código en cada imagen Docker: `.dockerignore` no excluye `Documentación/` ni `entregables/`. | `.dockerignore` | **RESUELTO.** `.dockerignore` excluye ahora `Documentación`, `entregables` y `**/node_modules` (con comentario explicando el anclado a la raíz que fallaba antes). |
| B2 | `entregables/node_modules` versionado en git; `.gitignore` con `/node_modules` no cubre subdirectorios. | `.gitignore` | **RESUELTO.** `.gitignore` usa `**/node_modules`. Verificado por ausencia: `git ls-files \| grep entregables/node_modules` no devuelve nada. |
| B3 | Interpolación Blade dentro de JavaScript en `evaluacion_potencialidad/ponderacion.blade.php:352`. | `evaluacion_potencialidad/ponderacion.blade.php:352` | **RESUELTO.** La línea ahora es `label: @json($zona->nombre)`. Verificado leyendo el fichero actual (línea 412) y confirmando que no queda ninguna interpolación Blade cruda dentro de `<script>`. |
| B4 | `env.example` obsoleto (Railway/MySQL) conviviendo con `.env.example` (Render/PostgreSQL). | raíz | **RESUELTO.** Solo existe `.env.example`, alineado con Render/PostgreSQL/SQLite de test; `env.example` (sin punto) ya no existe en el repositorio. |
| B5 | README desactualizado (XAMPP/MySQL) con el README genérico de Laravel a partir de la línea 76. | `README.md` | **RESUELTO**, con una deriva nueva encontrada de paso: el README (130 líneas, sin rastro del stub de Laravel) documenta Docker/PostgreSQL/Render correctamente, pero su primera línea dice "cinco matrices" cuando el sistema ya tiene nueve (se añadieron Paisaje, Valoración Territorial, Irritación, Concentración e Involucrados después de escribir esa frase). No se corrige aquí porque está fuera del alcance de esta auditoría, pero es exactamente el tipo de deriva que motivó esta revisión — se deja constancia para que no se repita el patrón. |
| B6 | Restos y código muerto: `start.txt`, `guardarCampos`, `@tailwindcss/vite` sin usar. | varios | **RESUELTO.** Ninguno de los tres existe ya: no hay `start.txt`, no hay `guardarCampos` en `app/` ni `routes/`, y `package.json` no tiene `@tailwindcss/vite` (solo `@tailwindcss/forms`, que es una dependencia distinta y sí se usa). |
| B7 | Comparaciones de rol inconsistentes (`!== 1` vs `== 2`/`== 3`) y `role_id` sin cast. | middleware y controladores | **RESUELTO.** No queda ninguna comparación cruda de `role_id` en `app/` (verificado por grep); todo pasa por `esAdmin()`/`esJefe()`/`esEquipo()`/`tieneRolOperativo()`, y `User::casts()` incluye `'role_id' => 'integer'`. |
| B8 | Menú móvil sin los enlaces de admin (`hidden sm:flex` solamente). | `layouts/navigation.blade.php:97-102` | **RESUELTO**, sin test (es una diferencia de visibilidad CSS entre los dos bloques de navegación, que un test de backend no puede distinguir: ambos bloques están siempre en el HTML). `layouts/navigation.blade.php` tiene ahora los mismos `x-responsive-nav-link` que el menú de escritorio dentro del bloque `sm:hidden`, con un comentario explícito citando el problema original. |
| B9 | Vistas muertas (`admLogin`, `show.admLogin`, `seleccionar_campos.blade.php`, `dashboard.blade.php`) y `.claude/settings.local.json` versionado. | varios | **RESUELTO.** `admLogin.blade.php` y `seleccionar_campos.blade.php` ya no existen; no queda ninguna referencia a `route('admLogin')` ni `route('show.admLogin')`. Los `dashboard.blade.php` que sí existen hoy (`admin/` y `operativo/`) están en uso activo (`PanelController::index`, `DashboardController::index`) — no son los que este hallazgo señalaba como muertos. `.claude/settings.local.json` ya no está en `git ls-files` y `.gitignore` lo excluye explícitamente. |

---

## Lo que está bien hecho

No todo son problemas. Verificado explícitamente:

- **Sin inyección SQL.** No hay `DB::raw` ni `whereRaw` con entrada de usuario; todo pasa por Eloquent parametrizado.
- **Cero usos de `{!! !!}`** en las 57 vistas — el escapado de Blade se respeta en todo el HTML. (La excepción es C4, que no es un fallo de escapado sino de contexto: `{{ }}` dentro de un atributo `on*` no protege.)
- **CSRF completo.** Todos los formularios llevan `@csrf`.
- **Sin mass assignment activo.** Ningún `$request->all()` en creates ni updates; `$fillable` explícito en todos los modelos.
- **Contraseñas correctas**: cast `'password' => 'hashed'`, `$hidden` bien configurado.
- **Subida de archivos segura**: `store()` genera nombres hash aleatorios (sin sobrescritura ni path traversal), con validación de tipo MIME y tamaño.
- **Transacciones** correctamente aplicadas en `InventarioController::store/update`.
- **Eager loading** correcto y con paginación en los listados — sin problemas N+1.
- **Laravel 12.39.0**, versión reciente y sin vulnerabilidades conocidas.
- **Historial de git limpio**: ningún secreto real filtrado, solo placeholders.
- **Cálculos ponderados correctos**: los pesos de FIT suman 1.0, los de FET (0.20 + 0.40 + 0.40) también, la normalización de Percepción y los umbrales VTT son consistentes.
- **`LugarController::destroy`** protege contra borrar lugares con zonas asociadas, y `UserController::destroy` impide el auto-borrado.

---

## Plan de acción sugerido

> **Nota 2026-08-09:** este plan quedó obsoleto casi entero — los diez puntos
> de código que listaba (C1-C4, A2-A7, A4, M1, M2, M5, M11) están resueltos y
> verificados, ver cada hallazgo arriba. Se deja el texto histórico plegado
> abajo y se reduce este plan a lo que de verdad sigue pendiente, que no es
> código:

**Pendiente real, los tres son pasos de despliegue, no de repositorio:**
1. Rotar la contraseña del administrador en la instancia ya desplegada (C1)
   — el seeder arreglado protege despliegues futuros, no revierte el actual.
2. Dar de alta un bucket S3-compatible y rellenar `AWS_*` en Render, o subir
   al plan Starter y descomentar el `disk:` de `render.yaml` (A1).
3. Configurar un proveedor SMTP real y rellenar `MAIL_HOST`/`MAIL_USERNAME`/
   `MAIL_PASSWORD` en Render (M16).

<details>
<summary>Plan original del 6 de agosto (histórico, ya ejecutado)</summary>

**Ahora mismo (producción comprometida):**
1. Cambiar la contraseña del admin en la instancia desplegada.
2. Sacar los usuarios demo del seeder de producción (C1).
3. Añadir el middleware de pertenencia a zona (C2) y escopar los inventarios (C3).
4. Sacar `{{ $user->name }}` del atributo `onsubmit` (C4) — es una línea.

**Esta semana:**
4. Mover el almacenamiento de imágenes fuera del contenedor (A1).
5. Arreglar `HasFactory` y poner a correr los tests (A2), luego añadir tests de los cálculos y de autorización (A3).
6. Migración con los `unique` y los `nullOnDelete` faltantes (A5, A6).
7. Validar `accion_estado` (A7).

**Después:**
8. Limpiar los modelos fantasma (A4), retirar `/__bootstrap` y cerrar el registro público (M1, M2).
9. Índices de claves foráneas (M5) y casts decimales (M11).
10. Higiene de repositorio: `.dockerignore`, `.gitignore`, README (B1, B2, B5).

</details>

---

## Nota de verificación

**Fecha:** 9 de agosto de 2026.
**Contra qué commit:** base `main` en `a03378c` (362 tests en verde), rama
`auditoria-al-dia`. Los cambios de esta verificación —este documento y los
tres tests nuevos— son los commits siguientes en esa rama; verlos con
`git log auditoria-al-dia` o `git diff a03378c..auditoria-al-dia`.

**Método:** cada uno de los 37 hallazgos (C1–C4, A1–A8, M1–M16, B1–B9) se
comprobó contra el código actual, no contra este documento. Donde el
hallazgo era de permisos, la comprobación fue una petición real con
`actingAs()` intentando lo que el hallazgo dice que no debería poder pasar
— no una relectura del `if`. Se ejecutó la suite completa antes y después:
**362 → 365 tests, todos en verde** (3 tests nuevos, para los hallazgos
resueltos que no tenían ninguno: M6, M9, M11). Los tres se verificaron
además reintroduciendo temporalmente el bug original en el código de
producción y confirmando que el test nuevo lo detecta, antes de revertir.

**Resultado:** cero hallazgos de seguridad vivos. Dos hallazgos pendientes,
ninguno de código — son pasos de despliegue (A1: bucket S3, M16: SMTP) que
ya estaban correctamente señalados como pendientes en el documento previo a
esta verificación.

**Próxima vez que se lea este documento:** si una sección de hallazgo no
tiene una línea `**Estado: ...**` justo debajo de su título, es que no se ha
verificado desde el 9 de agosto de 2026 — no asumir que sigue vigente ni que
está resuelta.
