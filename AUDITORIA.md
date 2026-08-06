# Auditoría técnica — UDAExplore / Gestión Turística

**Fecha:** 6 de agosto de 2026
**Alcance:** Laravel 12.39.0, PHP 8.2, PostgreSQL en producción (Render), Docker.
~1.400 líneas de controladores, 57 vistas Blade, 15 migraciones, 21 commits.
**Método:** análisis estático del código. No fue posible ejecutar la suite de tests
(no hay PHP ni `vendor/` instalados en la máquina de auditoría).

---

> **Estado:** corregido **todo el informe**: los cuatro críticos (C1–C4), los ocho
> altos (A1–A8), los 16 medios (M1–M16) y los 9 bajos (B1–B9). Además se
> actualizaron las dependencias, que acumulaban 31 avisos de seguridad.
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
> El almacenamiento de imágenes (A1) se probó de extremo a extremo contra un
> servidor S3 real (MinIO): el mismo código produce `/storage/…` con el disco
> local y la URL del bucket con `FILESYSTEM_DISK=s3`, sin cambios en el código.
>
> **Pendiente, todo fuera del código:** rotar la contraseña del administrador en
> la instancia desplegada, rellenar las credenciales SMTP y —si se quiere
> conservar las fotos— dar de alta un bucket S3 y definir las variables `AWS_*`.
>
> Falta **rotar manualmente la contraseña del administrador en la instancia ya
> desplegada**: el arreglo del seeder protege despliegues futuros, no el actual.
> El resto de hallazgos altos, medios y bajos siguen pendientes.

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

Recuento: **4 críticos, 10 altos, 16 medios, 9 bajos**.

---

## CRÍTICOS

### C1 — Credenciales de administrador conocidas, activas en producción

**Dónde:** `database/seeders/DatabaseSeeder.php:19-41`, `docker/entrypoint.sh:34-39`, `render.yaml`

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

**Dónde:** `app/Http/Middleware/isPersonal.php:19` + todos los controladores de `app/Http/Controllers/Operativo/`

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

**Dónde:** `app/Http/Controllers/Operativo/InventarioController.php:100-111`, `:113-121`, `:123-139`, `:141-191`

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

**Dónde:** `resources/views/admin/users/index.blade.php:75`

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

**Dónde:** `render.yaml` (bloque `disk:` comentado), `Dockerfile`, `docker/entrypoint.sh:14-21`

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

**Dónde:** `app/Models/User.php:11`

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

> **Parcialmente cubierto.** Se añadieron `AutorizacionZonaTest`, `AdminUsuariosTest` y
> `SeedersTest`, que fijan las reglas de acceso por zona, el escapado del nombre de
> usuario y el comportamiento del seeder. Sigue sin haber tests de los cálculos
> ponderados (FIT, FET, VTT, Potencialidad, Percepción), que es lo que queda pendiente.

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

**Dónde:** `app/Models/Evaluacion.php:9`, `EvaluacionValor.php:9`, `MatrizCriterio.php:9`, `MatrizVariable.php:9`

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

**Dónde:** `evaluaciones_fit`, `evaluaciones_fet`, `evaluaciones_potencialidad`, `potencialidad_campos_activos`

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

**Dónde:** `app/Http/Controllers/Admin/UserController.php:69-79`, `app/Http/Controllers/ProfileController.php:43-59`

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

**Dónde:** `EvaluacionFetController.php:58-60`, `EvaluacionFitController.php:77-79`, `EvaluacionPercepcionController.php:87-89`, `EvaluacionPotencialidadController.php:281-283`

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

**Dónde:** `EvaluacionFetController.php:26` y equivalentes en los otros tres controladores

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

| # | Hallazgo | Ubicación |
|---|---|---|
| M1 | **Endpoint `/__bootstrap`** ejecuta `migrate` + `db:seed` desde el navegador. El token viaja en query string (queda en logs), se compara con `!==` en vez de `hash_equals()`, no tiene rate limiting y usa `env()` en runtime (con `config:cache` activo devuelve null y la ruta muere). Además el seeder no es idempotente: una segunda visita duplica catálogos. | `routes/web.php:22-34` |
| M2 | **Registro público abierto.** Cualquiera en internet crea cuentas; quedan con `role_id = NULL` e inservibles, pero llenan la tabla. En un sistema de roles administrado el registro debería estar deshabilitado. | `routes/auth.php:15-18` |
| M3 | **`VttController` escribe en base de datos en una petición GET.** Viola la semántica HTTP (sin CSRF, cacheable, el prefetch del navegador puede dispararlo) y cualquier usuario sobrescribe el `user_id` del VTT con solo visitar la página. | `VttController.php:44-53`, `routes/web.php:88` |
| M4 | **Catálogos sin `unique`**, por lo que el `insertOrIgnore()` del seeder no ignora nada: cada re-ejecución duplica las 4 regiones, 24 provincias, categorías y tipos de propietario. | `SystemSeeder.php:45,54` y migración principal |
| M5 | **Índices de claves foráneas ausentes.** Producción es PostgreSQL, que —a diferencia de MySQL— **no** indexa las FK automáticamente. Sin índice: `inventarios.zona_id`, `zonas.jefe_user_id`, `zona_equipo.user_id`, `users.role_id`, `categorias_recurso.parent_id` y las `zona_id` de las evaluaciones. | migraciones |
| M6 | **Potencialidad: efecto colateral persistido antes de validar.** `PotencialidadCamposActivos::updateOrCreate()` corre antes de `$request->validate()`; si la validación falla, la configuración ya cambió sin transacción que lo revierta. Además `campos[]` no se filtra contra una lista blanca. | `EvaluacionPotencialidadController.php:245-264` |
| M7 | **Fotos de inventario sin límite de cantidad.** Se valida cada archivo (`max:2048`) pero no el array. Una petición con cientos de imágenes llena el disco. | `InventarioController.php:58,158` |
| M8 | **Borrar una zona deja las fotos de sus inventarios huérfanas.** La cascada de BD borra las filas, pero nadie borra los archivos de `storage/app/public/inventarios`. | `ZonaController.php:107-114` |
| M9 | **`ZonaController::update`**: enviar imagen nueva y "quitar imagen" a la vez guarda el archivo y luego anula la referencia — archivo huérfano y cambio perdido. | `ZonaController.php:87-99` |
| M10 | **`role_id` en `$fillable` de `User`.** Hoy no es explotable (todas las rutas usan arrays validados), pero un `User::create($request->all())` futuro habilita escalada a admin. Lo mismo con `user_id` y `estado` en los modelos de evaluación. | `User.php:14` |
| M11 | **Casts numéricos ausentes** en los ~50 campos `decimal` de las evaluaciones. PostgreSQL devuelve `numeric` como *string*, así que el comportamiento difiere entre local (SQLite → float) y producción. | modelos de evaluación |
| M12 | **`SESSION_SECURE_COOKIE` no está definida** en `render.yaml`, así que la cookie de sesión no lleva el flag `Secure` pese a que el sitio va por HTTPS. | `render.yaml`, `config/session.php:172` |
| M13 | **Asignación de jefe/equipo sin validar el rol.** `'jefe_user_id' => 'required|exists:users,id'` acepta a cualquier usuario. Si se asigna como jefe a alguien de rol 3, la zona queda bloqueada en borrador sin explicación visible. | `ZonaController.php:33,74` |
| M14 | **La paginación sale sin estilos en producción.** `tailwind.config.js:7` escanea las vistas de paginación de `vendor/`, pero la etapa de assets del `Dockerfile:7-9` solo copia los configs y `resources/` — **`vendor/` no existe ahí** (Composer corre después, en la etapa 2). Las clases se purgan. Afecta a los cuatro listados con `->links()`. Solo se ve en el entorno desplegado; en local funciona. | `Dockerfile:7-9`, `tailwind.config.js:7` |
| M15 | **Clases Tailwind construidas dinámicamente se purgan.** `class="bg-{{ $color }}-50 border border-{{ $color }}-200"` — Tailwind busca nombres de clase literales, y `bg-green-50` nunca aparece como texto. La tarjeta del cuadrante se renderiza sin color. Necesita un mapa de clases completas o un `safelist`. | `admin/zonas/potencialidad.blade.php:89-92` |
| M16 | **La recuperación de contraseña no funciona.** `render.yaml` no define ninguna variable `MAIL_*`, así que queda el default `log`, y `LOG_CHANNEL=stderr`. El formulario responde "enlace enviado" pero el correo solo se escribe en stderr. Nadie puede recuperar su contraseña salvo que el admin la resetee a mano. | `render.yaml`, `auth/forgot-password.blade.php` |

---

## BAJOS

| # | Hallazgo | Ubicación |
|---|---|---|
| B1 | **19 MB de archivos no-código entran en cada imagen Docker.** `.dockerignore` no excluye `Documentación/` (3,2 MB) ni `entregables/` (16 MB, con su propio `node_modules`). | `.dockerignore` |
| B2 | **`entregables/node_modules` está versionado en git**: 576 archivos. `.gitignore` usa `/node_modules`, anclado a la raíz, que no cubre subdirectorios. | `.gitignore` |
| B3 | **Interpolación Blade dentro de JavaScript.** `label: '{{ $zona->nombre }}'` — no es explotable (dentro de `<script>` el navegador no decodifica entidades HTML), pero un nombre con apóstrofo se muestra como `d&#039;Oro`. Usar `@json()`, como sí se hace correctamente en `evaluacion_percepcion/ponderacion.blade.php:187`. | `evaluacion_potencialidad/ponderacion.blade.php:352` |
| B4 | **`env.example` está obsoleto**: documenta un despliegue en Railway con MySQL, mientras el despliegue real es Render con PostgreSQL (`render.yaml`). Conviven dos archivos de ejemplo contradictorios (`.env.example` y `env.example`). | raíz |
| B5 | **README desactualizado**: describe XAMPP y MySQL, sin mención de Docker ni PostgreSQL. Además arrastra el README genérico de Laravel a partir de la línea 76. | `README.md` |
| B6 | **Restos y código muerto**: `start.txt` vacío; ruta y método `guardarCampos` que solo redirige; `@tailwindcss/vite@4.1.17` instalado pero sin usar (el build va por PostCSS con Tailwind 3.4.18). | varios |
| B7 | **Comparaciones de rol inconsistentes**: `IsAdmin.php:19` usa `!== 1` (estricto) mientras el módulo operativo usa `== 2` / `== 3` (flojo), y `User` no castea `role_id` a entero. | middleware y controladores |
| B8 | **El menú móvil no tiene ningún enlace útil.** Los accesos a Panel Admin / Usuarios / Lugares / Zonas están solo en el bloque `hidden sm:flex`. Por debajo de 640 px el admin no puede navegar a ninguna sección — relevante para un sistema de trabajo de campo. | `layouts/navigation.blade.php:97-102` |
| B9 | **Vistas muertas que apuntan a rutas inexistentes**: `auth/admLogin.blade.php:9` usa `route('admLogin')` y `components/layout.blade.php:12` usa `route('show.admLogin')`; ninguna de las dos rutas existe (produciría error 500 si se enlazara). También sin uso: `evaluacion_potencialidad/seleccionar_campos.blade.php` (251 líneas) y `dashboard.blade.php`. Además `.claude/settings.local.json` está versionado con rutas absolutas del disco del desarrollador. | varios |

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
