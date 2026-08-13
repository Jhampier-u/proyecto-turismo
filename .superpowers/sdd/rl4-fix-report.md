# Informe — arreglo de los hallazgos de la revisión final (rama resumen-lista)

## Estado: DONE

## Commit

- `163d0f6` — fix(listas): la franja de resumen deja de contradecir el bloqueo por ST

Un solo commit: cubre los seis hallazgos (código + tests) porque están todos
entrelazados en las mismas dos pantallas (franja de Frecuentación + pestaña
de Resultados) y separarlos en dos commits habría dejado alguno de los dos a
medias (p. ej. el fix de `pestanas-matriz.blade.php` sin el test que lo
verifica, o el test 6a en rojo sin el fix que lo pone en verde).

`package-lock.json` sigue modificado en el árbol de trabajo y **no se
incluyó** en el commit, tal como se pidió.

## Salida literal de `php artisan test` (ejecución final, tras todos los arreglos)

```
Tests:    552 passed (3262 assertions)
Duration: 32.56s
```

Baseline antes de tocar nada: **547 passed (3249 assertions)**. Los 5 tests
nuevos son: Hallazgo 6a (Frecuentación), Hallazgo 6b ×2 (Involucrados y
Frecuentación), la guarda del Hallazgo 4 (ResumenListaTest) y una cobertura
añadida para el Hallazgo 2 (PestanasMatrizTest). Dos tests existentes se
**renombraron** (Hallazgo 5) sin tocar sus aserciones, así que no suman al
conteo. +13 aserciones en total.

## Hallazgo 6b: ¿era `! $confirmada` realmente borrable?

**Sí.** Se verificó a mano, antes de escribir ningún test nuevo: se quitó
`! $confirmada &&` de `puedeValidar` en `InvolucradosController::index()` y
en `FrecuentacionController::index()`, y se corrió la suite completa
(`php artisan test`) con esa mutación aplicada. Resultado: **547 passed**,
sin ningún rojo — ni en `InvolucradosTest`, ni en `FrecuentacionTest`, ni en
ningún otro archivo. Confirmado esto, se revirtió la mutación y se
añadieron los dos tests nuevos
(`test_el_jefe_no_ve_el_boton_con_la_lista_validada_y_completa`, uno por
controlador) que sí lo habrían detectado.

## Qué se hizo, hallazgo por hallazgo

1. **Franja en verde sobre un estado bloqueado.** `<x-resumen-lista>` ya no
   fija el color de su ranura (`resources/views/components/resumen-lista.blade.php:61-63`):
   ahora es un `<span>` sin clase, con un comentario explicando por qué -la
   ranura la usa Frecuentación para la ST, que a veces es un dato neutro y a
   veces el único motivo de bloqueo, y ese significado lo conoce la vista,
   no el componente-. `resources/views/operativo/frecuentacion/index.blade.php:75-89`
   ahora pinta dos ramas: `ST: {{ $config->st }}` en gris si `$stDefinida`,
   o `falta la Superficie Territorial` en ámbar si no -misma frase que
   `EstadoZona.php:525`-.

2. **La pestaña se contradecía con la franja.**
   `resources/views/components/pestanas-matriz.blade.php:43-54` separa las
   dos condiciones de la rama `'sitios'` en `$sitiosCompletos` y
   `$stDefinida` con nombre propio (`$completa` sigue significando
   exactamente lo mismo: `$sitiosCompletos && $stDefinida`). El candado
   (líneas 83-91) ahora dice `falta la Superficie Territorial` cuando ese es
   el único motivo, y se queda con `sin sitios completos` en cualquier otro
   caso -incluida la zona vacía, que sigue diciendo lo mismo que antes-. No
   se tocaron las otras ramas del componente (actores, criterios).

3. **Singular con total en cadena.**
   `resources/views/components/resumen-lista.blade.php:47`: `$total === 1`
   pasó a `(int) $total === 1`.

4. **`puedeValidar` sin ruta.** Mismo patrón que
   `<x-barra-lateral-formulario>` (revienta con `InvalidArgumentException`
   si los props llegan a medias): `resources/views/components/resumen-lista.blade.php:35-45`
   ahora exige `rutaValidar` cuando `puedeValidar` es cierto. Test nuevo en
   `tests/Feature/ResumenListaTest.php::test_puede_validar_sin_ruta_revienta`
   (comprueba `\Illuminate\View\ViewException`, no la excepción original:
   Blade envuelve cualquier excepción del render, igual que ya hacía
   `BarraLateralFormularioTest`).

5. **Nombres de test que prometían lo que no comprobaban.** Renombrados en
   `tests/Feature/InvolucradosTest.php` y `tests/Feature/FrecuentacionTest.php`:
   `test_el_boton_de_validar_esta_arriba_y_no_al_final` →
   `test_no_queda_un_segundo_boton_de_validar_al_final`. Aserciones sin
   tocar. Se actualizó también un comentario en `FrecuentacionTest.php` que
   citaba el nombre viejo del test hermano en `InvolucradosTest.php`.

6. **Dos estados sin test, TDD real.**
   - **6a**: `test_con_sitios_completos_y_sin_st_el_jefe_ve_el_aviso_ambar_y_no_el_boton`
     en `FrecuentacionTest.php`. Escrito con la frase ya arreglada
     (`falta la Superficie Territorial`); corrido contra el código sin tocar
     dio **rojo** de verdad (`assertSee` fallaba porque esa frase no existía
     todavía, el texto real era `ST sin responder` en gris) -confirmando que
     el test ejercía justo el bug del Hallazgo 1-. Verde tras el arreglo 1.
   - **6b**: descrito arriba. No hubo "romper y ver fallar" en el sentido de
     que el test naciera en rojo -el código ya era correcto-, sino la
     verificación de mutación manual descrita más arriba, que es la forma en
     que este caso concreto pedía demostrarse.
   - Añadido además un test para el Hallazgo 2 en `PestanasMatrizTest.php`
     (`test_sitios_completos_sin_st_dice_el_motivo_real_en_vez_de_sin_sitios_completos`),
     que no estaba pedido explícitamente como parte del Hallazgo 6 pero
     cierra el hueco de cobertura que el propio hallazgo describe.

## Tests existentes actualizados (no por regresión, sino porque fijaban la contradicción)

- `FrecuentacionTest::test_la_franja_avisa_si_falta_la_superficie_territorial`
  y `test_con_superficie_definida_la_franja_la_muestra`: la aserción
  `'ST sin responder'` pasó a `'falta la Superficie Territorial'` -es
  exactamente el texto que el Hallazgo 1 pedía cambiar, así que estos dos
  tests estaban fijando el estado viejo (correctamente, para lo que existía
  entonces) y ahora fijan el nuevo.

## Dudas / cosas que no encajaron del todo

- Ninguna duda de diseño. Un detalle de ejecución: para verificar 6b hubo
  que mutar temporalmente ambos controladores en el árbol de trabajo,
  correr la suite y revertir -no se commiteó ninguna versión intermedia-.
- Los documentos en `docs/superpowers/plans/2026-08-12-resumen-lista.md` y
  `docs/superpowers/specs/2026-08-12-resumen-lista-design.md` todavía citan
  el texto viejo (`ST sin responder`, el nombre de test viejo): son
  documentos de planificación histórica, no código ni tests, así que no se
  tocaron.
