# Dashboard y espacio de los formularios — diseño

**Fecha:** 12 de agosto de 2026
**Estado:** decisiones tomadas en brainstorming con el responsable del proyecto. **Falta el plan de implementación.**

Dos mejoras de interfaz, independientes entre sí. Se pueden implementar por separado
y en cualquier orden.

---

## 1. El Dashboard — «Continuar donde lo dejaste»

### El problema

`/mis-zonas` (`resources/views/operativo/dashboard.blade.php`) contesta hoy una sola
pregunta: **«¿qué zonas tengo?»**. Un jefe de zona con una o dos zonas ve dos tarjetas
y media pantalla en blanco.

### Qué se descartó, y por qué importa

Se consideraron cuatro caminos y **tres se descartaron a conciencia**:

- **Apretar lo que ya hay** (rejilla de tarjetas más anchas). Descartado porque no
  arregla nada: con dos zonas seguirá pareciendo vacío. **El problema no es el ancho.**
- **Estado del estudio** (franja de cifras: zonas, matrices validadas, a medias).
  Buen segundo paso, pero contesta «¿cómo voy?», que **el panel de cada zona ya
  contesta mejor** y con más detalle.
- **El territorio** (la zona como lugar: descripción, provincia, fotos del inventario).
  Descartado **hoy**, no para siempre: depende de fotos de un inventario que está
  vacío, y hasta que exista el bucket de S3 esas fotos **se borran en cada
  redespliegue**. Reconsiderarlo cuando el bucket exista y haya inventario real.

### La decisión

**El dashboard pasa de ser un índice a ser un punto de partida.** Arriba, lo siguiente
que toca hacer: la última matriz que el usuario tocó y la siguiente sin terminar, con
enlace directo. Las tarjetas de zona se quedan como están, debajo.

El criterio que lo justifica: **«se siente vacío» casi nunca se arregla añadiendo
relleno; se arregla dando algo que hacer.** Y funciona igual con una zona que con
seis, que es justo donde hoy falla.

### Lo que queda por decidir al planificar

- **De dónde sale «la última matriz que tocaste».** No hay hoy ningún registro de
  actividad por usuario. Las evaluaciones tienen `updated_at` y `user_id`, así que
  probablemente se derive de ahí sin tabla nueva — **hay que comprobarlo**, incluidas
  las entradas que no son de tipo `matriz` (Involucrados, Frecuentación) y el
  Inventario, que no tiene estado.
- **Qué se enseña cuando no hay nada empezado**, que es el caso de un usuario nuevo.
  Un panel de «continuar» vacío sería peor que el actual.
- **Qué ve el admin.** Su lista es otra (`admin.zonas.index`) y su relación con el
  trabajo es distinta: escribe borradores pero no valida.

---

## 2. Los formularios — ensanchar y barra lateral fija

### El dato concreto

Los diez formularios de matriz usan `max-w-5xl` (1024 px) mientras
`resources/views/layouts/app.blade.php` ya permite `max-w-7xl` (1280 px). **Se están
dejando 256 px sin usar** antes de reorganizar nada.

### La decisión

Ensanchar a `max-w-7xl` **y gastar el espacio nuevo en una barra lateral fija** que
acompaña al evaluador: cuántos criterios lleva, índice de bloques con los completos
marcados, y el botón de guardar siempre a la vista.

El criterio: los 256 px rinden más **orientando que apretando**. En Potencialidad, con
156 criterios, lo que cansa no es que el formulario sea estrecho, sino perder el sitio
y tener que subir o bajar hasta el final para guardar.

### Lo que se descartó, y este motivo hay que conservarlo

**Dos columnas de criterios.** Parte el desplazamiento por la mitad, y aun así se
descarta: **el orden de los criterios lo fija el instrumento**, y una rejilla de dos
columnas obliga al evaluador a decidir si lee en zigzag o hacia abajo. Es exactamente
la clase de ambigüedad que este proyecto lleva meses quitando de los números; no
conviene meterla en la lectura.

Queda **fuera de alcance, no prohibido**: dos columnas solo en los formularios de
campos cortos —Concentración, con sus 113 casillas numéricas, donde no hay píldoras
que apretar— es defendible más adelante. Empezar con dos maquetaciones a la vez
multiplica lo que hay que probar en diez formularios.

### Lo que queda por decidir al planificar

- **Si la barra lateral es un componente compartido.** Las diez matrices tienen
  estructuras distintas —bloques en FIT, secciones en Potencialidad, categorías en
  Percepción, sin bloques en Concentración—, así que el índice no sale de un sitio
  único. Hay precedente de resolverlo bien (`x-pestanas-matriz`) y de resolverlo mal
  (el ternario repetido en diecinueve vistas que documenta `x-boton-volver`).
- **Qué hace en las matrices que no son formularios de criterios**: Involucrados y
  Frecuentación son CRUD de filas, no tienen bloques que indexar.
- **Comportamiento en pantallas estrechas.** Una barra lateral fija a 1280 px no cabe
  en un portátil pequeño ni en tableta.
- **El recuento de la barra ya existe**: `EstadoZona::criteriosRespondidos()` y el
  contador en vivo de los formularios de Concentración y Potencialidad. Reutilizar,
  no reinventar — y ojo con que **un 0 respondido es un dato, no un hueco**.

---

## Restricciones que aplican a las dos

- Nada por debajo de 14 px salvo insignias. Sin `uppercase`. Clases de Tailwind
  completas, nunca construidas por concatenación.
- Suite en verde. Hoy son **483 tests**.
- Comentarios en castellano explicando el *por qué*.

## Material de apoyo

Las maquetas del brainstorming quedaron en `.superpowers/brainstorm/` (ignorado por
git): `dashboard.html` y `formularios.html`.

## Siguiente paso

**Escribir el plan de implementación**, uno por mejora o uno con dos partes. No hay
nada implementado de este diseño.
