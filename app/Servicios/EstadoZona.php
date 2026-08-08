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

    private function fila(string $clave): FilaMatriz
    {
        $entrada = Registro::ENTRADAS[$clave];

        return match ($entrada['tipo']) {
            'inventario' => $this->filaInventario($clave, $entrada),
            'resultado'  => $this->filaResultado($clave, $entrada),
            'actores'    => $this->filaActores($clave, $entrada),
            default      => $this->filaMatriz($clave, $entrada),
        };
    }

    private function filaInventario(string $clave, array $entrada): FilaMatriz
    {
        $cuantos = $this->zona->inventarios()->count();

        // El admin conserva la URL (necesita consultar los recursos) pero no
        // la acción de escritura: el middleware ya le corta cualquier POST,
        // así que ofrecerle 'Abrir' invita a un botón que termina en 403.
        $esAdmin = $this->usuario->esAdmin();

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'sin_estado',
            detalle: $cuantos === 1 ? '1 recurso registrado' : "{$cuantos} recursos registrados",
            url:     route($entrada['rutas']['editar'], $this->zona->id),
            accion:  $esAdmin ? 'Ver' : 'Abrir',
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
        $esAdmin    = $this->usuario->esAdmin();

        if ($evaluacion === null) {
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'sin_empezar',
                detalle: "{$entrada['criterios']} criterios · sin empezar",
                url:     $esAdmin ? null : route($entrada['rutas']['editar'], $this->zona->id),
                accion:  $esAdmin ? null : 'Empezar',
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

        $respondidos = $this->respondidos($evaluacion);

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
            url:     $esAdmin
                ? route($entrada['rutas']['ver'], $this->zona->id)
                : route($entrada['rutas']['editar'], $this->zona->id),
            accion:  $esAdmin ? 'Ver' : 'Continuar',
            // esJefe() ya excluye al admin por construcción (son roles
            // distintos): ! $esAdmin era una condición redundante.
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
        $esAdmin = $this->usuario->esAdmin();

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
                url:     $esAdmin ? null : route($entrada['rutas']['editar'], $this->zona->id),
                accion:  $esAdmin ? null : 'Empezar',
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
            url:     $esAdmin
                ? route($entrada['rutas']['ver'], $this->zona->id)
                : route($entrada['rutas']['editar'], $this->zona->id),
            accion:  $esAdmin ? 'Ver' : 'Continuar',
            puedeValidar:    $incompletos === 0 && $this->usuario->esJefe(),
            avisoValidacion: $incompletos === 0 && $this->usuario->esEquipo()
                ? 'Lista para validar — avísale a ' . ($this->zona->jefe?->name ?? 'tu Jefe de Zona')
                : null,
        );
    }

    /**
     * Criterios ya respondidos de una evaluación.
     *
     * Cuenta las columnas de criterio que no están en null. El registro sabe
     * cuántos criterios tiene cada matriz, pero no cuáles: repetir esa lista
     * aquí sería una segunda fuente de verdad que se desincroniza sola.
     */
    private function respondidos(Model $evaluacion): int
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
