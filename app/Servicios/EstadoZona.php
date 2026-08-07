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

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'borrador',
            detalle: 'Borrador' . $firma,
            url:     $esAdmin
                ? route($entrada['rutas']['ver'], $this->zona->id)
                : route($entrada['rutas']['editar'], $this->zona->id),
            accion:  $esAdmin ? 'Ver' : 'Continuar',
            puedeValidar:    ! $esAdmin && $this->usuario->esJefe(),
            avisoValidacion: $this->usuario->esEquipo()
                ? 'Lista para validar — avísale a ' . ($this->zona->jefe?->name ?? 'tu Jefe de Zona')
                : null,
        );
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
