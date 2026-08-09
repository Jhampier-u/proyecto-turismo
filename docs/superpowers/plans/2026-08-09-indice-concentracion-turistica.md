# Índice de Concentración Turística — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Añadir la novena matriz —el Índice de Concentración Turística—, que cuenta la oferta del territorio por categoría en dos bloques: atractivos y planta turística.

**Architecture:** Un controlador más sobre `MatrizPonderadaController`, como las siete matrices de formulario. Lo propio de esta son unas 117 columnas de conteo, una escala sin tope y ninguna columna calculada.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Tailwind CSS 3, SQLite en desarrollo y tests, PostgreSQL 16 en producción, PHPUnit 11, Python 3.14 con openpyxl para el generador.

**Diseño:** `docs/superpowers/specs/2026-08-09-indice-concentracion-turistica-design.md`

## Global Constraints

- **Los conteos no tienen tope.** `reglaValor()` se sobreescribe a `integer|min:0`, como ya hace Paisaje con su escala no contigua. No se toca la clase base.
- **Nada calculado se guarda.** Porcentajes, `Sia`, `Pi` e `ICT` se derivan siempre. Comprobado que un `calcular()` que devuelve `[]` no incomoda a `MatrizPonderadaController`: `columnasCalculadasVacias()` da `[]` y la unión con los valores es inocua.
- **`Pi` no existe para un sector vacío**, no vale cero. El `ICT` de ese sector sí vale 0.
- Los conteos nacen `nullable` y sin defecto: un subtipo sin contar no es un 0, y aquí el 0 significa «no hay ninguno», que es un dato.
- Nada por debajo de 14 px salvo insignias. Sin `uppercase`. Clases de Tailwind completas, nunca por concatenación.
- Comentarios en castellano explicando el *por qué*.
- Suite completa en verde antes de cada commit. No se toca ningún contenedor Docker.

## Estructura de ficheros

**Crear:**
- `database/matrices/generar_concentracion.py` — el generador.
- `app/Matrices/Concentracion.php` — **generado**, no escrito a mano.
- `database/migrations/2026_08_11_000001_create_evaluaciones_concentracion_table.php`
- `app/Models/EvaluacionConcentracion.php`
- `app/Http/Controllers/Operativo/EvaluacionConcentracionController.php`
- `resources/views/operativo/evaluacion_concentracion/form.blade.php` y `ponderacion.blade.php`
- `tests/Feature/ConcentracionTest.php`, `tests/Unit/ConcentracionCalculoTest.php`

**Modificar:**
- `app/Matrices/Registro.php`, `routes/web.php`, `resources/views/components/icono.blade.php`

---

### Task 1: El generador y la definición del instrumento

**El riesgo de esta matriz no es la lógica, son los 117 nombres.** Escribirlos a
mano no rompe ningún test si uno se copia mal: solo desvía un conteo en
silencio. Por eso se generan desde el Excel, como se hizo con Valoración
Territorial.

**Files:**
- Create: `database/matrices/generar_concentracion.py`
- Create (generado): `app/Matrices/Concentracion.php`
- Test: `tests/Unit/ConcentracionCalculoTest.php`

- [ ] **Step 1: Leer el generador que ya existe**

`database/matrices/generar_valoracion_territorial.py` es el precedente: lee el
`.xlsx`, valida lo que extrae y aborta si algo no cuadra, y escribe una clase
PHP con una cabecera que dice que se regenere en vez de editarse. **Sigue su
forma.**

El instrumento está en `~/Downloads/fwdmatrices/Índice de Concentración Turística.xlsx`.
Python 3.14 con openpyxl está disponible desde PowerShell como `python` (desde
bash, `python3` es otro intérprete sin openpyxl).

- [ ] **Step 2: Extraer la hoja de atractivos**

Hoja `ICT(Rt-At)`. Dos tablas paralelas en la misma hoja:

- **Manifestaciones culturales**, columnas B-G, filas 7-28: `CATEGORÍA` (B),
  `TIPO` (C), `SUBTIPO` (D). La fila 29 es el total.
- **Atractivos naturales**, columnas I-M, filas 7-61: `CATEGORÍA` (I), `TIPO`
  (J), `SUBTIPO` (K). La fila 62 es el total.

Las columnas de categoría y tipo solo traen valor en la fila donde empiezan
—están combinadas—, así que hay que arrastrarlas hacia abajo.

El nombre de campo se compone del bloque, el tipo y el subtipo, en minúsculas y
sin acentos: `at_mc_arquitectura_museos`, `at_nat_montana_volcanes`. **Que el
generador aborte si dos filas producen el mismo nombre**, que es el fallo más
probable de una taxonomía con subtipos repetidos entre tipos —«Cuevas» aparece
en litoral y en montaña—.

- [ ] **Step 3: Extraer la hoja de planta turística**

Hoja `ICT(Pt)`, columnas J-L, filas 8-43: `Sector` (J), `Categoría` (K). El
sector solo aparece en su primera fila; arrástralo. Cada sector tiene su sigla
entre paréntesis en el nombre —«Alojamiento (AL)»—, y esa sigla es la que usan
las filas de subtotal: úsala como prefijo del campo: `pt_al_hotel`,
`pt_rs_cafeteria`.

- [ ] **Step 4: Escribir la clase generada**

`app/Matrices\Concentracion.php`, `final class`, con:

- `ATRACTIVOS` y `PLANTA`: el árbol de cada bloque, con sus categorías o
  sectores y, dentro, el mapa campo → etiqueta.
- `campos()`: los 117 nombres en orden.
- Una cabecera que diga de qué fichero se generó y que se regenere en vez de
  editarse, como la de `ValoracionTerritorial`.

- [ ] **Step 5: Fijar el recuento por test**

En `tests/Unit/ConcentracionCalculoTest.php`, un test que afirme cuántos campos
tiene cada bloque y que no hay ninguno repetido. Es lo que convierte un error
del generador en un fallo ruidoso.

**Aviso:** el número exacto de subtipos hay que leerlo del Excel, no darlo por
supuesto — este plan dice «unos 22», «unos 55» y «unas 40» porque son
aproximaciones de una inspección, no un recuento verificado. **Cuenta tú y pon
el número real.**

- [ ] **Step 6: Commit**

```bash
git add database/matrices/generar_concentracion.py app/Matrices/Concentracion.php \
        tests/Unit/ConcentracionCalculoTest.php
git commit -m "feat(concentracion): genera la definicion del instrumento desde el Excel"
```

---

### Task 2: El cálculo

Aritmética pura sobre arrays de escalares, sin Eloquent: es la lección que dejó
Involucrados, donde el cálculo acabó en la clase del instrumento importando
modelos y creando la primera dependencia circular del sistema.

**Files:**
- Modify: `app/Matrices/Concentracion.php` (a mano, fuera de la parte generada)
- Test: `tests/Unit/ConcentracionCalculoTest.php`

- [ ] **Step 1: Los tests, primero**

- **Porcentajes de atractivos**: con tres subtipos a 2, 3 y 5, sus porcentajes
  son 20 %, 30 % y 50 %.
- **Un bloque entero a cero**: la suma es 0 y los porcentajes no pueden reventar
  ni inventarse. Decide qué devuelven y fíjalo.
- **`Sia`, `Pi` e `ICT` por sector**: con un sector de `Sia` 4, `Pi` es 50.
- **Un sector vacío**: `Sia` 0, **`Pi` null** —no cero—, `ICT` 0.
- **`ICT` suma 1** entre todos los sectores cuando alguno tiene establecimientos.

- [ ] **Step 2: Implementar y verificar**

Métodos estáticos que reciban arrays de conteos y devuelvan arrays. Sin tocar la
base de datos y sin recibir modelos.

- [ ] **Step 3: Commit**

---

### Task 3: Tabla, modelo, controlador, rutas y registro

Es la parte rodada: sigue a `EvaluacionIrritacionController` paso por paso.

**Lo propio de esta matriz:**

- La migración declara las 117 columnas como `unsignedInteger` **nullable y sin
  defecto**. Derívalas de `Concentracion::campos()` **en el momento de escribir
  la migración**, pero **escríbelas literalmente en el fichero**: una migración
  no debe importar código de `app/`, y menos código generado. Es la lección de
  `2026_08_06_000005` y está explicada en su cabecera.
- El controlador sobreescribe `reglaValor()` a `integer|min:0`.
- `calcular()` devuelve `[]`.
- `criterios()` devuelve los dos bloques.
- La entrada del registro va en el grupo `presion`, con su `criterios` real y un
  icono nuevo que no repita ninguno —hay un test que lo comprueba—.

**Registra la entrada antes de escribir el controlador**, por lo que ya pasó en
Involucrados: sin ella, la suite queda verde y la matriz es invisible desde la
página de zona.

Al añadirla, los recuentos de `RegistroMatricesTest` y `EstadoZonaTest` pasan de
8 a 9, y varios tests que los cablean necesitarán ajuste. Si alguno hay que
**aflojar** en vez de actualizar un número, párate: indicaría que la entrada no
encaja donde creemos.

---

### Task 4: Formulario

Dos secciones, y dentro una subsección por categoría o sector, con un campo
numérico por subtipo. **Sin desplegables: son cantidades, no puntuaciones.**

Cada sector muestra su subtotal en vivo, que es lo que el evaluador necesita
para saber si va bien. Con 117 campos, un contador de respondidos por sección
también ayuda.

Tests: que se pintan los 117 campos, que el admin lo recibe bloqueado —con la
aserción **contando** los campos deshabilitados, no buscando la cadena
`disabled`, que las clases de Tailwind satisfacen por su cuenta—.

---

### Task 5: Resultados

Dos tablas. La de atractivos, con cantidad y porcentaje por subtipo. La de
planta, con `Sia`, `Pi` e `ICT` por sector, y **los sectores vacíos marcados
como que no aplica**, no con un cero ni con un guion sin explicar.

Con la matriz a medias, `x-matriz-sin-resultados`.

Tests: que los dos bloques se pintan con sus números en el caso completo —**no
solo `assertDontSee` en el incompleto**, que es la aserción de una sola cara que
ya se coló dos veces en esta serie—, y que un sector vacío dice que no aplica.

---

### Task 6: Revisión final

- [ ] Suite y `npm run build` en verde.
- [ ] **La migración contra PostgreSQL real**, con un contenedor desechable
      (`docker run --rm`, parada por nombre explícito, sin `prune` de nada).
      Comprobar que las 117 columnas quedan nullable y sin defecto. Ojo: es la
      tabla más ancha del proyecto; PostgreSQL admite 1600 columnas, así que no
      hay problema, pero conviene verlo.
- [ ] **La suite entera contra PostgreSQL**, que desde el arreglo del
      aislamiento ya se puede: `php -d extension=pdo_pgsql -d extension=pgsql
      vendor/phpunit/phpunit/phpunit`. Debe dar los mismos números que SQLite.
- [ ] Recorrido manual: rellenar unos pocos conteos, comprobar el subtotal en
      vivo, guardar a medias, ver el aviso de matriz sin resultados, completar y
      validar.
- [ ] Actualizar `docs/ESTADO-PROYECTO.md`: novena matriz hecha, queda una.

---

## Fuera de este plan

- **Derivar los atractivos del inventario.** Requiere antes que un inventario
  pueda declararse cerrado por zona.
- **La columna de jerarquía** de la hoja de atractivos, que el instrumento trae
  y no usa en ninguna fórmula.
- **Índice Espacial de Frecuentación**, la última, bloqueada por una fórmula que
  se contradice en su propia hoja.
