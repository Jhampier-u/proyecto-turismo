# Índice de Irritación Turística — diseño

**Fecha:** 7 de agosto de 2026
**Instrumento:** `C:\Users\<usuario>\Downloads\fwdmatrices\Índice de Irritación Turística.xlsx`
**Fuente citada en el instrumento:** elaboración de Numa Sebastián Calle Lituma y Ronal Edison Chaca.

Primera de las cuatro matrices que quedaban sin implementar. Se eligió por ser
la que más se parece a lo ya construido: las otras tres —Involucrados,
Concentración y Frecuentación— no son formularios de criterios y necesitan
decisiones que este documento no aborda.

---

## Qué mide

Cuánto molesta el turismo. Se pregunta por separado a los dos lados que lo
sufren, y por eso el instrumento son dos encuestas paralelas de seis atributos
cada una: una a los **visitantes** y otra a la **localidad receptora**.

**La escala es inversa.** Va de 0 a 10 y un 10 no es un sobresaliente: es
irritación crítica. Es la primera matriz del sistema que funciona así, y es el
detalle que más fácilmente se implementa al revés.

### Los doce atributos

| Sigla | Visitantes | Sigla | Residentes |
|---|---|---|---|
| Cg | Congestión de visitantes en el destino | Cg | Congestión de visitantes en el destino |
| Cs | Calidad percibida de servicios y productos | Is | Impacto social percibido |
| Ca | Calidad percibida de actividades turísticas | Ie | Impacto económico percibido |
| Cv | Calidad de vida de la localidad percibida por el visitante | Ia | Impacto ambiental percibido |
| Ga | Grado de apertura u hospitalidad de la localidad | Cv | Calidad de vida percibida por el residente |
| Sd | Seguridad percibida en el destino | Sd | Seguridad percibida en el destino |

Congestión y Seguridad se preguntan a los dos lados; son atributos distintos con
el mismo nombre, no una repetición: uno recoge la percepción del visitante y el
otro la del residente.

### Fórmulas (literales del original)

```
Promedio de Atributos (visitantes) = AVERAGE(Cg, Cs, Ca, Cv, Ga, Sd)
Promedio de Atributos (residentes) = AVERAGE(Cg, Is, Ie, Ia, Cv, Sd)

Clasificación(x) = SI x >= 7  → «Crítico»
                   SI x >= 3  → «Moderado»
                   SI x <= 2  → «Bajo»
```

Sin pesos: el instrumento promedia los seis atributos por igual. La misma
clasificación se aplica a cada atributo suelto y al promedio del bloque.

### Interpretación (literal del original)

| Rango | Clasificación | Visitantes | Residentes |
|---|---|---|---|
| Menor a 2 | Bajo | Presentan un nivel de aceptación amplio hacia el destino y su dinámica turística. | Presentan un nivel de aceptación amplio hacia el destino y su dinámica turística. |
| De 3 a 6 | Moderado | Empiezan a expresar descontento por la dinámica turística que se desarrolla en el lugar. | Empiezan a expresar descontento por la dinámica turística que se desarrolla en el lugar. |
| Desde 7 | Crítico | Se encuentran en un estado de insatisfacción con la dinámica turística del sitio. | Se encuentran en un estado de insatisfacción con la dinámica turística del sitio. |

### Erratas del instrumento, y qué se asume

- La tabla de rangos de residentes dice «De 3 a 5 → Moderado» mientras la de
  visitantes dice «De 3 a 6», pero su columna de valores enumera «3, 4, 5, 6» y
  **todas** las fórmulas usan `>=3`. Se asume la fórmula: de 3 a 6.
- Las filas 34-39 del original repiten los valores bajo sus siglas. No es una
  segunda tabla: alimenta el gráfico de radar incrustado. No se replica.

---

## Decisiones tomadas

**Una sola matriz con dos bloques, no dos matrices.** El instrumento es uno, y
partirlo en dos entradas del registro sería que el sistema contradijera al
instrumento; además subiría el denominador del progreso de la zona a siete por
lo que es un solo estudio. Valoración Territorial ya tiene esta forma con CT y
UC. El argumento a favor de partirlo era poder validar el trabajo de campo de
residentes sin esperar al de visitantes, y el guardado parcial lo cubre: se
rellena un bloque, se guarda, y se valida cuando estén los dos. Lo que se
pierde es cerrar medio instrumento, que es justo lo que no debería poder
cerrarse.

**Dos resultados, sin índice combinado.** El original no los cruza y no se
inventa aquí un promedio de promedios: mezclaría dos poblaciones distintas en
una cifra que no significa nada.

**Un desplegable, no tarjetas.** Once valores por atributo y doce atributos son
132 controles si se usan tarjetas. Además, `criterio-escala` y
`criterio-pildoras` colorean **por posición dando por hecho que más alto es
mejor**, que es exactamente lo contrario de esta matriz. Se usa un desplegable
de la familia de `select-0-3`, con la clasificación en cada opción —«7 —
Crítico»— porque es información real del instrumento y evita tener que
consultar la tabla de rangos aparte.

**El color se invierte:** 0 en verde, 10 en rojo.

**Grupo «Presión y uso».** Ya está declarado y vacío en `Registro::GRUPOS`,
esperando precisamente a esta matriz y a las otras tres.

---

## Modelo de datos

Tabla `evaluaciones_irritacion`, con la forma que ya tienen las demás:
`zona_id` único, `user_id`, `estado` (`borrador`/`confirmado`), marcas de
tiempo.

**Doce columnas de criterio**, `tinyInteger` nullable sin defecto, con el prefijo
del bloque:

```
vis_congestion, vis_calidad_servicios, vis_calidad_actividades,
vis_calidad_vida, vis_apertura, vis_seguridad

res_congestion, res_impacto_social, res_impacto_economico,
res_impacto_ambiental, res_calidad_vida, res_seguridad
```

Nullable desde la migración de creación, no en una migración posterior: el
guardado parcial ya está en el sistema y una matriz nueva nace con él.

**Dos columnas calculadas**, `decimal(5, 3)` nullable:

```
visitantes_promedio, residentes_promedio
```

**El nombre importa y no es cosmético.** `EstadoZona::esColumnaDeCriterio()`
distingue criterios de columnas calculadas por sufijo `_promedio` y `_total`.
Llamarlas `iit_visitantes` las haría contar como criterios y el «8 de 12
respondidos» de la página de zona mentiría.
`EstadoZonaTest::test_el_filtro_de_criterios_cuadra_con_el_esquema_de_las_seis_tablas`
lo detectaría —está para eso—, pero es mejor no provocarlo.

**La clasificación no se guarda.** `Bajo`/`Moderado`/`Crítico` es función del
promedio, así que es accesorio derivado en el modelo, como el cuadrante de
Valoración Territorial. Guardarla sería una segunda fuente de verdad que se
desincroniza en cuanto alguien corrija un umbral.

---

## Arquitectura

`EvaluacionIrritacionController extends MatrizPonderadaController`. Se hereda
todo lo que ya existe y solo se declara lo propio de esta matriz:

| Método | Qué declara |
|---|---|
| `criterios()` | Los dos bloques con sus seis campos |
| `escala()` | `[0, 10]` |
| `calcular()` | Las dos medias simples |
| `modelo()`, `rutaResultados()` | Los de siempre |
| `mensajeExito()`, `mensajeCerrada()` | Textos propios |

Sale gratis, por herencia: guardado parcial en borrador y obligatoriedad al
confirmar, totales en `null` mientras falte algún criterio, aviso de matriz sin
resultados, cuenta de respondidos en la página de zona, y el control de acceso
por rol.

No hace falta una clase en `App\Matrices`: esas existen para instrumentos
generados desde el Excel —21 pesos y 63 descripciones en Valoración
Territorial—. Aquí son doce nombres y ningún peso; declararlos en el controlador
es menos indirección, no más.

---

## Interfaz

**Formulario.** Dos secciones, una por bloque, con el mismo contador de
respondidos y el botón de borrar respuesta que ya tienen las demás. Encabezando
la página, un aviso de que la escala es inversa: sin él, quien venga de rellenar
Paisaje puntuará al revés.

**Resultados.** Un panel por bloque con su promedio, su clasificación y el texto
de interpretación que corresponda, más el desglose de los seis atributos con la
clasificación de cada uno. Sin cruzar los dos bloques.

Con la matriz a medias, el componente `x-matriz-sin-resultados` que ya existe.

**Página de zona.** Una fila en «Presión y uso», con su recuento de respondidos
y su enlace, sin nada especial: lo resuelve el registro.

---

## Pruebas

- **Del cálculo:** las dos medias, y que un atributo sin responder no baja la
  media de su bloque —heredado, pero conviene fijarlo aquí también porque es la
  primera matriz que nace con guardado parcial—.
- **De los umbrales:** los tres tramos en sus bordes exactos (2, 3, 6, 7), que
  es donde se rompen las clasificaciones.
- **De la escala:** que un 11 se rechaza y un 10 se acepta. La escala más ancha
  del sistema hasta ahora era 0-5.
- **De la máquina de estados:** el jefe confirma, el equipo solo guarda
  borrador, y una confirmada queda cerrada al equipo con su mensaje.
- **Del registro:** los tests de integridad que ya existen recorren las entradas
  declaradas, así que la ruta, el modelo y el recuento de criterios quedan
  cubiertos al añadir la entrada.
- **Del admin:** que ve los resultados en modo lectura y no puede escribir.

---

## Fuera de alcance

- **Las otras tres matrices.** Involucrados es un CRUD de actores, Concentración
  se solapa con el inventario y Frecuentación tiene una fórmula dudosa en el
  original. Cada una necesita su propio diseño.
- **El gráfico de radar** del instrumento original. Las demás matrices tienen
  gráfico; este se puede añadir después sin tocar nada de lo anterior.
- **Encuestar de verdad.** El sistema registra el resultado agregado del trabajo
  de campo, no administra cuestionarios. Igual que Percepción.
