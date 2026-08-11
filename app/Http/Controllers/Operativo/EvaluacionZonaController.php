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
     * Recibe el estado destino porque de él depende la obligatoriedad: en
     * borrador se admiten huecos, al confirmar no.
     *
     * @return array<string, mixed>
     */
    abstract protected function prepararDatos(Request $request, $zonaId, ?Model $actual, string $estado): array;

    /** ¿Están todos los criterios respondidos? Las matrices lo afinan. */
    protected function estaCompleta(array $datos): bool
    {
        return true;
    }

    protected function mensajeIncompleto(array $datos): string
    {
        return 'Borrador guardado. Faltan criterios por responder.';
    }

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

        // ! esJefe() y no esEquipo(): desde que el admin escribe, él también
        // tiene que encontrarse la matriz cerrada. Con esEquipo() podría
        // reabrir lo que el jefe validó y luego no poder volver a validarlo.
        if ($actual && $actual->estado === 'confirmado' && ! $user->esJefe()) {
            return back()->with('error', $this->mensajeCerrada());
        }

        $request->validate(['accion_estado' => 'nullable|in:borrador,confirmado']);

        // El estado se decide ANTES de validar los criterios: es lo que
        // determina si se exigen todos o se admite un borrador a medias.
        // Solo el Jefe de Zona puede confirmar; el equipo siempre borrador.
        $estado = $user->esJefe()
            ? $request->input('accion_estado', 'borrador')
            : 'borrador';

        $datos = $this->prepararDatos($request, $zonaId, $actual, $estado);

        $modelo::updateOrCreate(
            ['zona_id' => $zonaId],
            $datos + ['user_id' => $user->id, 'estado' => $estado]
        );

        $this->despuesDeGuardar($zonaId, $user);

        // Una matriz incompleta no tiene resultados que enseñar: se vuelve al
        // formulario, que es donde el usuario sigue trabajando.
        if (! $this->estaCompleta($datos)) {
            return back()->with('success', $this->mensajeIncompleto($datos));
        }

        return redirect()
            ->route($this->rutaResultados(), $zonaId)
            ->with('success', $this->mensajeExito($estado, $datos));
    }
}
