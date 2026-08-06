# UDAExplore — Gestión Turística

Sistema de gestión de inventarios y evaluación de potencial turístico territorial.
Implementa cinco matrices de valoración (Inventarios, FIT, FET, Potencialidad
Turística y Percepción de la Localidad) sobre zonas asignadas a equipos de campo.

**Stack:** Laravel 12 · PHP 8.2 · PostgreSQL (producción) / SQLite (desarrollo y
tests) · Tailwind CSS 3 + Vite · Docker.

---

## Roles

| Rol | Puede |
|---|---|
| **Admin** | Gestionar usuarios, lugares y zonas. Consultar resultados de cualquier zona (solo lectura). |
| **Jefe de Zona** | Completar y **validar** las evaluaciones de las zonas que dirige. |
| **Equipo** | Registrar inventarios y completar borradores en las zonas donde está asignado. |

Un usuario solo accede a las zonas donde es jefe o miembro del equipo. El
registro público está deshabilitado: las cuentas las crea el administrador.

---

## Puesta en marcha (desarrollo local)

Requisitos: PHP 8.2+, Composer, Node.js 20+.

```bash
git clone <url-del-repositorio> && cd proyecto-turismo
cp .env.example .env
composer install
npm install
php artisan key:generate
touch database/database.sqlite       # o configura DB_* para MySQL/PostgreSQL
php artisan migrate --seed
npm run build
php artisan serve
```

`.env.example` viene con `DB_CONNECTION=sqlite`, que no requiere instalar ningún
motor de base de datos.

En entorno `local`, `migrate --seed` crea usuarios de prueba (contraseña
`password` en los tres):

| Correo | Rol |
|---|---|
| `admin@local.test` | Admin |
| `jefe@local.test` | Jefe de Zona |
| `equipo@local.test` | Equipo |

Para trabajar con recarga en caliente de assets, en otra terminal:

```bash
npm run dev
```

---

## Tests

```bash
php artisan test
```

Cubren las reglas de acceso por zona, las fórmulas ponderadas de las matrices, el
flujo borrador → confirmado, las restricciones de integridad y el comportamiento
de los seeders.

---

## Despliegue (Render)

El despliegue usa el `Dockerfile` de la raíz y el blueprint `render.yaml`, que
provisiona el servicio web y una base PostgreSQL. El `docker/entrypoint.sh`
ejecuta migraciones en cada arranque y siembra los catálogos si la base está
vacía.

Variables que hay que rellenar a mano en el dashboard de Render (están marcadas
como `sync: false` en el blueprint):

- `APP_KEY` — genera con `php artisan key:generate --show`
- `APP_URL` — la URL que asigne Render
- `ADMIN_EMAIL`, `ADMIN_PASSWORD` — cuenta de administrador inicial. **Sin ellas
  no se crea ningún administrador.** Se usan una sola vez, cuando la base está
  vacía; cambiar la contraseña después desde la aplicación no se revierte.
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`
  — sin SMTP, la recuperación de contraseña no envía nada.

### Almacenamiento de imágenes

La aplicación lee y escribe las imágenes a través del **disco por defecto**
(`FILESYSTEM_DISK`), nunca contra una ruta fija. Cambiar de almacenamiento es
por tanto solo cuestión de variables de entorno.

Con `FILESYSTEM_DISK=public` los archivos van dentro del contenedor, que en el
plan gratuito de Render es efímero: **las fotos se pierden en cada redespliegue
o reinicio por inactividad**. Es el valor actual del blueprint.

Para conservarlas, usa cualquier proveedor compatible con S3 (Cloudflare R2,
Backblaze B2, Supabase Storage, MinIO):

1. Crea un bucket y permite **lectura pública** de sus objetos.
2. Genera un par de credenciales de acceso.
3. En Render define:

   | Variable | Valor |
   |---|---|
   | `FILESYSTEM_DISK` | `s3` |
   | `AWS_ACCESS_KEY_ID` | tu clave de acceso |
   | `AWS_SECRET_ACCESS_KEY` | tu clave secreta |
   | `AWS_BUCKET` | nombre del bucket |
   | `AWS_DEFAULT_REGION` | `auto` en R2; su región en otros |
   | `AWS_ENDPOINT` | endpoint S3 del proveedor |
   | `AWS_URL` | dominio público desde el que se sirven los archivos |
   | `AWS_USE_PATH_STYLE_ENDPOINT` | `true` |

No hace falta migrar datos: las rutas guardadas en la base de datos
(`inventarios/…`, `zonas/…`) son relativas y no cambian. Las imágenes anteriores
que ya se hayan perdido se muestran con un marcador de «Imagen no disponible» en
lugar del icono de imagen rota.

---

## Documentación

- [AUDITORIA.md](AUDITORIA.md) — auditoría técnica: hallazgos, correcciones aplicadas y trabajo pendiente.
- `Documentación/` — matrices de origen y documentos de la pasantía.
- `entregables/` — informe técnico y presentación.
