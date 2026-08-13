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

T2: completa. Cuatro botones convertidos, no tres. Suite sigue en 578, ningún
test tocado.
  - **La lista de «cuatro botones sueltos» del traspaso no era de fiar, y en
    las dos direcciones.** En vez de creérsela se hizo un barrido por
    expresión regular sobre todas las vistas.
  - **Sobraban dos:** los de `admin/zonas/index` no son dos sino seis, y son
    la excepción de tamaño que FV10 respetó a propósito —`px-3 py-1.5 text-sm`
    con colores suaves en una tabla densa—. El barrido los saca junto a los
    de `admin/users/index`, `admin/lugares/index`, `inventarios/index`,
    `frecuentacion/index` e `involucrados/index`: son **la misma categoría**,
    no cuatro despistes. Convertirlos exigiría un `tamano="pequeno"` en el
    primitivo, que no es esta tarea.
  - **Faltaba uno, y es el que más se ve:** `<x-matriz-sin-resultados>` tenía
    su «Ir al formulario» escrito a mano (`bg-gray-200 text-black`), y es un
    **componente compartido por cinco matrices**. El traspaso no lo lista
    porque su lista salió de mirar vistas, no componentes.
  - **Corrección a mi propio diseño:** dije que el de Paisaje iba a
    `secundario`. Al mirar las otras cuatro pantallas iguales —Concentración,
    Irritación, Potencialidad y Valoración Territorial ya tienen ese mismo
    «← Volver al Formulario» como `<x-boton>` primario— quedó claro que
    `secundario` lo habría dejado distinto igual, solo que de otra manera.
    Va a primario, como sus cuatro hermanos.
  - El amarillo de Valoración Territorial era del panel de aviso que lo
    rodea, no del botón: el panel sigue amarillo y el botón se une al resto.
  - El «← Volver al listado» de `inventarios/show` se queda como enlace de
    texto: no es un botón y convertirlo sería inventar uno.

T3: completa. `<x-contenedor>` gana `:padding`, y los dos anidados dejan de
aplicarlo dos veces. Suite 578 -> 580, ningún test tocado. Rojo verificado.
  - El arreglo **no es borrar los contenedores interiores**, que es lo primero
    que parece al leer «contenedor anidado»: `admin/lugares/form` y
    `operativo/frecuentacion/form` son estrechos a propósito —un formulario de
    cuatro campos a 1440px es peor, no mejor— y borrarlos los mandaría a 1440.
    Lo único que sobra al anidar es el padding.
  - Mismo prop, mismo nombre y misma razón que `<x-tarjeta :padding="false">`,
    que existe desde FV6. No se inventa un mecanismo nuevo para un problema
    que el sistema ya sabía resolver.
  - Dos tests: uno fija el padding por defecto —que nadie afirmaba, y es lo
    que separa el contenido del borde de la ventana en móvil, así que
    perderlo no se ve en escritorio— y otro que sin él no sale, conservando
    el ancho.

T4: completa. `<x-resumen-lista>` pasa a `<x-tarjeta>`. Suite 580 -> 581,
ningún test tocado.
  - **La premisa falsa era falsa, comprobado y no supuesto.** La revisión de
    `fundacion-visual` dejó la franja fuera creyendo que convertirla obligaba
    a mover su `flex` a un hijo. `$attributes->merge` concatena sobre el
    `<div>` del componente, así que no.
  - El test nuevo afirma las dos mitades **en la misma etiqueta**: el
    `border-gray-200/80` y el `rounded-xl` del sistema junto al `flex`,
    `justify-between` y `p-4` propios. Comprobarlas por separado pasaría en
    verde con la franja partida en dos divs, que es justo lo que se quería
    evitar: `flex` y `justify-*` actúan sobre hijos directos.
  - `:padding="false"` conservando el `p-4`: la franja va pegada sobre una
    tabla y el `p-6` del componente la engordaría. Misma regla que siguió
    `profile/edit` con su `p-4 sm:p-8` en FV8.
  - El botón verde de validar **se queda verde**: esa sí es una excepción real
    y deliberada de FV10, no un resto.
  - Cambia el radio de `sm:rounded-lg` (8px) a `rounded-xl` (12px) y gana el
    borde. Es la unificación que persigue la fase, igual que las tarjetas de
    `admin/dashboard` en FV8.

T5: completa, y creció con un hallazgo consultado. Suite 583 -> 584.
  - Fuera el `@import` a otro proveedor de fuentes con DM Sans, el
    `font-family` de `.pt-root`, el `background:#f0f4f8` y el
    `min-height:100vh`. Era la única pantalla de la aplicación con otra letra
    y otro fondo.
  - `TipografiaUnicaTest`, tres tests: dos sobre el fuente de las 84 vistas
    —quitando antes los comentarios de Blade, porque si no el guardián
    fallaría contra la explicación de su propio hallazgo— y uno sobre el HTML
    servido de la página que tenía el defecto. **Rojo verificado** volviendo a
    meter el `@import`.
  - Dos grises huérfanos corregidos: `#d1d5db` y `#374151` son `gray-300` y
    `gray-700` de la paleta ANTERIOR a FV4. Nadie los actualizó cuando `gray`
    pasó a ser alias de `slate`, que es exactamente lo que le pasa a una copia.
    Los demás hexadecimales quedan anotados con el token del que son copia.
  - La bomba de `area-{{ $color }}` **no se desactiva**, se documenta: hoy no
    explota porque `area-*` es CSS propio y no de Tailwind.

T5b: los repintados con `!important`, consultado antes de meterlo porque no
estaba en el diseño y cambia el aspecto de cuatro pantallas. Suite sigue en
584.
  - **FV11 arregló el componente y dio por cerrado el defecto, pero no tocó
    las llamadas.** `<x-boton-volver>` delega en `<x-boton
    variante="secundario">` desde entonces, y cuatro vistas seguían
    repintándolo con el modificador `!` de Tailwind, que gana a las clases del
    componente: azul sólido en `vtt/resultado` e `inventarios/index`, gris
    sólido en `potencialidad/ponderacion`, enlace de texto azul en
    `potencialidad/form`. El mismo botón, cuatro aspectos.
  - Ninguno era decisión de su pantalla: los comentarios que los justificaban
    son de antes de que existiera el sistema de botones. Concentración e
    Irritación ya ponen `<x-boton-volver :zona="$zona" />` a secas junto a su
    primario, y a esa forma se igualan las cuatro.
  - Un quinto botón a mano en la cabecera de `potencialidad/form`, con
    `style=` en línea. **Por eso ningún barrido lo encontraba: buscaban por
    `class`.** Es el segundo caso de esta rama en que la lista heredada de lo
    pendiente falla por cómo se buscó, no por lo que había.

### Lo que no se pudo verificar, y no se da por hecho

**La comprobación en navegador de T5 no se hizo:** Playwright no está
instalado en esta máquina, y ponerlo con su Chromium es un cambio de entorno
que no estaba en el encargo. Lo que sí se verificó, y hasta dónde llega:

- El CSS construido declara **solo** `Inter` para `font-sans`; ni rastro de
  DM Sans, googleapis o gstatic en `public/build`.
- El HTML servido del formulario de Potencialidad no contiene `@import`,
  `font-family` ni ningún proveedor ajeno, y sí el `fonts.bunny.net` del
  layout.

Queda sin cubrir **qué fuente acaba dibujando el navegador**, que depende de
que bunny.net responda y de las fuentes de la máquina. La causa sí está
atada: en esa página ya no se pide ninguna familia, así que la única que
gobierna es la del layout.

**Anotado para la Fase 1, no arreglado aquí:** las cinco páginas de
resultados que tienen «← Volver al Formulario» al pie llevan ya un
`<x-pestanas-matriz>` arriba con esa misma navegación. El botón duplica la
pestaña. Es estructura de página, que es justo lo que esta fase no toca.
