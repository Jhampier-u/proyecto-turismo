<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Matrices\Involucrados;
use App\Models\Involucrado;
use App\Models\InvolucradosConfig;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * CRUD de la Matriz de Involucrados Turísticos Territoriales.
 *
 * A diferencia de las siete matrices anteriores no hereda de
 * EvaluacionZonaController: esa clase base asume un formulario fijo de
 * criterios de la zona (una fila por zona, updateOrCreate por zona_id). Aquí
 * hay una lista variable de actores —una fila por actor— y el estado
 * borrador/confirmado vive aparte, en InvolucradosConfig. El patrón que sí
 * encaja es el de InventarioController: un CRUD normal, con el añadido de la
 * máquina de estados que copia la idea (no la clase) de
 * EvaluacionZonaController::update().
 */
class InvolucradosController extends Controller
{
    public function index($zonaId)
    {
        $zona    = Zona::findOrFail($zonaId);
        $actores = $zona->involucrados()->get();
        $config  = InvolucradosConfig::where('zona_id', $zonaId)->first();
        $user    = Auth::user();

        $confirmada     = $config?->estado === 'confirmado';
        $incompletos    = $zona->involucrados()->incompletos()->count();
        $listaCompleta  = $actores->isNotEmpty() && $incompletos === 0;

        return view('operativo.involucrados.index', [
            'zona'        => $zona,
            'actores'     => $actores,
            'config'      => $config,
            'confirmada'  => $confirmada,
            'atributos'   => Involucrados::ATRIBUTOS,
            'escalaMax'   => Involucrados::ESCALA_MAX,
            // Mismo criterio que EvaluacionZonaController::update(): solo el
            // equipo queda cerrado al confirmar. El jefe conserva la
            // capacidad de seguir tocando la lista, igual que en las siete
            // matrices de formulario.
            'puedeEditar'  => $user->esJefe() || ($user->esEquipo() && ! $confirmada),
            'puedeValidar' => ! $confirmada && $user->esJefe() && $listaCompleta,
            // La pista para el equipo —"avísale a tu Jefe"— solo tiene
            // sentido con la lista completa y sin validar todavía, el mismo
            // criterio que FilaMatriz::filaActores() usa para avisoValidacion.
            'avisoValidacion' => ! $confirmada && $user->esEquipo() && $listaCompleta,
        ]);
    }

    public function create($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        return view('operativo.involucrados.form', [
            'zona'            => $zona,
            'actor'           => new Involucrado(),
            'atributos'       => Involucrados::ATRIBUTOS,
            'etiquetasEscala' => $this->etiquetasEscala(),
        ]);
    }

    public function store(Request $request, $zonaId)
    {
        Zona::findOrFail($zonaId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        $request->validate($this->reglas());

        Involucrado::create($this->datosDe($request) + ['zona_id' => $zonaId]);

        return redirect()->route('operativo.involucrados.index', $zonaId)
            ->with('success', $this->mensajeConReapertura('Actor registrado correctamente.', $zonaId));
    }

    public function edit($zonaId, $actorId)
    {
        $zona  = Zona::findOrFail($zonaId);
        $actor = Involucrado::where('zona_id', $zonaId)->findOrFail($actorId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        return view('operativo.involucrados.form', [
            'zona'            => $zona,
            'actor'           => $actor,
            'atributos'       => Involucrados::ATRIBUTOS,
            'etiquetasEscala' => $this->etiquetasEscala(),
        ]);
    }

    public function update(Request $request, $zonaId, $actorId)
    {
        $actor = Involucrado::where('zona_id', $zonaId)->findOrFail($actorId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        $request->validate($this->reglas());

        $actor->update($this->datosDe($request));

        return redirect()->route('operativo.involucrados.index', $zonaId)
            ->with('success', $this->mensajeConReapertura('Actor actualizado correctamente.', $zonaId));
    }

    public function destroy($zonaId, $actorId)
    {
        $actor = Involucrado::where('zona_id', $zonaId)->findOrFail($actorId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        $actor->delete();

        return redirect()->route('operativo.involucrados.index', $zonaId)
            ->with('success', $this->mensajeConReapertura('Actor eliminado.', $zonaId));
    }

    /**
     * Cierra la lista de actores. Exige lo mismo que pide el diseño del
     * instrumento para que la normalización signifique algo: al menos un
     * actor, y ninguno a medias —un actor incompleto no tiene grado, y sin
     * grado no hay nada que normalizar—.
     */
    public function validar($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $user = Auth::user();

        // Ruta dedicada y no una rama de accion_estado como en las siete
        // matrices de formulario: aquí no tiene sentido "guardar borrador"
        // porque no hay un formulario único que enviar, así que se rechaza
        // sin más al que no sea jefe en vez de degradar la petición en
        // silencio.
        abort_unless($user->esJefe(), 403);

        $total = $zona->involucrados()->count();

        if ($total === 0) {
            return back()->with('error', 'No puedes validar sin al menos un actor registrado.');
        }

        if ($zona->involucrados()->incompletos()->exists()) {
            return back()->with('error', 'No puedes validar: hay actores con criterios sin responder.');
        }

        InvolucradosConfig::updateOrCreate(
            ['zona_id' => $zonaId],
            ['user_id' => $user->id, 'estado' => 'confirmado']
        );

        return redirect()->route('operativo.involucrados.resultados', $zonaId)
            ->with('success', 'Lista de actores VALIDADA y CERRADA correctamente.');
    }

    /**
     * Vista mínima de resultados: solo lo necesario para que la ruta exista y
     * no rompa. La tarea 4 la completa con la normalización, el producto de
     * relevancia y el ranking de Mitchell que describe el diseño del
     * instrumento.
     */
    public function resultados($zonaId)
    {
        $zona    = Zona::findOrFail($zonaId);
        $actores = $zona->involucrados()->get();

        return view('operativo.involucrados.resultados', [
            'zona'    => $zona,
            'actores' => $actores,
            // "Completa" en el mismo sentido que el resto de vistas de
            // resultados: nada que enseñar con la lista vacía o con algún
            // actor a medias, porque el instrumento normaliza sobre el
            // conjunto entero.
            'completa' => $actores->isNotEmpty() && $actores->every(fn(Involucrado $a) => $a->estaCompleto()),
        ]);
    }

    /**
     * Solo el equipo queda cerrado por una lista confirmada; el jefe conserva
     * la edición, igual que EvaluacionZonaController::update() solo bloquea
     * a esEquipo(). Se devuelve al listado con el mensaje de cerrada en vez
     * de un 403: el middleware `zona` ya cubre el caso de quien no tiene
     * ningún acceso a la zona, esto es una regla de negocio distinta.
     */
    private function bloqueoSiCerrada($zonaId): ?RedirectResponse
    {
        $config = InvolucradosConfig::where('zona_id', $zonaId)->first();

        if ($config?->estado === 'confirmado' && Auth::user()->esEquipo()) {
            return redirect()->route('operativo.involucrados.index', $zonaId)
                ->with('error', $this->mensajeCerrada());
        }

        return null;
    }

    private function mensajeCerrada(): string
    {
        return 'Esta lista de actores ya fue validada por el Jefe de Zona. No puedes editarla.';
    }

    /**
     * "Confirmado" significa que ESE conjunto exacto de actores fue
     * validado, no "hubo una validación en algún momento": el resultado
     * normaliza cada grado dividiendo por la suma de todos los actores, así
     * que si la lista cambia después de validarla, todos los normalizados
     * cambian con ella y lo que queda "cerrado" ya no es lo que se validó.
     *
     * Las siete matrices de formulario no necesitan este mecanismo porque su
     * propio guardado recalcula `estado` en cada envío —guardar como
     * borrador ya reabre la evaluación—; aquí el CRUD de un actor no toca
     * `InvolucradosConfig` en absoluto, así que sin esto la lista podía
     * seguir diciendo "validada" mientras el jefe (al único que
     * bloqueoSiCerrada() no le impide escribir) le añadía, editaba o le
     * borraba actores por debajo.
     *
     * Solo el jefe llega hasta aquí en la práctica: bloqueoSiCerrada() ya
     * cierra el paso al equipo antes de que el actor se llegue a tocar.
     */
    private function reabrirSiConfirmada($zonaId): bool
    {
        $config = InvolucradosConfig::where('zona_id', $zonaId)->first();

        if ($config?->estado !== 'confirmado') {
            return false;
        }

        // user_id también se actualiza: la tabla guarda "qué usuario la
        // tocó por última vez" (ver el docblock de InvolucradosConfig), y
        // reabrirla es justo eso, un toque, aunque no sea una confirmación.
        $config->update(['estado' => 'borrador', 'user_id' => Auth::id()]);

        return true;
    }

    /**
     * Compone el mensaje de éxito de una escritura, avisando si de paso
     * reabrió una lista ya validada. El aviso va en el propio mensaje —no
     * solo en el estado de la página— porque quien acaba de guardar es quien
     * necesita saber que tiene que volver a validar, no quien mire la zona
     * más tarde.
     */
    private function mensajeConReapertura(string $base, $zonaId): string
    {
        return $this->reabrirSiConfirmada($zonaId)
            ? "{$base} Al modificar la lista ya validada, vuelve a borrador: hay que validarla de nuevo."
            : $base;
    }

    /**
     * El vocabulario de la escala 0-3 para cada uno de los once campos,
     * indexado por nombre de campo. Vive resuelto aquí y no en la vista
     * —que solo lo consume— por el mismo motivo que EvaluacionIrritacionController
     * no deja que sus plantillas lean App\Matrices\Irritacion por su cuenta:
     * una sola fuente de verdad para lo que sabe el instrumento.
     *
     * @return array<string, array<int, string>>
     */
    private function etiquetasEscala(): array
    {
        return array_combine(
            Involucrados::campos(),
            array_map(
                fn(string $campo) => Involucrados::etiquetasEscala($campo),
                Involucrados::campos()
            )
        );
    }

    /** @return array<string, string> */
    private function reglas(): array
    {
        return [
            'nombre' => 'required|string|max:200',
            ...array_fill_keys(Involucrados::campos(), 'nullable|integer|min:0|max:3'),
        ];
    }

    /**
     * Los criterios llegan como cadena vacía cuando el desplegable se deja en
     * "— sin responder —"; ConvertEmptyStringsToNull ya los convierte a null
     * antes de llegar aquí, pero el resto sigue el mismo patrón explícito que
     * MatrizPonderadaController::prepararDatos() para que update() reciba
     * siempre las once claves, incluida la que el usuario acaba de vaciar.
     *
     * @return array<string, mixed>
     */
    private function datosDe(Request $request): array
    {
        $datos = ['nombre' => $request->input('nombre')];

        foreach (Involucrados::campos() as $campo) {
            $bruto = $request->input($campo);
            $datos[$campo] = $bruto === null ? null : (int) $bruto;
        }

        // Casillas, no desplegables: una sin marcar no llega en la petición.
        // request()->boolean() ya trata esa ausencia como false, que es el
        // valor correcto (no posee el atributo), así que no hace falta
        // distinguir "no marcada" de "sin responder" como con los criterios.
        $datos['tiene_poder']       = $request->boolean('tiene_poder');
        $datos['tiene_legitimidad'] = $request->boolean('tiene_legitimidad');
        $datos['tiene_urgencia']    = $request->boolean('tiene_urgencia');

        return $datos;
    }
}
