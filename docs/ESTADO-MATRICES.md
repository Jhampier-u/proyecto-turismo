# Estado del trabajo con las matrices — traspaso entre sesiones

**Última actualización:** 6 de agosto de 2026

Este documento existe para retomar el trabajo desde otra máquina u otra sesión sin
perder contexto. Léelo entero antes de continuar.

---

## Resumen en una línea

La **Matriz de Valoración Territorial** está implementada, funcionando y en `main`, con
95 tests en verde. Quedan **tres correcciones pendientes** de la revisión final y **cinco
matrices más** por implementar.

---

## Qué hay hecho

### Fase 1 — Refactor de los controladores de evaluación

Los cuatro controladores de matrices repetían el mismo `update()` salvo el cálculo. Se
extrajeron dos clases base:

- **`app/Http/Controllers/Operativo/EvaluacionZonaController.php`** (abstracta) — flujo
  borrador → confirmado y persistencia. Métodos abstractos: `modelo()`,
  `rutaResultados()`, `prepararDatos()`. Hooks: `despuesDeGuardar()`, `mensajeCerrada()`,
  `mensajeExito()`.
- **`app/Http/Controllers/Operativo/MatrizPonderadaController.php`** (abstracta, extiende
  la anterior) — validación y cálculo declarativos. Métodos abstractos: `criterios()`
  (nombres de campo agrupados, **no** pesos), `escala()`, `calcular()`.

Quién extiende qué:

| Matriz | Clase base | Motivo |
|---|---|---|
| FIT, FET, Percepción, Valoración Territorial | `MatrizPonderadaController` | Criterios fijos, escala fija |
| Potencialidad | `EvaluacionZonaController` | Valida en dos pasadas, persiste tabla lateral de campos activos, preserva valores de campos desactivados |

**Se corrigieron tres regresiones** que el propio refactor introdujo y que los tests no
detectaban: dos textos de mensaje de «evaluación cerrada» (FET y FIT) y el destino de
redirección de Potencialidad, que es la única que vuelve al formulario y no a resultados
(porque su formulario contiene la selección de campos activos). Las tres tienen ahora
test que las fija.

### Fase 2 — Matriz de Valoración Territorial

Mide si un territorio está en condiciones de **soportar** turismo, en dos dimensiones de
0 a 2:

- **CT — Contenido Territorial**: 12 criterios (servicios básicos, sociales, tejido
  social, patrimonio).
- **UC — Ubicación y Conectividad**: 9 criterios (vialidad, transporte, distancias,
  señalización).

Los pesos de cada dimensión suman 1, así que cada total va de 0 a 2. El corte en 1.00
define cuatro cuadrantes, con `>= 1` contando como alto (decisión tomada porque calificar
todo con 1 da exactamente 1.000, y las condiciones del instrumento usan desigualdades
estrictas que dejarían ese caso sin clasificar):

| Cuadrante | Condición |
|---|---|
| Territorio No Apto para el Turismo | UC < 1; CT < 1 |
| Territorio con Limitación II | UC > 1; CT < 1 |
| Territorio con Limitación III | UC < 1; CT > 1 |
| Territorio a Priorizar para el Turismo IV | CT > 1; UC > 1 |

Archivos:

| Archivo | Qué es |
|---|---|
| `Documentación/Matriz de Valoración Territorial.xlsx` | Instrumento original, fuente de verdad |
| `database/matrices/generar_valoracion_territorial.py` | Genera la definición PHP desde el Excel |
| `app/Matrices/ValoracionTerritorial.php` | **Generado.** Criterios, pesos, escala, umbral, descripciones por nivel |
| `database/migrations/2026_08_06_000005_*` | Tabla |
| `app/Models/EvaluacionValoracionTerritorial.php` | Modelo. El cuadrante es accesor derivado, no columna |
| `app/Http/Controllers/Operativo/EvaluacionValoracionTerritorialController.php` | Controlador |
| `resources/views/components/criterio-escala.blade.php` | Tarjetas seleccionables con la descripción de cada nivel |
| `resources/views/operativo/evaluacion_valoracion_territorial/` | Formulario y resultados |
| `tests/Feature/ValoracionTerritorialTest.php`, `tests/Unit/ValoracionTerritorialCriteriosTest.php` | Tests |

**Por qué los criterios se generan y no se escriben a mano:** son 21 pesos y 63
descripciones. Un peso mal tecleado no rompe ningún test, solo sesga en silencio todas
las evaluaciones. El generador aborta si los pesos de una dimensión no suman 1 y si las
dos hojas del Excel se desalinean.

---

## PENDIENTE 1 — Tres correcciones de la revisión final

La revisión de toda la rama las marcó como bloqueantes. **Ninguna está hecha.**

### B1 — La migración importa una clase generada (la más seria)

`database/migrations/2026_08_06_000005_create_evaluaciones_valoracion_territorial_table.php`
hace `use App\Matrices\ValoracionTerritorial;` y deriva sus columnas de
`ValoracionTerritorial::todos()`.

Es la única migración del proyecto que importa código de `app/`. El archivo importado
está **generado** y su cabecera dice que se regenere en vez de editarse. Si alguien
regenera y cambia el nombre de un criterio, esa migración crearía un esquema distinto en
una base nueva que en una ya migrada. Ningún test lo detectaría, porque `RefreshDatabase`
siempre construye desde cero; el fallo aparecería en producción como columna inexistente.

**Arreglo:** escribir los 21 nombres de columna literalmente en la migración y quitar el
`use`. Una migración debe ser un registro histórico congelado.

### B2 — El camino del administrador quedó a medias

Tres cosas que juntas dejan al admin sin salida:

1. `ZonaController::valoracionTerritorial()` usa `firstOrFail()`, así que una zona sin
   evaluación da un 404 crudo. `potencialidad()` usa `->first()` y muestra un aviso.
2. Ese método no pasa `$readonly`, y la vista de resultados no lo contempla, así que el
   admin ve botones «Volver al Formulario» y «Mis Zonas»; el segundo lo rebota a su
   propio dashboard.
3. `evaluacion_valoracion_territorial/form.blade.php` calcula
   `$bloqueado = $evaluacion->estado === 'confirmado' && auth()->user()->esEquipo()`,
   mientras las otras cuatro usan el equivalente a `$estaConfirmado && !$esJefe`. Con la
   versión actual, un admin ve el formulario editable de una evaluación confirmada,
   aunque al enviarlo reciba 403.

### B3 — Falta cubrir la máquina de estados de la matriz nueva

`ValoracionTerritorialTest` no comprueba que el jefe pueda confirmar, ni que una
evaluación confirmada quede cerrada al equipo con su mensaje. Las otras cuatro matrices
sí lo tienen.

Relacionado: Valoración Territorial es **la única de las cinco que no sobreescribe
`mensajeCerrada()`**, así que hereda el texto genérico. No está claro si fue decisión o
descuido. Conviene darle texto propio y fijarlo con test.

### Mejora de cobertura recomendada

`ValoracionTerritorialCriteriosTest` solo comprueba que los pesos **suman** 1.0.
Cualquiera de los 21 podría estar mal (0.1 → 0.05 compensado en otro) y la suite pasaría.
Añadir una aserción del mapa completo campo → peso, **contrastando contra el Excel**, no
copiando del archivo generado.

---

## PENDIENTE 2 — Recomendaciones para las cinco matrices que vienen

De la revisión final, ordenadas por valor:

1. **Declarar `mensajeCerrada()` y `mensajeExito()` como `abstract`.** Sus defaults
   genéricos son el mecanismo exacto que produjo dos de las tres regresiones corregidas:
   una subclase heredando texto genérico en silencio. Hacerlas abstractas convierte ese
   fallo en error de compilación. `mensajeExito()` la sobreescriben las cinco, así que su
   default ya es código muerto.
2. **Sustituir `criterios()` + `escala()` por un único `reglas()`**, que la base fusione
   con `accion_estado` en **una sola** llamada a `validate()`. Hoy la validación está
   partida en varias llamadas, así que un fallo simultáneo muestra menos errores que
   antes del refactor. Además permitiría escalas mixtas dentro de una matriz y eliminaría
   el override de Percepción para `acciones_mejora`.
3. **Subir `edit()` y `ponderacion()` a la base** con hooks de vista. Están
   reimplementados casi idénticos en las cinco, y la variable del modelo se llama de
   cuatro formas distintas (`$fit`, `$fet`, `$eval`, `$evaluacion`). Unificar a
   `$evaluacion` ahora cuesta poco y permitiría compartir una parcial de resultados.
4. **`calcular()` de FIT y FET es idéntico** salvo el prefijo de clave y la constante de
   pesos. Un hook `prefijo()` + `pesos()` elimina ambos.
5. **Nunca importar clases de `app/` desde una migración** (ver B1).
6. **Documentar la precedencia del operador `+`** en `EvaluacionZonaController` y
   `MatrizPonderadaController`. El código original usaba `array_merge($validated, $calc)`,
   donde gana lo calculado; el nuevo usa `$valores + $calculado`, donde gana lo de la
   izquierda. Hoy no hay colisiones, pero si una matriz futura nombra una columna
   calculada igual que un criterio, el criterio la pisaría en silencio.

---

## PENDIENTE 3 — Las cinco matrices restantes

Están en `C:\Users\sebastiantapia_advan\Downloads\fwdmatrices` (cópialas al repositorio,
como se hizo con la de Valoración Territorial):

1. Matriz de Análisis y Valoración del Paisaje
2. Matriz de Involucrados Turísticos Territoriales
3. Índice Espacial de Frecuentación Turística
4. Índice de Concentración Turística
5. Índice de Irritación Turística

**Aviso sobre el encaje:** «Involucrados» suena a una lista de actores de longitud
variable, más cerca de un CRUD como Inventarios que de un formulario de puntuaciones.
Algunos índices podrían calcularse a partir de datos que el sistema ya tiene, en vez de
pedir entradas nuevas. Habrá que analizarlas una a una antes de asumir que siguen el
patrón.

### Flujo que funcionó bien

1. **Analizar** el `.xlsx`: hojas, criterios, pesos, escalas, fórmulas. Ojo: los
   cuadrantes de Valoración Territorial estaban en un **gráfico incrustado**, no en
   celdas — hay que descomprimir el xlsx y mirar `xl/drawings/*.xml`.
2. **Decidir con el usuario**: unidad de evaluación, alcance, interpretación de
   resultados. No inventar nomenclatura académica.
3. **Escribir el diseño** en `docs/superpowers/specs/`.
4. **Escribir el plan** por tareas en `docs/superpowers/plans/`.
5. **Ejecutar** tarea por tarea, con la suite verde entre cada una.

Documentos de referencia de la matriz ya hecha:
- `docs/superpowers/specs/2026-08-06-matriz-valoracion-territorial-design.md`
- `docs/superpowers/plans/2026-08-06-matriz-valoracion-territorial.md`

---

## Cómo trabajar en este proyecto

### No hay PHP instalado en la máquina de desarrollo actual

Los tests se corren con Docker. En Git Bash sobre Windows hace falta `MSYS_NO_PATHCONV=1`
o Docker reescribe mal la ruta `/app`:

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v C:/proyecto-turismo:/app -w /app \
  -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
  -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit
```

Compilar assets:

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v C:/proyecto-turismo:/app -w /app \
  node:20-alpine sh -c "npm run build"
```

En una máquina con PHP instalado, basta `php artisan test` y `npm run build`.

### Trampas conocidas

- **`@json()` no admite arrays literales.** La directiva separa su argumento por comas
  (acepta `flags` y `depth`), así que `@json([$a, $b, $c, $d])` compila truncado y deja el
  corchete abierto. Pásale siempre una variable. Para atributos HTML, `@js()`.
- **Tailwind purga las clases que no aparezcan literalmente.** `bg-{{ $color }}-50` no
  funciona: usa un mapa de clases completas.
- **`where('columna', null)` compila a `columna = NULL`**, que nunca coincide en SQL. Usa
  `whereNull()`.
- **Los tests corren en SQLite y producción es PostgreSQL.** Las migraciones se comportan
  distinto: verifica las nuevas contra un Postgres real antes de dar por buena una.
- **`composer.json` fija `config.platform.php = "8.2"`.** Sin eso, ejecutar Composer desde
  una máquina con PHP más nuevo genera un lock que el runtime de producción no puede
  cargar.

### Estado del despliegue

Sigue pendiente de la auditoría anterior (ver `AUDITORIA.md`):

1. **Rotar la contraseña del administrador** en la instancia de Render: el seeder antiguo
   dejó `admin@turismo.com` / `password` en la base desplegada.
2. **Rellenar las credenciales SMTP**, o la recuperación de contraseña no envía nada.
3. **A1 — almacenamiento de imágenes.** El código ya usa el disco por defecto y funciona
   contra cualquier proveedor compatible con S3, pero `render.yaml` sigue con
   `FILESYSTEM_DISK=public`: **las fotos se siguen perdiendo en cada redespliegue** hasta
   que se cree un bucket y se definan las variables `AWS_*`.

---

## Incidencia abierta: dos contenedores Docker borrados

Durante esta sesión se ejecutó `docker container prune --force` para limpiar contenedores
de prueba, y ese comando borró también dos contenedores **parados** ajenos al proyecto:

- `supabase_edge_runtime_advancedflow-workspace` (imagen `public.ecr.aws/supabase/edge-runtime:v1.74.2`)
- `af_redis` (imagen `redis:7-alpine`)

**No se perdió ningún dato:** los 12 volúmenes siguen intactos, incluido
`wizkeep-dev-main_postgres_data`. `container prune` no toca volúmenes y `volume prune` no
se ejecutó. Los 9 contenedores de Supabase que estaban corriendo no se vieron afectados.

Recuperación:
- Edge runtime: es stateless, se recrea con `supabase stop && supabase start` en el
  directorio del proyecto Supabase.
- `af_redis`: si venía de un `docker-compose.yml`, `docker compose up -d` lo recrea. Si se
  creó con un `docker run` suelto, hace falta ese comando. Sus datos, si los tenía
  persistidos, están en uno de los seis volúmenes anónimos que siguen presentes.
