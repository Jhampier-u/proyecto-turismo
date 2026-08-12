<?php

namespace App\Servicios;

use App\Matrices\Registro;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Traduce una zona y quien la mira a las filas de su página.
 *
 * Toda la lógica de «si está confirmada lleva a resultados, si no al
 * formulario» vivía repetida en los @php de cada vista. Aquí está una vez y
 * se puede probar sin levantar HTTP.
 */
final class EstadoZona
{
    /** @var array<string, ?Model> evaluación cargada por clave de matriz */
    private array $evaluaciones = [];

    public function __construct(
        private readonly Zona $zona,
        private readonly User $usuario,
    ) {
        foreach (Registro::matrices() as $clave => $entrada) {
            $modelo = $entrada['modelo'];

            $this->evaluaciones[$clave] = $modelo::where('zona_id', $this->zona->id)->first();
        }
    }

    public function papel(): string
    {
        return match (true) {
            $this->usuario->esAdmin() => 'admin',
            $this->usuario->esJefe()  => 'jefe',
            default                   => 'equipo',
        };
    }

    public function totalMatrices(): int
    {
        return count(Registro::matrices());
    }

    public function validadas(): int
    {
        return count(array_filter(
            $this->evaluaciones,
            fn(?Model $e) => $e !== null && $e->estado === 'confirmado'
        ));
    }

    /**
     * Progreso de varias zonas con un número fijo de consultas.
     *
     * El dashboard solo necesita el recuento, no las filas resueltas. Instanciar
     * un EstadoZona por zona costaba seis consultas por zona; esto son seis en
     * total, haya una zona o cincuenta.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Zona>  $zonas
     * @return array<int, array{hechas: int, total: int}>  indexado por zona_id
     */
    public static function progresoDe(Collection $zonas): array
    {
        $ids   = $zonas->pluck('id');
        $total = count(Registro::matrices());

        // Arranca en 0 para que toda zona pedida aparezca en el resultado,
        // incluidas las que no tengan ninguna evaluación todavía.
        $hechasPorZona = $ids->mapWithKeys(fn(int $id) => [$id => 0])->all();

        foreach (Registro::matrices() as $entrada) {
            $modelo = $entrada['modelo'];

            $confirmadas = $modelo::whereIn('zona_id', $ids)
                ->where('estado', 'confirmado')
                ->pluck('zona_id');

            foreach ($confirmadas as $zonaId) {
                $hechasPorZona[$zonaId]++;
            }
        }

        return $ids->mapWithKeys(fn(int $id) => [$id => [
            'hechas' => $hechasPorZona[$id],
            'total'  => $total,
        ]])->all();
    }

    /** @return array<string, array{titulo: string, filas: list<FilaMatriz>}> */
    public function grupos(): array
    {
        $grupos = [];

        foreach (Registro::GRUPOS as $clave => $grupo) {
            $entradas = Registro::deGrupo($clave);

            if ($entradas === []) {
                continue;
            }

            $grupos[$clave] = [
                'titulo' => $grupo['titulo'],
                'filas'  => array_values(array_map(
                    fn($entradaClave) => $this->fila($entradaClave),
                    array_keys($entradas)
                )),
            ];
        }

        return $grupos;
    }

    /**
     * Una sola fila, por clave. grupos() ya resuelve todas para pintar la
     * página de zona entera; esto es la misma resolución cuando solo hace
     * falta una -el caso de proximoPaso(), que no necesita el resto-.
     */
    public function filaDeClave(string $clave): FilaMatriz
    {
        return $this->fila($clave);
    }

    /**
     * «Lo siguiente que toca hacer»: la matriz que el usuario tocó por
     * última vez y la primera que todavía no ha terminado, cada una en su
     * zona. Alimenta el panel de arriba del dashboard operativo.
     *
     * Dos preguntas distintas -"¿por dónde sigo?" y "¿qué no he ni
     * empezado?"- que casi siempre señalan a matrices distintas. Cuando
     * coinciden -lo normal si se trabaja en el orden del registro- se
     * fusionan en una sola entrada para no repetir la misma tarjeta dos
     * veces.
     *
     * $progreso se recibe ya calculado -progresoDe($zonas)- en vez de
     * volver a pedirlo aquí: el dashboard ya lo necesita para las tarjetas
     * de zona de más abajo, y pedirlo dos veces duplicaría sus consultas
     * sin ganar nada.
     *
     * Coste fijo, no por zona: como mucho una consulta por cada uno de los
     * diez modelos validables para "última tocada", y como mucho dos
     * instancias de EstadoZona -coste fijo cada una, ver su constructor-
     * para resolver la fila completa de "última" y de "siguiente". El
     * número de zonas no multiplica el número de consultas, mismo
     * principio que progresoDe().
     *
     * @param  Collection<int, Zona>  $zonas
     * @param  array<int, array{hechas: int, total: int}>  $progreso  de progresoDe($zonas)
     * @return array{ultima: ?array{zona: Zona, fila: FilaMatriz}, siguiente: ?array{zona: Zona, fila: FilaMatriz}, fusionado: bool}
     */
    public static function proximoPaso(User $usuario, Collection $zonas, array $progreso): array
    {
        if ($zonas->isEmpty()) {
            return ['ultima' => null, 'siguiente' => null, 'fusionado' => false];
        }

        $ultima    = self::ultimaTocadaPor($usuario, $zonas);
        $siguiente = self::siguientePendiente($usuario, $zonas, $progreso);

        $fusionado = $ultima !== null
            && $siguiente !== null
            && $ultima['zona']->id === $siguiente['zona']->id
            && $ultima['fila']->clave === $siguiente['fila']->clave;

        return [
            'ultima'    => $ultima,
            'siguiente' => $fusionado ? null : $siguiente,
            'fusionado' => $fusionado,
        ];
    }

    /**
     * La matriz validable con el updated_at más reciente entre las que este
     * usuario guardó él mismo (user_id), dentro de sus zonas.
     *
     * Fiable para las ocho matrices de tipo 'matriz': EvaluacionZonaController
     * ::update() fija user_id y toca updated_at en cada guardado, borrador o
     * confirmado, sin excepción -ver su Step 96-. NO es igual de fiable para
     * 'actores' e 'sitios': sus tablas de configuración solo fijan user_id
     * al validar, reabrir, o -en Frecuentación- guardar la Superficie
     * Territorial, nunca por el simple alta/edición/borrado de una fila
     * mientras la lista sigue en borrador sin validar nunca; y las filas de
     * detalle (Involucrado, SitioFrecuentacion) no tienen columna user_id en
     * absoluto. Es una limitación conocida -documentada en el plan, no un
     * bug- y no la ensancha ninguna consulta de más: si no hay user_id que
     * mirar, esa matriz simplemente no compite por "última tocada".
     */
    private static function ultimaTocadaPor(User $usuario, Collection $zonas): ?array
    {
        $idsZonas   = $zonas->pluck('id');
        $zonasPorId = $zonas->keyBy('id');

        $mejorClave = null;
        $mejorZona  = null;
        $mejorFecha = null;

        foreach (Registro::matrices() as $clave => $entrada) {
            $modelo = $entrada['modelo'];

            $fila = $modelo::where('user_id', $usuario->id)
                ->whereIn('zona_id', $idsZonas)
                ->whereNotNull('updated_at')
                ->orderByDesc('updated_at')
                ->first(['zona_id', 'updated_at']);

            if ($fila === null) {
                continue;
            }

            if ($mejorFecha === null || $fila->updated_at->gt($mejorFecha)) {
                $mejorClave = $clave;
                $mejorZona  = $zonasPorId[$fila->zona_id];
                $mejorFecha = $fila->updated_at;
            }
        }

        if ($mejorClave === null) {
            return null;
        }

        $estado = new self($mejorZona, $usuario);

        return ['zona' => $mejorZona, 'fila' => $estado->filaDeClave($mejorClave)];
    }

    /**
     * La primera matriz validable, en el orden de declaración del registro,
     * que no está validada -explorando las zonas en el orden recibido y
     * deteniéndose en la primera que tenga algo pendiente-.
     *
     * $progreso -de coste fijo, ya calculado por el dashboard- decide SOLO
     * por qué zona empezar; resolver la fila en sí -con su nombre, su
     * detalle y su enlace- solo cuesta una instancia de EstadoZona, y como
     * mucho una, no una por zona.
     */
    private static function siguientePendiente(User $usuario, Collection $zonas, array $progreso): ?array
    {
        foreach ($zonas as $zona) {
            $p = $progreso[$zona->id] ?? null;

            if ($p === null || $p['hechas'] >= $p['total']) {
                continue;
            }

            $estado = new self($zona, $usuario);

            foreach (Registro::matrices() as $clave => $entrada) {
                $fila = $estado->filaDeClave($clave);

                if ($fila->estado !== 'validada') {
                    return ['zona' => $zona, 'fila' => $fila];
                }
            }
        }

        return null;
    }

    private function fila(string $clave): FilaMatriz
    {
        $entrada = Registro::ENTRADAS[$clave];

        return match ($entrada['tipo']) {
            'inventario' => $this->filaInventario($clave, $entrada),
            'resultado'  => $this->filaResultado($clave, $entrada),
            'actores'    => $this->filaActores($clave, $entrada),
            'sitios'     => $this->filaSitios($clave, $entrada),
            default      => $this->filaMatriz($clave, $entrada),
        };
    }

    private function filaInventario(string $clave, array $entrada): FilaMatriz
    {
        $cuantos = $this->zona->inventarios()->count();

        // El admin gestiona el inventario como uno más -crea, edita y borra
        // recursos- desde que PerteneceAZona dejó de restringirlo a métodos
        // seguros: 'Abrir' es cierto para los tres roles.
        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'sin_estado',
            detalle: $cuantos === 1 ? '1 recurso registrado' : "{$cuantos} recursos registrados",
            url:     route($entrada['rutas']['editar'], $this->zona->id),
            accion:  'Abrir',
        );
    }

    /**
     * Un resultado derivado no se rellena: o está disponible porque sus
     * dependencias están validadas, o está bloqueado y dice cuáles faltan.
     */
    private function filaResultado(string $clave, array $entrada): FilaMatriz
    {
        $faltan = array_filter(
            $entrada['depende_de'],
            fn(string $dep) => ($this->evaluaciones[$dep] ?? null)?->estado !== 'confirmado'
        );

        if ($faltan !== []) {
            $nombres = array_map(
                fn(string $dep) => Registro::ENTRADAS[$dep]['nombre'],
                $faltan
            );

            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'bloqueada',
                detalle: 'Se desbloquea al validar: ' . implode(' y ', $nombres),
                url:     null,
                accion:  null,
            );
        }

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'validada',
            detalle: 'Disponible',
            url:     route($entrada['rutas']['ver'], $this->zona->id),
            accion:  'Ver',
        );
    }

    private function filaMatriz(string $clave, array $entrada): FilaMatriz
    {
        $evaluacion = $this->evaluaciones[$clave];

        if ($evaluacion === null) {
            // El admin rellena formularios como el equipo desde que el
            // middleware dejó de restringirlo (Tarea 1): la misma acción de
            // "Empezar" para los tres roles.
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'sin_empezar',
                detalle: "{$entrada['criterios']} criterios · sin empezar",
                url:     route($entrada['rutas']['editar'], $this->zona->id),
                accion:  'Empezar',
            );
        }

        $validada = $evaluacion->estado === 'confirmado';
        $firma    = $this->firma($evaluacion);

        if ($validada) {
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'validada',
                detalle: 'Validada' . $firma,
                url:     route($entrada['rutas']['ver'], $this->zona->id),
                accion:  'Ver',
            );
        }

        $respondidos = self::criteriosRespondidos($evaluacion);

        // Validar exige la matriz entera: confirmarla a medias la rechaza la
        // validación del controlador. Antes del guardado parcial un borrador
        // siempre estaba completo y no había diferencia; ahora ofrecer «lista
        // para validar» sobre 3 de 34 manda al jefe a un formulario que le
        // devolverá errores.
        $completa = $respondidos === $entrada['criterios'];

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'borrador',
            detalle: "Borrador · {$respondidos} de {$entrada['criterios']} respondidos" . $firma,
            // El admin sigue editando un borrador aunque esté completo -antes
            // se le mandaba a 'ver' como si ya no pudiera tocarlo, que era
            // falso: solo una matriz VALIDADA le queda cerrada, y esa rama
            // vive arriba-.
            url:     route($entrada['rutas']['editar'], $this->zona->id),
            accion:  'Continuar',
            puedeValidar:    $completa && $this->usuario->esJefe(),
            avisoValidacion: $completa && $this->usuario->esEquipo()
                ? 'Lista para validar — avísale a ' . ($this->zona->jefe?->name ?? 'tu Jefe de Zona')
                : null,
        );
    }

    /**
     * Una lista de actores no tiene denominador fijo: son «cinco actores, dos a
     * medias», no «21 de 34 respondidos». Por eso no reutiliza filaMatriz().
     *
     * El modelo de la entrada es la configuración por zona —la que lleva el
     * estado—, no la de cada actor.
     */
    private function filaActores(string $clave, array $entrada): FilaMatriz
    {
        $config  = $this->evaluaciones[$clave];

        $cuantos  = $this->zona->involucrados()->count();
        $validada = $config?->estado === 'confirmado';
        $firma    = $config ? $this->firma($config) : '';

        // El estado de la configuración manda sobre el recuento de actores,
        // igual que en las hermanas: filaMatriz() decide por $evaluacion, no
        // por cuántas respuestas tiene. Comprobar 'sin actores' antes que
        // 'validada' dejaba una fila «sin empezar» mientras validadas() y
        // progresoDe() ya la contaban como hecha, con cabecera y fila en
        // contradicción. Una configuración confirmada con cero actores no
        // debería poder existir —la validación de la tarea 3 va a exigir al
        // menos un actor—, así que si este caso aparece aquí es una
        // inconsistencia de datos, no un estado normal que haya que diseñar.
        if ($validada) {
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'validada',
                detalle: "Validada · {$cuantos} actores" . $firma,
                url:     route($entrada['rutas']['ver'], $this->zona->id),
                accion:  'Ver',
            );
        }

        if ($cuantos === 0) {
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'sin_empezar',
                detalle: 'Todavía sin actores registrados',
                url:     route($entrada['rutas']['editar'], $this->zona->id),
                accion:  'Empezar',
            );
        }

        $incompletos = $this->zona->involucrados()->incompletos()->count();

        $detalle = $incompletos === 0
            ? "Borrador · {$cuantos} actores, todos completos"
            : "Borrador · {$cuantos} actores, {$incompletos} sin completar";

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'borrador',
            detalle: $detalle . $firma,
            // Mismo trato que filaMatriz(): el admin edita un borrador de
            // actores como el equipo, esté completo o no.
            url:     route($entrada['rutas']['editar'], $this->zona->id),
            accion:  'Continuar',
            puedeValidar:    $incompletos === 0 && $this->usuario->esJefe(),
            avisoValidacion: $incompletos === 0 && $this->usuario->esEquipo()
                ? 'Lista para validar — avísale a ' . ($this->zona->jefe?->name ?? 'tu Jefe de Zona')
                : null,
        );
    }

    /**
     * Una lista de sitios no tiene denominador fijo, igual que la de
     * actores, pero con una condición de más: la Superficie Territorial (ST)
     * es un escalar de la ZONA, no de cada sitio, y sin ella ningún ÍETP
     * existe aunque todos los sitios tengan su DET respondido. Por eso no
     * reutiliza filaActores(): esa rama no sabe nada de un segundo dato de
     * completitud aparte de la lista.
     *
     * El modelo de la entrada es FrecuentacionConfig -la configuración por
     * zona, que lleva el estado Y la ST-, no el de cada sitio.
     *
     * El estado de la configuración manda sobre el recuento de sitios, igual
     * que en filaActores(): comprobar "sin sitios" antes que "validada"
     * dejaría una fila «sin empezar» mientras validadas() y progresoDe() ya
     * la cuentan como hecha, con cabecera y fila en contradicción -mismo
     * motivo, documentado en el docblock de filaActores()-.
     */
    private function filaSitios(string $clave, array $entrada): FilaMatriz
    {
        $config = $this->evaluaciones[$clave];

        $cuantos  = $this->zona->frecuentacionSitios()->count();
        $validada = $config?->estado === 'confirmado';
        $firma    = $config ? $this->firma($config) : '';

        if ($validada) {
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'validada',
                detalle: "Validada · {$cuantos} sitios" . $firma,
                url:     route($entrada['rutas']['ver'], $this->zona->id),
                accion:  'Ver',
            );
        }

        if ($cuantos === 0) {
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'sin_empezar',
                detalle: 'Todavía sin sitios registrados',
                url:     route($entrada['rutas']['editar'], $this->zona->id),
                accion:  'Empezar',
            );
        }

        $incompletos = $this->zona->frecuentacionSitios()->incompletos()->count();
        $stDefinida  = ($config?->st ?? null) !== null && $config->st > 0;

        // Dos motivos de bloqueo, no uno: sitios sin DET, y una ST sin
        // definir o en cero. Son causas distintas -una es "faltan datos de
        // sitio", la otra "falta un dato de la zona"- y conviene que el
        // detalle las distinga en vez de fundirlas en una frase que no dice
        // cuál de las dos hace falta resolver.
        $detalle = match (true) {
            $incompletos > 0 && ! $stDefinida => "Borrador · {$cuantos} sitios, {$incompletos} sin DET, falta la Superficie Territorial",
            $incompletos > 0                  => "Borrador · {$cuantos} sitios, {$incompletos} sin DET",
            ! $stDefinida                      => "Borrador · {$cuantos} sitios completos, falta la Superficie Territorial",
            default                            => "Borrador · {$cuantos} sitios, todos completos",
        };

        $listaCompleta = $incompletos === 0 && $stDefinida;

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'borrador',
            detalle: $detalle . $firma,
            url:     route($entrada['rutas']['editar'], $this->zona->id),
            accion:  'Continuar',
            puedeValidar:    $listaCompleta && $this->usuario->esJefe(),
            avisoValidacion: $listaCompleta && $this->usuario->esEquipo()
                ? 'Lista para validar — avísale a ' . ($this->zona->jefe?->name ?? 'tu Jefe de Zona')
                : null,
        );
    }

    /**
     * Criterios respondidos de una evaluación.
     *
     * Cuenta las columnas de criterio que no están en null, filtrando
     * getAttributes() en vez de mantener una lista de campos propia: el
     * registro sabe cuántos criterios tiene cada matriz, pero no cuáles, y
     * repetir esa lista aquí sería una segunda fuente de verdad que se
     * desincroniza sola.
     *
     * Pública y estática porque la usan dos consumidores que no comparten
     * instancia: la ficha de la zona y las pestañas de cada matriz. Si cada
     * uno contara por su cuenta, habría dos respuestas a la misma pregunta y
     * el «21 de 34» de un sitio no coincidiría con el del otro.
     */
    public static function criteriosRespondidos(Model $evaluacion): int
    {
        return count(array_filter(
            $evaluacion->getAttributes(),
            fn($valor, string $columna) => $valor !== null && self::esColumnaDeCriterio($columna),
            ARRAY_FILTER_USE_BOTH
        ));
    }

    /**
     * ¿Es esta columna un criterio del instrumento, y no control ni cálculo?
     *
     * Es una heurística por nombre, y los nombres no siguen un patrón único
     * entre matrices: FIT guarda `fit_rtt`, `media_rtt` y `fit`; Percepción
     * guarda `pond_ds`; Potencialidad guarda `val_afluencia`. Un criterio de
     * verdad excluido, o una columna calculada colada, cambian el «21 de 34»
     * sin romper nada visible.
     *
     * Por eso es pública: EstadoZonaTest la recorre contra el esquema real de
     * las seis tablas y exige que el número de columnas que sobreviven sea
     * exactamente el que declara el registro. Sin esa comprobación, esta lista
     * sería una suposición —y en esta misma rama ya hubo un filtro por prefijo
     * que capturaba 0 de 16 columnas sin que nada lo detectara.
     */
    public static function esColumnaDeCriterio(string $columna): bool
    {
        $control = ['id', 'zona_id', 'user_id', 'estado', 'created_at', 'updated_at'];

        if (in_array($columna, $control, true)) {
            return false;
        }

        // Los totales de FIT y FET se llaman como la propia matriz.
        if ($columna === 'fit' || $columna === 'fet') {
            return false;
        }

        // Medias, ponderados y totales, con los nombres que usa cada matriz.
        foreach (['media_', 'pond_', 'val_', 'fit_', 'fet_'] as $prefijo) {
            if (str_starts_with($columna, $prefijo)) {
                return false;
            }
        }

        foreach (['_promedio', '_total'] as $sufijo) {
            if (str_ends_with($columna, $sufijo)) {
                return false;
            }
        }

        // Percepción guarda además un campo de texto libre que no se puntúa.
        return $columna !== 'acciones_mejora';
    }

    /** «— Ana Pérez, hace 2 días». Se guarda desde siempre y no se enseñaba. */
    private function firma(Model $evaluacion): string
    {
        $quien   = $evaluacion->user?->name;
        $cuando  = $evaluacion->updated_at?->diffForHumans();

        return match (true) {
            $quien !== null && $cuando !== null => " — {$quien}, {$cuando}",
            $cuando !== null                    => " — {$cuando}",
            default                             => '',
        };
    }
}
