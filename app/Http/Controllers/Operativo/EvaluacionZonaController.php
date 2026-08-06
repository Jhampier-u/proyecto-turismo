<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Flujo común a todas las evaluaciones por zona.
 *
 * Concentra la máquina de estados borrador → confirmado y la persistencia, que
 * eran idénticas en los cuatro controladores de evaluación salvo el bloque de
 * cálculo. La duplicación ya había costado un fallo replicado cuatro veces
 * (accion_estado sin validar).
 *
 * Cada matriz aporta su modelo, su ruta de resultados y sus datos calculados.
 */
abstract class EvaluacionZonaController extends Controller
{
    /** FQCN del modelo de la evaluación. */
    abstract protected function modelo(): string;

    /** Nombre de la ruta a la que se redirige tras guardar. */
    abstract protected function rutaResultados(): string;

    /**
     * Valida la petición y devuelve las columnas a persistir, sin user_id ni
     * estado, de los que se encarga esta clase.
     *
     * @return array<string, mixed>
     */
    abstract protected function prepararDatos(Request $request, $zonaId, ?Model $actual): array;

    /** Se ejecuta tras guardar. FIT y FET lo usan para la instantánea del VTT. */
    protected function despuesDeGuardar($zonaId, User $user): void
    {
        //
    }

    protected function mensajeCerrada(): string
    {
        return 'Esta evaluación ya fue validada por el Jefe de Zona. No puedes editarla.';
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        return $estado === 'confirmado'
            ? 'Evaluación VALIDADA y CERRADA correctamente.'
            : 'Borrador guardado. El Jefe de Zona debe validarlo.';
    }

    public function update(Request $request, $zonaId)
    {
        $user   = Auth::user();
        $modelo = $this->modelo();

        $actual = $modelo::where('zona_id', $zonaId)->first();

        if ($actual && $actual->estado === 'confirmado' && $user->esEquipo()) {
            return back()->with('error', $this->mensajeCerrada());
        }

        $request->validate(['accion_estado' => 'nullable|in:borrador,confirmado']);

        $datos = $this->prepararDatos($request, $zonaId, $actual);

        // Solo el Jefe de Zona puede confirmar; el equipo siempre guarda borrador.
        $estado = $user->esJefe()
            ? $request->input('accion_estado', 'borrador')
            : 'borrador';

        $modelo::updateOrCreate(
            ['zona_id' => $zonaId],
            $datos + ['user_id' => $user->id, 'estado' => $estado]
        );

        $this->despuesDeGuardar($zonaId, $user);

        return redirect()
            ->route($this->rutaResultados(), $zonaId)
            ->with('success', $this->mensajeExito($estado, $datos));
    }
}
