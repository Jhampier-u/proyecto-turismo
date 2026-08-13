> **Este fichero se sobrescribe al empezar cada rama.** Guarda la bitácora de
> **una sola**, la que esté en curso. Antes de arrancar la siguiente, lo que
> merezca sobrevivir tiene que estar volcado en `docs/ESTADO-PROYECTO.md`, que
> es el documento que sí acumula.
>
> Los `*-report.md` de cada tarea sí se quedan, y son el detalle largo. Los
> `*.diff` y los `*-brief.md` no viajan: se derivan de `git diff` y de los
> planes de `docs/superpowers/plans/`, que ya están versionados.

# Progreso — Los restos de la Fase 0

Rama: restos-fase-0
Base de la rama: 4e5a709
Suite en la base: 576 tests

Sin plan escrito: el encargo se clasificó como **acotado** —seis cambios a
código que ya existe y se puede leer— así que el diseño se acordó en la
conversación y no hay fichero de spec. Esta bitácora hace de registro.

Objetivo: cerrar el punto 15 de `docs/ESTADO-PROYECTO.md` §6, la lista de lo
que la revisión final de `fundacion-visual` (FV11) encontró y dejó sin
arreglar con su motivo. Ninguna vista cambia de estructura: esto sigue siendo
Fase 0.

## Lo que se midió antes de empezar, y corrige al encargo

El traspaso se queda corto en dos sitios y largo en uno:

- **«los dos pequeños de `admin/zonas/index`» son seis** —tres en la tabla,
  tres en las tarjetas— y **no son un olvido**: `px-3 py-1.5 text-sm` con
  colores suaves dentro de una tabla densa es la tercera excepción que FV10
  respetó a propósito. Quedan fuera con su motivo.
- **`<x-badge>` tenía dos candidatos que el traspaso no nombra:**
  `frecuentacion/index:146` e `involucrados/index:101` pintan «Completo» con
  `bg-green-100 text-green-800` y «A medias» con `bg-amber-100 text-amber-800`,
  que son letra por letra el fondo y el texto de `validada` y `borrador` en
  `ESTILOS_ESTADO`, sin el borde. Aun así **no se adoptan**: ver la decisión
  más abajo.
- **Los dos contenedores anidados no se arreglan borrándolos.** Esas dos
  vistas son estrechas a propósito; quitar el interior las mandaría a 1440.

## El hallazgo que cambió el tamaño de la rama

`evaluacion_potencialidad/form.blade.php` no solo tiene «colores copiados a
mano», como decía el traspaso. Tiene esto, en la línea 49:

```css
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:...');
.pt-root { font-family:'DM Sans',sans-serif; background:#f0f4f8; min-height:100vh; }
```

**Una tercera tipografía y un segundo fondo de página.** FV4 puso Inter en
todo el sistema y tuvo que corregir `layouts/guest`, que se había quedado
pidiendo Figtree, justo por esto; esta vista se lo salta con un `@import`
remoto. La matriz más grande —156 criterios— es la única pantalla con otra
letra y otro fondo, y **ningún test lo ve**, porque los tests miran HTML y no
qué fuente resuelve el navegador.

Y los hexadecimales copiados no son dos: son `#e2e8f0`, `#1e293b`, `#64748b`,
`#f8fafc`, `#f1f5f9`, `#22c55e`, `#d1d5db`, más seis degradados.

## Decisiones que vienen del diseño y no se replantean

- **Potencialidad se alinea, no se migra.** Fuera la tipografía y el fondo
  propios; el CSS del acordeón, el toggle y el grid se queda como excepción
  documentada. Reescribir 68 líneas de CSS en una vista de 445 con Alpine
  cambiaría el aspecto y arriesgaría regresiones que sus tests —que miran
  radios y campos activos, no maquetación— no verían.
- **`<x-badge>` se queda sin adoptar, con el motivo escrito.** Sus dos
  candidatos pintan completitud de *fila*, no uno de los cinco estados: un
  sitio «Completo» dentro de una lista en borrador no está `validada`, nadie
  lo validó. Adoptarlo ahorraría cuatro copias de color a cambio de que el
  código afirme algo falso —el precedente de Irritación, donde un componente
  de nombre genérico reutilizado con otra semántica habría pintado de verde lo
  peor—. Su primer consumidor natural son las tarjetas de zona de la Fase 2.
- **Ningún test existente se modifica.** Uno que se ponga rojo es la señal de
  que la sustitución cambió comportamiento, no aspecto, y se para. (En
  `fundacion-visual` esta restricción chocó dos veces y las dos veces tenía
  razón: los tests que afirmaban sobre `max-w-7xl` y los que contaban la
  palabra `disabled` en vez del atributo.)
- **Ninguna vista cambia de estructura.** Breadcrumbs, KPIs y columnas son las
  fases 1 a 4.

## Fuera de alcance, y por qué

- Los seis botones de `admin/zonas/index` — excepción deliberada de tamaño.
- `<x-nav-link>` y `<x-responsive-nav-link>`, también de Breeze — son
  navegación, no botones, y la Fase 1 rehace la navbar entera.
- Los dos riesgos de purgado abiertos (`storage/framework/views` dentro del
  `content` de Tailwind, `resources/js` fuera) — son configuración, no restos
  de la Fase 0.

## Tareas

T1 botones de Breeze · T2 tres botones sueltos · T3 `:padding` en
`<x-contenedor>` · T4 `<x-resumen-lista>` a `<x-tarjeta>` · T5 alineación de
Potencialidad · T6 revisión de rama y traspaso.

T1: completa. Diez botones convertidos en ocho ficheros; los tres componentes
de Breeze borrados al quedarse sin un solo uso vivo. Suite 576 -> 578, ningún
test existente tocado.
  - **El riesgo previsto se materializó, y el test lo cazó.** Los dos
    componentes tienen defaults OPUESTOS: `<x-secondary-button>` lleva
    `type="button"` y `<x-boton>` lleva `type="submit"`. El «Cancelar» del
    diálogo de borrar la cuenta vive dentro del `<form>` de borrado, así que
    la conversión directa lo convirtió en un segundo botón de borrar. **Rojo
    verificado**: se hizo la conversión sin el `type` a propósito, el test
    falló, y solo entonces se puso. Ningún test de los que ya había lo habría
    visto —el HTML sigue siendo válido y la página responde 200—.
  - `BotonesPerfilTest` afirma sobre la página servida, no sobre el componente
    en aislado: lo que hay que garantizar no es que `<x-boton>` sepa recibir
    un `type`, sino que esta vista se lo pase. Lleva su contraparte positiva
    —el botón de confirmar sí envía— porque sin ella poner `type="button"` en
    los dos dejaría el primer test verde y el diálogo sin forma de borrar.
  - Los otros siete son envíos limpios dentro de su formulario, comprobados
    uno a uno antes de convertir.
  - `<x-modal>`, `<x-input-label>`, `<x-text-input>` y `<x-input-error>` son
    también de Breeze y se quedan: no son botones y esta tarea no los cubre.
