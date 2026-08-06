# Matriz de Valoración Territorial — diseño

**Fecha:** 6 de agosto de 2026
**Estado:** aprobado, pendiente de implementar
**Fuente:** `Matriz de Valoración Territorial.xlsx` — Lic. Numa Sebastián Calle Lituma
y Lic. Ronal Edison Chaca Espinoza

---

## Qué mide la matriz

Evalúa si un territorio está en condiciones de **soportar** actividad turística. No
valora atractivos: un territorio con recursos excepcionales puntúa bajo si carece de
agua potable o de carretera.

Dos dimensiones independientes, cada una en escala 0–2:

- **CT — Contenido Territorial** (12 criterios): lo que el territorio tiene por
  dentro. Servicios básicos, servicios sociales, tejido social y patrimonio.
- **UC — Ubicación y Conectividad** (9 criterios): lo que lo conecta con el exterior.
  Vialidad, transporte, distancias y señalización.

Cada criterio se califica 0, 1 o 2 según descripciones textuales específicas, y se
pondera. Los pesos de cada dimensión suman 1, por lo que ambos totales van de 0 a 2.

### Fórmulas (literales del original)

```
CT = f(EE)·10% + f(AP)·10% + f(SC)·5%  + f(RB)·10% + f(PS)·5% + f(SS)·5%
   + f(SG)·5%  + f(CR)·10% + f(AE)·10% + f(OS)·10% + f(DC)·10% + f(DN)·10%

UC = f(V)·15%  + f(IC)·10% + f(FC)·10% + f(DT)·10% + f(DS)·10%
   + f(DD)·10% + f(DM)·10% + f(CO)·15% + f(S)·10%
```

### Cuadrantes (literales del original)

| Cuadrante | Condición | Lectura |
|---|---|---|
| Territorio No Apto para el Turismo | UC < 1; CT < 1 | Ni contenido ni conexión |
| Territorio con Limitación II | UC > 1; CT < 1 | Bien conectado, sin base territorial |
| Territorio con Limitación III | UC < 1; CT > 1 | Con base territorial, aislado |
| Territorio a Priorizar para el Turismo IV | CT > 1; UC > 1 | Apto en ambas dimensiones |

Ejes del gráfico: **X = Contenido Territorial**, **Y = Ubicación y Conectividad**.

---

## Decisiones tomadas

1. **Unidad de evaluación: la zona.** Igual que las otras cinco matrices. Hereda el
   middleware `PerteneceAZona` y el flujo borrador → confirmado.

2. **Sin vista comparativa.** El original enfrenta hasta 10 sitios, pero se
   implementa solo el resultado por zona. Una comparativa entre zonas sería una
   pantalla nueva sin equivalente en el resto del sistema; queda fuera de alcance.

3. **El umbral 1.00 cuenta como alto (`>= 1`).** Las condiciones del original usan
   desigualdades estrictas, de modo que el valor exacto 1.00 no caería en ningún
   cuadrante — y no es un caso raro: **calificar todos los criterios con 1 da
   exactamente 1.00**, porque los pesos suman 1. Se resuelve con `>= 1`, que además
   es lo que ya hace Potencialidad, dejando ambas matrices consistentes.

---

## Arquitectura

Se introduce una jerarquía de dos niveles antes de implementar la matriz. Los cuatro
controladores de evaluación existentes repiten el mismo `update()` salvo el bloque de
cálculo; con seis matrices más en camino, esa duplicación se multiplicaría. (La
auditoría ya mostró el coste: el fallo de `accion_estado` sin validar estaba replicado
en los cuatro.)

### Nivel 1 — `EvaluacionZonaController` (abstracta)

Concentra la máquina de estados y la persistencia, que es lo único idéntico en todas:

```php
abstract protected function modelo(): string;           // FQCN del modelo
abstract protected function rutaResultados(): string;   // nombre de ruta
abstract protected function prepararDatos(Request $r, $zonaId, ?Model $actual): array;

protected function despuesDeGuardar($zonaId, User $user): void {}   // hook opcional
protected function mensajeExito(string $estado, array $datos): string;
```

El `update()` de la base: carga la evaluación actual, bloquea si está confirmada y
quien edita es del equipo, valida `accion_estado`, delega en `prepararDatos()`,
resuelve el estado según el rol, hace `updateOrCreate` y redirige.

`despuesDeGuardar()` existe para que FIT y FET registren la instantánea del VTT sin
que la base sepa de su existencia.

**Potencialidad extiende aquí directamente**, porque su flujo es atípico: valida en
dos pasadas, persiste la tabla lateral `potencialidad_campos_activos` y preserva los
valores de los campos inactivos. Todo eso vive dentro de su `prepararDatos()`.

### Nivel 2 — `MatrizPonderadaController extends EvaluacionZonaController`

Para la familia «criterios fijos, escala fija, suma ponderada»: FIT, FET, Percepción y
Valoración Territorial. Implementa `prepararDatos()` a partir de tres declaraciones:

```php
protected function criterios(): array;   // dimensión => [campo => peso]
protected function escala(): array;      // [min, max]
abstract protected function calcular(array $valores): array;
```

Construye las reglas de validación desde `criterios()` y `escala()`, así que cada
matriz aporta únicamente su declaración y su cálculo.

**Por qué dos niveles y no uno:** un nivel único obligaría a Potencialidad a fingir que
es una matriz ponderada simple, y la base acabaría con condicionales para acomodarla.
Se descartó por la misma razón un motor genérico por configuración: las matrices
difieren en escala (0–2, 0–3, 1–3), en campos activables y en campos de texto libre, y
esas diferencias son de fondo, no de forma.

### Orden de trabajo y riesgo

El refactor toca cuatro controladores en funcionamiento. Mitigación: los tests de
cálculo (FIT máximo, FIT por bloque, FET ponderado, Percepción normalizada) y los de
flujo borrador/confirmado ya existen y están verdes. Se migra **un controlador,
se corre la suite, y solo entonces el siguiente**.

---

## Modelo de datos

Tabla `evaluaciones_valoracion_territorial`:

```
id
zona_id      FK → zonas, cascade, UNIQUE
user_id      FK → users, nullOnDelete
estado       enum('borrador','confirmado') default 'borrador'

-- 12 criterios CT, tinyInteger default 0
ct_energia_electrica  ct_agua_potable          ct_comunicacion
ct_recoleccion_basura ct_problemas_sociales    ct_salud
ct_seguridad          ct_conservacion_recursos ct_actividad_economica
ct_organizacion_social ct_elementos_culturales ct_espacios_naturales

-- 9 criterios UC, tinyInteger default 0
uc_vialidad                     uc_infraestructura_conectividad
uc_frecuencia_conectividad      uc_distancia_atractivo
uc_distancia_sitio_visita       uc_distancia_destino
uc_distancia_mercado_emisor     uc_conglomeracion_oferta
uc_senalizacion

-- calculados, decimal(5,3) default 0
ct_total
uc_total

timestamps
```

Convenciones seguidas: criterios como `tinyInteger`, calculados como `decimal(5,3)`,
prefijo por dimensión, `unique(zona_id)` desde el inicio.

**Los pesos no van en la base de datos.** Son parte de la metodología publicada, no
configuración del usuario: viven en `criterios()` del controlador, igual que en FIT y
FET, y no son alterables desde la aplicación.

**El cuadrante no se almacena.** Es función pura de `ct_total` y `uc_total`, así que se
expone como accesor:

```php
public function getCuadranteAttribute(): string
{
    $ct = $this->ct_total >= 1;
    $uc = $this->uc_total >= 1;

    return match (true) {
        $ct && $uc  => 'Territorio a Priorizar para el Turismo IV',
        !$ct && $uc => 'Territorio con Limitación II',
        $ct && !$uc => 'Territorio con Limitación III',
        default     => 'Territorio No Apto para el Turismo',
    };
}
```

Decisión deliberada: el VTT sí almacenaba su resultado y por eso podía quedar
desfasado respecto a los FIT y FET que lo originaban (hallazgo M3 de la auditoría).
Derivándolo, el cuadrante no puede mentir.

Casts: `ct_total` y `uc_total` a `float`.

---

## Interfaz

### Formulario

**La descripción es la opción.** Cada criterio se presenta como tres tarjetas
seleccionables con el texto completo de su nivel:

```
Vialidad
┌──────────────────────┬──────────────────────┬──────────────────────┐
│ 0                    │ 1                    │ 2                    │
│ Vías de tercer orden │ Vías de segundo      │ Vías de primer orden │
│ en mal estado        │ orden con deterioro  │ con mantenimiento    │
│                      │ considerable         │ adecuado             │
└──────────────────────┴──────────────────────┴──────────────────────┘
```

Los textos de los 21 criterios están en las hojas `Contenido Territorial` y
`Ubicación y Conectividad` del original y se transcriben literalmente.

Se descartó reutilizar `<x-select-0-2>` con sus etiquetas genéricas («Ausencia /
Fragilidad / Aprovechable») porque obligaría al evaluador a trabajar con el Excel
abierto al lado, que es justo lo que la matriz pretende evitar: sin los anclajes
textuales, dos evaluadores puntúan distinto.

Organización: dos secciones plegables (CT y UC) con subtotal en vivo mediante Alpine,
que el formulario de Potencialidad ya usa. En móvil las tarjetas se apilan.

Al pie de cada sección, la columna **Fuente** del original: PDOT y fuentes oficiales
para servicios, visitas in situ para elementos culturales y naturales.

### Resultados

Mismo formato que Potencialidad:

- Los dos totales, CT y UC.
- Tarjeta del cuadrante con su nombre y su lectura.
- Gráfico de dispersión con el punto de la zona y los cuatro cuadrantes rotulados,
  corte en 1.00 en ambos ejes.
- **Tabla por dimensión** con criterio, calificación, peso y aporte, con el total al
  pie — réplica de las hojas `Valoración CT` y `Valoración UC`. Es lo que permite
  explicar por qué el territorio cayó donde cayó; sin ella el cuadrante es un
  veredicto sin argumento.

Los datos de las gráficas se pasan con `@json()` sobre variables, nunca sobre arrays
literales: la directiva separa su argumento por comas y truncaría el array.

### Integración

- Tarjeta de estado en `operativo/dashboard`, como el resto de matrices.
- Vista de solo lectura para el administrador en
  `/admin/zona/{zona}/valoracion-territorial`.
- Rutas dentro del grupo `operativo/zona/{zona}`, protegidas por `PerteneceAZona`.

---

## Pruebas

### Del refactor

Las 67 pruebas existentes deben permanecer verdes durante toda la extracción de la
base. Se ejecutan **después de migrar cada controlador**, no al final.

### De la matriz

- **Pesos:** que los de cada dimensión sumen exactamente 1. Un peso mal tecleado
  sesgaría todas las evaluaciones en silencio, y es el error más fácil de cometer al
  transcribir 21 criterios.
- **Cálculo con todo en 2:** CT = 2 y UC = 2.
- **Cálculo con pesos diferenciados:** solo Vialidad en 2 y el resto en 0 → UC = 0.30.
- **El caso del umbral:** todos los criterios en 1 → CT = 1.000 y UC = 1.000 exactos, y
  el cuadrante resultante es «Territorio a Priorizar para el Turismo IV». Fija por
  escrito la decisión 3.
- **Los cuatro cuadrantes**, uno por combinación.
- **Flujo:** el equipo guarda borrador aunque envíe `accion_estado=confirmado`; el jefe
  confirma; una evaluación confirmada queda cerrada para el equipo.
- **Autorización:** la ruta responde 403 desde una zona ajena.
- **Validación:** `accion_estado` inválido devuelve error, no un 500.
- **Renderizado** de la vista de resultados.

---

## Fuera de alcance

- Comparativa entre zonas (decisión 2).
- Las otras cinco matrices del lote, que se abordarán una por una.
- Migración de datos: es una matriz nueva, no hay histórico que convertir.
