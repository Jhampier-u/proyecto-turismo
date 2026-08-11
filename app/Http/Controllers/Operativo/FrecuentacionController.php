<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Matrices\Frecuentacion;
use App\Models\FrecuentacionConfig;
use App\Models\SitioFrecuentacion;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CRUD del Índice Espacial de Frecuentación Turística.
 *
 * Mismo reparto que InvolucradosController y por el mismo motivo: no hereda
 * de MatrizPonderadaController porque esa clase asume un formulario fijo de
 * criterios de la zona (updateOrCreate por zona_id), y aquí hay una lista
 * variable de sitios más un escalar de configuración (la Superficie
 * Territorial, ST) que ninguna otra matriz tiene. La diferencia con
 * Involucrados no es de forma sino de fórmula -ver el diseño, punto 3-: el
 * ÍETP de un sitio no depende de los demás sitios, pero SÍ hace falta
 * reabrir la lista al tocar un sitio o la ST, porque lo que se valida es
 * ÍEFT, una suma sensible a cada término, con un divisor (ST) compartido por
 * todos.
 */
class FrecuentacionController extends Controller
{
    public function index($zonaId)
    {
        $zona   = Zona::findOrFail($zonaId);
        $sitios = $zona->frecuentacionSitios()->get();
        $config = FrecuentacionConfig::where('zona_id', $zonaId)->first();
        $user   = Auth::user();

        $confirmada  = $config?->estado === 'confirmado';
        $incompletos = $zona->frecuentacionSitios()->incompletos()->count();
        // La ST es del territorio entero, no de un sitio: la lista solo está
        // completa si además de no tener sitios a medias, hay una ST > 0.
        $stDefinida    = ($config?->st ?? null) !== null && $config->st > 0;
        $listaCompleta = $sitios->isNotEmpty() && $incompletos === 0 && $stDefinida;

        return view('operativo.frecuentacion.index', [
            'zona'       => $zona,
            'sitios'     => $sitios,
            'config'     => $config,
            'confirmada' => $confirmada,
            // Mismo criterio que InvolucradosController::index(): el admin
            // escribe borradores igual que el equipo (Tarea 2 de
            // permisos-y-navegación), así que cuenta como quien puede editar
            // mientras la lista no esté confirmada; el jefe conserva la
            // capacidad de seguir tocándola siempre.
            'puedeEditar'     => $user->esJefe() || ! $confirmada,
            'puedeValidar'    => ! $confirmada && $user->esJefe() && $listaCompleta,
            'avisoValidacion' => ! $confirmada && $user->esEquipo() && $listaCompleta,
        ]);
    }

    public function create($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        return view('operativo.frecuentacion.form', [
            'zona'  => $zona,
            'sitio' => new SitioFrecuentacion(),
        ]);
    }

    public function store(Request $request, $zonaId)
    {
        Zona::findOrFail($zonaId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        $request->validate($this->reglas());

        // Alta y reapertura en la misma transacción: mismo motivo que
        // InvolucradosController::store() -un rollback a medias dejaría el
        // sitio nuevo ya escrito pero la lista todavía diciendo "confirmado"
        // sobre un conjunto que ya cambió-.
        $reabrio = DB::transaction(function () use ($request, $zonaId) {
            SitioFrecuentacion::create($this->datosDe($request) + ['zona_id' => $zonaId]);

            return $this->reabrirSiConfirmada($zonaId);
        });

        return redirect()->route('operativo.frecuentacion.index', $zonaId)
            ->with('success', $this->mensajeConReapertura('Sitio registrado correctamente.', $reabrio));
    }

    public function edit($zonaId, $sitioId)
    {
        $zona  = Zona::findOrFail($zonaId);
        $sitio = SitioFrecuentacion::where('zona_id', $zonaId)->findOrFail($sitioId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        return view('operativo.frecuentacion.form', [
            'zona'  => $zona,
            'sitio' => $sitio,
        ]);
    }

    public function update(Request $request, $zonaId, $sitioId)
    {
        $sitio = SitioFrecuentacion::where('zona_id', $zonaId)->findOrFail($sitioId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        $request->validate($this->reglas());

        // Misma transacción que store(): la actualización y la reapertura
        // tienen que caer juntas o ninguna, por el mismo motivo.
        $reabrio = DB::transaction(function () use ($sitio, $request, $zonaId) {
            $sitio->update($this->datosDe($request));

            return $this->reabrirSiConfirmada($zonaId);
        });

        return redirect()->route('operativo.frecuentacion.index', $zonaId)
            ->with('success', $this->mensajeConReapertura('Sitio actualizado correctamente.', $reabrio));
    }

    public function destroy($zonaId, $sitioId)
    {
        $sitio = SitioFrecuentacion::where('zona_id', $zonaId)->findOrFail($sitioId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        // Borrar y reabrir en la misma transacción, por el mismo motivo que
        // store()/update(): sin ella, un fallo al reabrir podía dejar la
        // lista "confirmado" con un sitio de menos del que se validó.
        $reabrio = DB::transaction(function () use ($sitio, $zonaId) {
            $sitio->delete();

            return $this->reabrirSiConfirmada($zonaId);
        });

        return redirect()->route('operativo.frecuentacion.index', $zonaId)
            ->with('success', $this->mensajeConReapertura('Sitio eliminado.', $reabrio));
    }

    /**
     * Guarda la Superficie Territorial (ST) de la zona: un escalar de
     * configuración, no un sitio, así que no pasa por store()/update() del
     * CRUD de sitios. `gt:0` rechaza cero y negativos desde el formulario;
     * Frecuentacion::ietp() es una segunda defensa, no la única, por si algún
     * dato llega por otro camino -ver el diseño, punto 2-.
     */
    public function actualizarSuperficie(Request $request, $zonaId)
    {
        Zona::findOrFail($zonaId);

        if ($bloqueo = $this->bloqueoSiCerrada($zonaId)) {
            return $bloqueo;
        }

        $request->validate(['st' => 'nullable|numeric|gt:0']);

        // Igual que store()/update()/destroy(): guardar la ST y reabrir la
        // lista confirmada en la misma transacción. La ST es compartida por
        // todos los sitios, así que cambiarla invalida el ÍEFT ya certificado
        // aunque no se haya tocado ningún sitio -ver el diseño, punto 3-.
        $reabrio = DB::transaction(function () use ($request, $zonaId) {
            FrecuentacionConfig::updateOrCreate(
                ['zona_id' => $zonaId],
                ['user_id' => Auth::id(), 'st' => $request->input('st')]
            );

            return $this->reabrirSiConfirmada($zonaId);
        });

        return redirect()->route('operativo.frecuentacion.index', $zonaId)
            ->with('success', $this->mensajeConReapertura('Superficie Territorial guardada correctamente.', $reabrio));
    }

    /**
     * Cierra la lista de sitios. Exige, en este orden: al menos un sitio;
     * ninguno con el DET sin responder; una ST definida y mayor que cero -sin
     * ella, ÍEFT no se puede calcular para ningún sitio, ver el diseño,
     * punto 2-.
     */
    public function validar($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $user = Auth::user();

        // Ruta dedicada, igual que InvolucradosController::validar(): aquí
        // no hay un formulario único que enviar, así que se rechaza sin más
        // al que no sea jefe en vez de degradar la petición en silencio.
        abort_unless($user->esJefe(), 403);

        $total = $zona->frecuentacionSitios()->count();

        if ($total === 0) {
            return back()->with('error', 'No puedes validar sin al menos un sitio registrado.');
        }

        if ($zona->frecuentacionSitios()->incompletos()->exists()) {
            return back()->with('error', 'No puedes validar: hay sitios con el DET sin responder.');
        }

        $config = FrecuentacionConfig::where('zona_id', $zonaId)->first();

        if (($config?->st ?? null) === null || $config->st <= 0) {
            return back()->with('error', 'No puedes validar sin una Superficie Territorial mayor que cero.');
        }

        FrecuentacionConfig::updateOrCreate(
            ['zona_id' => $zonaId],
            ['user_id' => $user->id, 'estado' => 'confirmado']
        );

        return redirect()->route('operativo.frecuentacion.resultados', $zonaId)
            ->with('success', 'Lista de sitios VALIDADA y CERRADA correctamente.');
    }

    /**
     * Resultados: el ÍETP de cada sitio y el ÍEFT del territorio.
     *
     * "Completa" exige lo mismo que validar(): sitios no vacío, ninguno
     * incompleto, y una ST definida y positiva. Solo entonces se arman las
     * filas -Frecuentacion::ietp()/ieft() no se llaman con datos a medias,
     * mismo principio que ConcentracionCalculo e Involucrados-.
     */
    public function resultados($zonaId)
    {
        $zona   = Zona::findOrFail($zonaId);
        $sitios = $zona->frecuentacionSitios()->get();
        $config = FrecuentacionConfig::where('zona_id', $zonaId)->first();

        $incompletos = $sitios->contains(fn(SitioFrecuentacion $s) => ! $s->estaCompleto());
        $stValida    = ($config?->st ?? null) !== null && $config->st > 0;

        $completa = $sitios->isNotEmpty() && ! $incompletos && $stValida;

        $filas = collect();
        $ieft  = null;

        if ($completa) {
            $filas = $sitios->map(fn(SitioFrecuentacion $sitio) => [
                'sitio' => $sitio,
                'ietp'  => Frecuentacion::ietp($sitio->det, $config->st),
            ]);

            $ieft = Frecuentacion::ieft($filas->pluck('ietp')->all());
        }

        return view('operativo.frecuentacion.resultados', [
            'zona'     => $zona,
            'completa' => $completa,
            'config'   => $config,
            'filas'    => $filas,
            'ieft'     => $ieft,
            'motivoSinResultados' => $completa ? null : $this->motivoSinResultados($sitios, $incompletos, $stValida),
        ]);
    }

    /**
     * Por qué no hay resultados que enseñar, en una frase -no "no aplica"
     * fila por fila-: la lista vacía o con sitios a medias es un motivo
     * (faltan sitios por responder), y la ST ausente o en cero es otro
     * (falta un dato de la zona entera, no de un sitio). Son causas
     * distintas y pueden darse las dos a la vez, mismo criterio que
     * EstadoZona::filaSitios() ya usa para el detalle de la página de zona.
     *
     * @param \Illuminate\Support\Collection<int, SitioFrecuentacion> $sitios
     */
    private function motivoSinResultados($sitios, bool $incompletos, bool $stValida): string
    {
        $faltanSitios = $sitios->isEmpty() || $incompletos;
        $faltaSt      = ! $stValida;

        return match (true) {
            $faltanSitios && $faltaSt => 'Faltan sitios por responder, y además falta la Superficie Territorial, o es cero.',
            $faltanSitios             => 'Faltan sitios por responder.',
            default                   => 'Falta la Superficie Territorial, o es cero.',
        };
    }

    /**
     * ! esJefe() y no esEquipo(): mismo motivo que
     * InvolucradosController::bloqueoSiCerrada(). Desde que el admin
     * escribe (PermisosAdminTest), él también tiene que encontrarse la lista
     * cerrada.
     */
    private function bloqueoSiCerrada($zonaId): ?RedirectResponse
    {
        $config = FrecuentacionConfig::where('zona_id', $zonaId)->first();

        if ($config?->estado === 'confirmado' && ! Auth::user()->esJefe()) {
            return redirect()->route('operativo.frecuentacion.index', $zonaId)
                ->with('error', $this->mensajeCerrada());
        }

        return null;
    }

    private function mensajeCerrada(): string
    {
        return 'Esta lista de sitios ya fue validada por el Jefe de Zona. No puedes editarla.';
    }

    /**
     * "Confirmado" significa que ESE ÍEFT exacto fue validado. store()/
     * update()/destroy()/actualizarSuperficie() la llaman de forma explícita,
     * dentro de la misma transacción que su propia escritura -mismo motivo
     * que InvolucradosController::reabrirSiConfirmada()-.
     */
    private function reabrirSiConfirmada($zonaId): bool
    {
        $config = FrecuentacionConfig::where('zona_id', $zonaId)->first();

        if ($config?->estado !== 'confirmado') {
            return false;
        }

        $config->update(['estado' => 'borrador', 'user_id' => Auth::id()]);

        return true;
    }

    /**
     * Compone el mensaje de éxito de una escritura, avisando si de paso se
     * reabrió una lista ya validada. Mismo reparto que
     * InvolucradosController::mensajeConReapertura(): recibe el booleano ya
     * decidido, no dispara la transición de estado.
     */
    private function mensajeConReapertura(string $base, bool $reabrio): string
    {
        return $reabrio
            ? "{$base} Al modificar la lista ya validada, vuelve a borrador: hay que validarla de nuevo."
            : $base;
    }

    /** @return array<string, string> */
    private function reglas(): array
    {
        return [
            'nombre' => 'required|string|max:200',
            // Sin unidad confirmada (ver el diseño, punto 5) y sin tope
            // natural: mismo argumento que Concentración usa para sus
            // conteos, aplicado aquí a un decimal en vez de un entero.
            'det' => 'nullable|numeric|min:0',
        ];
    }

    /** @return array<string, mixed> */
    private function datosDe(Request $request): array
    {
        return [
            'nombre' => $request->input('nombre'),
            'det'    => $request->input('det'),
        ];
    }
}
