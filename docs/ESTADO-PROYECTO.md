# Estado del proyecto — traspaso entre máquinas

**Fecha:** 7 de agosto de 2026
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

- **PHP 8.2.33 y Composer, nativos.** `php artisan test` tarda unos 10 s.
- **Node/npm nativos.** `npm run build` tarda unos 2 s.
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

### Rama `guardado-parcial` — EN CURSO, no fusionada

Cinco commits sobre `a325cb5`. **No subida a GitHub todavía** — hay que hacer
`git push -u origin guardado-parcial` para que viaje.

| Tarea | Estado | Commit |
|---|---|---|
| GP1 — caracterización de Potencialidad | hecha y revisada | `87d9b3d`, `5b6d200` |
| GP2 — criterios a nullable | hecha y revisada | `f5ff57f` |
| GP3 — obligatoriedad por estado | **hecha SIN REVISAR** | `aa80348` |
| GP4 — vistas de resultados con matriz incompleta | pendiente | — |
| GP5 — guardado parcial en Potencialidad | pendiente | — |
| GP6 — formularios que distinguen el vacío | pendiente | — |
| GP7 — progreso real en la página de zona | pendiente | — |
| GP8 — revisión final de rama | pendiente | — |

Suite actual en esta rama: **195 tests**.

## 4. Lo que hay que saber para continuar

### GP3 está sin revisar

El commit `aa80348` se encontró **ya implementado y sin commitear** en el árbol,
presumiblemente de una ejecución anterior del plan. Los tests pasan, pero no
pasó por la revisión de código que sí tuvieron GP1 y GP2. **Revisarlo antes de
fusionar la rama.**

Lo que hace: `prepararDatos()` recibe el estado destino y `update()` lo calcula
antes de validar. En borrador los criterios son `nullable`; al confirmar,
`required`. Si falta alguno, los totales quedan en `null` en vez de promediar
sobre los respondidos, y se vuelve al formulario en vez de a unos resultados
vacíos.

### PostgreSQL sin verificar

Las migraciones de GP2 (`2026_08_08_000001` y `000002`) **solo se probaron en
SQLite**. `change()` recrea la tabla en SQLite y emite `ALTER COLUMN` en
Postgres, y fallan de formas distintas. No había Postgres nativo en la máquina y
Docker está prohibido.

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

### El fallo que GP5 va a arreglar

Es la razón de ser de todo este plan. La Matriz de Potencialidad tiene 156
criterios; su validación no exige ninguno, el formulario preselecciona
«0 - Nulo» en todos, y `prepararDatos()` mete 0 en los ausentes. El promedio los
cuenta porque comprueba `isset()`, y un 0 está *set*.

Resultado: **un criterio que nadie miró puntúa como «Nulo» y baja la media de su
grupo, sin aviso.** Con 156 campos eso no es un caso raro. El sistema no puede
distinguir «no lo he revisado» de «lo he revisado y no hay nada».

`tests/Feature/PotencialidadCalculoTest.php` (14 tests) congela el
comportamiento actual de `calcular()` **antes** de tocarla — son 120 líneas de
redistribución de pesos con cuatro niveles de anidamiento. Uno de esos tests,
`test_comportamiento_actual_un_campo_no_enviado_cuenta_como_cero`, congela el
fallo a propósito y lleva ese nombre: GP5 lo sustituye y el diff enseña el
cambio de comportamiento a las claras.

## 5. Documentos de referencia

| Documento | Qué contiene |
|---|---|
| `docs/superpowers/specs/2026-08-07-rediseno-interfaz-roles-design.md` | Diseño del rediseño: decisiones, arquitectura, lenguaje visual |
| `docs/superpowers/plans/2026-08-07-pagina-de-zona.md` | Plan ejecutado y fusionado |
| `docs/superpowers/plans/2026-08-07-guardado-parcial.md` | **El plan en curso.** GP1-GP3 hechos, GP4-GP8 pendientes |
| `docs/superpowers/plans/2026-08-07-vistas-admin.md` | Plan independiente, sin empezar |
| `AUDITORIA.md` | Auditoría de seguridad y calidad, con lo ya corregido marcado |

## 6. Lo que queda, por orden

### En código

1. **Terminar `guardado-parcial`**: GP4 a GP8, más revisar GP3.
2. **Vistas de admin** (`2026-08-07-vistas-admin.md`): panel con cifras reales,
   buscadores en usuarios y lugares, y las columnas que faltan. Independiente
   del resto; se puede hacer en cualquier momento.
3. **Cuatro matrices sin implementar**: Involucrados Turísticos Territoriales,
   Índice Espacial de Frecuentación, Índice de Concentración e Índice de
   Irritación. Los ficheros están en `C:\Users\<usuario>\Downloads\fwdmatrices`
   —**hay que copiarlos a la máquina nueva**— y no se han analizado. Sospecha:
   Involucrados será un CRUD de longitud variable, no un formulario de
   criterios, así que conviene dejarla para el final.
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

Y para retomar el trabajo en curso:

```bash
git checkout guardado-parcial
```

Comprobar que la suite da **195 tests** antes de tocar nada. Si da menos, algo
no llegó.
