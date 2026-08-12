<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Matrices\Involucrados;
use App\Models\Involucrado;
use App\Models\InvolucradosConfig;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'incompletos' => $incompletos,
            'atributos'   => Involucrados::ATRIBUTOS,
            'escalaMax'   => Involucrados::ESCALA_MAX,
            // Mismo criterio que EvaluacionZonaController::update(): solo
            // quien no es jefe queda cerrado al confirmar. El admin escribe
            // borradores igual que el equipo -Tarea 2-, así que cuenta como
            // quien puede editar mientras la lista no esté confirmada; el
            // jefe conserva la capacidad de seguir tocándola siempre.
            'puedeEditar'  => $user->esJefe() || ! $confirmada,
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

        $request->validate($this->reglas(), [], $this->etiquetas());

        // Alta y reapertura en la misma transacción: si reabrirSiConfirmada()
        // fallara a mitad de camino, un rollback a medias dejaría el actor
        // nuevo ya escrito pero la lista todavía diciendo "confirmado" sobre
        // un conjunto que ya cambió — justo el conjunto que ya no corresponde
        // a lo que se validó. Ver el docblock de reabrirSiConfirmada().
        $reabrio = DB::transaction(function () use ($request, $zonaId) {
            Involucrado::create($this->datosDe($request) + ['zona_id' => $zonaId]);

            return $this->reabrirSiConfirmada($zonaId);
        });

        return redirect()->route('operativo.involucrados.index', $zonaId)
            ->with('success', $this->mensajeConReapertura('Actor registrado correctamente.', $reabrio));
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

        $request->validate($this->reglas(), [], $this->etiquetas());

        // Misma transacción que store(): la actualización y la reapertura
        // tienen que caer juntas o ninguna, por el mismo motivo.
        $reabrio = DB::transaction(function () use ($actor, $request, $zonaId) {
            $actor->update($this->datosDe($request));

            return $this->reabrirSiConfirmada($zonaId);
        });

        return redirect()->route('operativo.involucrados.index', $zonaId)
            ->with('success', $this->mensajeConReapertura('Actor actualizado correctamente.', $reabrio));
    }

    public function destroy($zonaId, $actorId)
    {
        $actor = Involucrado::where('zona_id', $zonaId)->findOrFail($actorId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        // Borrar y reabrir en la misma transacción, por el mismo motivo que
        // store()/update(): sin ella, un fallo al reabrir podía dejar la
        // lista "confirmado" con un actor de menos del que se validó.
        //
        // Esta ruta es además la que sostiene una invariante que
        // EstadoZona::filaActores() da por descontada en un comentario: "una
        // configuración confirmada con cero actores no debería poder
        // existir". No es una garantía aparte, es consecuencia de solo dos
        // reglas trabajando juntas — validar() exige $total > 0 antes de
        // confirmar, y borrar el último actor de una lista confirmada pasa
        // por aquí, que la reabre en la misma transacción que la vacía. Si
        // algún día la reapertura se aparta de destroy() (una cola, un job,
        // un "deshacer" que borre sin pasar por este método) ese estado
        // imposible deja de serlo, y filaActores() empezaría a tratar un caso
        // real como si fuera una inconsistencia de datos.
        $reabrio = DB::transaction(function () use ($actor, $zonaId) {
            $actor->delete();

            return $this->reabrirSiConfirmada($zonaId);
        });

        return redirect()->route('operativo.involucrados.index', $zonaId)
            ->with('success', $this->mensajeConReapertura('Actor eliminado.', $reabrio));
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
     * Resultados: el ranking de actores por relevancia (Tarea 4) con sus
     * normalizados y su tipo de Mitchell.
     */
    public function resultados($zonaId)
    {
        $zona    = Zona::findOrFail($zonaId);
        $actores = $zona->involucrados()->get();
        // Igual que index(): quién tocó la lista por última vez, ahora
        // también visible en resultados (Tarea 2, Step 8b).
        $config  = InvolucradosConfig::where('zona_id', $zonaId)->first();

        // "Completa" en el mismo sentido que el resto de vistas de
        // resultados: nada que enseñar con la lista vacía o con algún actor a
        // medias, porque el instrumento normaliza sobre el conjunto entero.
        $completa = $actores->isNotEmpty() && $actores->every(fn(Involucrado $a) => $a->estaCompleto());

        $datos = $completa
            ? $this->relevanciasDe($actores)
            : ['filas' => collect(), 'degenerados' => []];

        return view('operativo.involucrados.resultados', [
            'zona'      => $zona,
            'completa'  => $completa,
            'config'    => $config,
            // Igual que index()/create()/edit(): la vista consume la
            // constante ya resuelta, no alcanza App\Matrices\Involucrados por
            // su cuenta.
            'atributos'   => Involucrados::ATRIBUTOS,
            'filas'       => $datos['filas'],
            // Qué atributos no diferencian a nadie de este conjunto
            // (Involucrados::esAtributoDegenerado()): la vista los pinta con
            // "—" en vez de un 1.00 que se confundiría con "está justo en la
            // media". Ver el docblock de esAtributoDegenerado().
            'degenerados' => $datos['degenerados'],
        ]);
    }

    /**
     * Arma las filas de la tabla de resultados —grados, normalizados y
     * relevancia por actor— y qué atributos salieron degenerados en este
     * conjunto. El ENSAMBLADO vive aquí y no en App\Matrices\Involucrados:
     * leer los grados de cada actor por atributo, invocar la aritmética pura
     * del instrumento y ordenar por relevancia es trabajo del controlador,
     * igual que EvaluacionFitController::calcular() recibe un array de
     * valores y dejar el CRUD y el modelo aparte. Antes esto vivía en
     * Involucrados::relevancias(), que por eso tenía que importar el modelo
     * Involucrado —el modelo ya importaba de vuelta a Involucrados para
     * campos()/grado()/tipoDe()—, la primera dependencia circular
     * instrumento↔modelo del sistema. Moverlo aquí la rompe: Involucrados
     * vuelve a ser solo constantes y funciones puras sobre escalares, como
     * las otras siete matrices.
     *
     * Precondición: solo se llama con $actores completos —resultados() la
     * invoca únicamente cuando $completa es true—. Con un actor a medias,
     * Involucrado::grado() devuelve null y
     * Involucrados::normalizar()/esAtributoDegenerado() lo rechazan con un
     * InvalidArgumentException en vez de devolver un número que parece
     * válido y no lo es.
     *
     * @param Collection<int, Involucrado> $actores
     * @return array{filas: Collection, degenerados: array<string, bool>}
     */
    private function relevanciasDe(Collection $actores): array
    {
        // Reindexado una sola vez: los arrays de grados/normalizados de abajo
        // y el bucle que arma las filas tienen que alinear la misma posición
        // con el mismo actor, y ->get() no garantiza claves 0..n-1 si algo
        // aguas arriba las tocó.
        $actores = $actores->values();
        $claves  = array_keys(Involucrados::ATRIBUTOS);

        $grados      = [];
        $normalizado = [];
        $degenerados = [];

        foreach ($claves as $atributo) {
            $grados[$atributo]      = $actores->map(fn(Involucrado $a) => $a->grado($atributo))->all();
            $degenerados[$atributo] = Involucrados::esAtributoDegenerado($grados[$atributo]);
            $normalizado[$atributo] = Involucrados::normalizar($grados[$atributo]);
        }

        $filas = $actores
            ->map(function (Involucrado $actor, int $indice) use ($claves, $grados, $normalizado) {
                $filaGrado       = [];
                $filaNormalizado = [];
                foreach ($claves as $atributo) {
                    $filaGrado[$atributo]       = $grados[$atributo][$indice];
                    $filaNormalizado[$atributo] = $normalizado[$atributo][$indice];
                }

                return [
                    'actor'       => $actor,
                    'grado'       => $filaGrado,
                    'normalizado' => $filaNormalizado,
                    'relevancia'  => Involucrados::relevancia($filaNormalizado),
                ];
            })
            ->sortByDesc('relevancia')
            ->values();

        return ['filas' => $filas, 'degenerados' => $degenerados];
    }

    /**
     * ! esJefe() y no esEquipo(): desde que el admin escribe (PermisosAdminTest),
     * él también tiene que encontrarse la lista cerrada, igual que
     * EvaluacionZonaController::update() desde su mismo cambio. Con
     * esEquipo() el admin podía borrar, crear o editar actores de una lista
     * ya validada -reabriéndola por el camino, vía reabrirSiConfirmada()-,
     * justo lo que esta guarda existe para impedir. Se devuelve al listado
     * con el mensaje de cerrada en vez de un 403: el middleware `zona` ya
     * cubre el caso de quien no tiene ningún acceso a la zona, esto es una
     * regla de negocio distinta.
     */
    private function bloqueoSiCerrada($zonaId): ?RedirectResponse
    {
        $config = InvolucradosConfig::where('zona_id', $zonaId)->first();

        if ($config?->estado === 'confirmado' && ! Auth::user()->esJefe()) {
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
     *
     * store()/update()/destroy() la llaman de forma explícita, dentro de la
     * misma transacción que la escritura del actor — no desde
     * mensajeConReapertura() como antes. Que la transición de estado más
     * delicada de esta matriz colgara de que alguien siga componiendo un
     * flash era el propio bug: un cambio futuro en cómo se arman los
     * mensajes de éxito podía dejar de llamar a este método sin que nada lo
     * avisara, y la lista seguiría diciendo "validada" para siempre.
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
     * Compone el mensaje de éxito de una escritura, avisando si de paso se
     * reabrió una lista ya validada.
     *
     * Recibe el booleano ya decidido — no llama a reabrirSiConfirmada() — a
     * propósito: este método solo arma una cadena de texto, y una función
     * que compone un mensaje no es el sitio para disparar la transición de
     * estado más delicada de la matriz. Quien escribe en la base es
     * store()/update()/destroy(), dentro de su propia transacción; aquí solo
     * se decide cómo contarlo. El aviso va en el propio mensaje —no solo en
     * el estado de la página— porque quien acaba de guardar es quien
     * necesita saber que tiene que volver a validar, no quien mire la zona
     * más tarde.
     */
    private function mensajeConReapertura(string $base, bool $reabrio): string
    {
        return $reabrio
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
     * campo => etiqueta de los once criterios, aplanando Involucrados::ATRIBUTOS
     * -que ya es 'poder'/'legitimidad'/'urgencia' => [titulo, campos]-, más
     * 'nombre': el único campo de este formulario que no es un criterio de
     * puntuación. No copia nada nuevo: ATRIBUTOS es la misma fuente que ya
     * consumen index(), create() y relevanciasDe() para lo que sabe el
     * instrumento sobre sus once campos.
     *
     * @return array<string, string>
     */
    private function etiquetas(): array
    {
        return ['nombre' => 'Nombre del actor'] + array_merge(...array_map(
            fn(array $atributo) => $atributo['campos'],
            array_values(Involucrados::ATRIBUTOS)
        ));
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
