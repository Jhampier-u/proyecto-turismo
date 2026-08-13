<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Servicios\EstadoZona;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Los órdenes que la tabla ofrece. Es la lista blanca: lo que no esté
     * aquí cae al de por defecto.
     */
    private const ORDENES = ['nombre', 'lugar', 'progreso'];

    private const DIRECCIONES = ['asc', 'desc'];

    private const ORDEN_POR_DEFECTO = 'nombre';

    private const DIRECCION_POR_DEFECTO = 'asc';

    public function index(Request $request)
    {
        $user = Auth::user();

        // El admin no usa este dashboard — tiene el suyo propio
        if ($user->esAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $zonas = match (true) {
            $user->esJefe()   => $user->zonasComoJefe()->with('lugar')->get(),
            $user->esEquipo() => $user->zonasComoEquipo()->with('lugar')->get(),
            default           => collect(),
        };

        // Antes esto eran cuatro consultas manuales que había que ampliar con
        // cada matriz nueva; una de ellas se olvidó y Paisaje quedó fuera.
        //
        // progresoDe() resuelve todas las zonas con un número fijo de
        // consultas (una por matriz). Instanciar un EstadoZona por zona aquí
        // costaba una consulta por matriz Y por zona — correcto pero no
        // escalaba con la lista de zonas del jefe.
        $progreso = EstadoZona::progresoDe($zonas);

        // Las cifras de conjunto, calculadas aquí y no en un @php de la vista:
        // el panel de admin ya recibe su $resumen así, y las dos portadas del
        // sistema se parecen también por dentro.
        $resumen = [
            'zonas'      => $zonas->count(),
            'validadas'  => array_sum(array_column($progreso, 'hechas')),
            'matrices'   => array_sum(array_column($progreso, 'total')),
            'terminadas' => count(array_filter(
                $progreso,
                fn(array $p) => $p['total'] > 0 && $p['hechas'] === $p['total']
            )),
        ];

        // proximoPaso() recibe $progreso ya calculado -pedirlo dos veces
        // duplicaría las consultas de progresoDe() sin ganar nada- y la
        // colección en el orden POR DEFECTO, no en el que pida la URL:
        // recorre las zonas en el orden que recibe y se detiene en la primera
        // con algo pendiente, así que con el orden de la tabla la
        // recomendación de arriba saltaría de zona cada vez que alguien pulsa
        // una cabecera. El panel es un consejo, no una fila de la lista.
        $proximoPaso = EstadoZona::proximoPaso(
            $user,
            $this->ordenar($zonas, $progreso, self::ORDEN_POR_DEFECTO, self::DIRECCION_POR_DEFECTO),
            $progreso
        );

        [$orden, $dir] = $this->ordenPedido($request);

        $zonas = $this->ordenar($zonas, $progreso, $orden, $dir);

        return view('operativo.dashboard', compact('zonas', 'progreso', 'proximoPaso', 'resumen', 'orden', 'dir'));
    }

    /**
     * El orden que pide la URL, o el de por defecto.
     *
     * Lista blanca y caída en silencio, con 200: un `orden` viejo en un
     * enlace compartido, o un `dir=arriba` escrito a mano, no deberían
     * enseñar una pantalla de error en la portada de la aplicación. Y
     * `query()` puede devolver un array -`?orden[]=x`-, que in_array
     * descarta sin reventar.
     *
     * @return array{0: string, 1: string}
     */
    private function ordenPedido(Request $request): array
    {
        $orden = $request->query('orden');
        $dir   = $request->query('dir');

        return [
            in_array($orden, self::ORDENES, true) ? $orden : self::ORDEN_POR_DEFECTO,
            in_array($dir, self::DIRECCIONES, true) ? $dir : self::DIRECCION_POR_DEFECTO,
        ];
    }

    /**
     * Ordena la colección en PHP, no con orderBy en SQL.
     *
     * El progreso no está en ninguna columna: se calcula a partir de las diez
     * matrices. Escribirlo en SQL sería tenerlo en dos idiomas, y son las
     * zonas de un operativo -unas pocas-, así que ordenar en memoria no
     * cuesta nada. Si algún día alguien acumulara cientos, el sitio donde
     * arreglarlo es este mismo método.
     *
     * @param  Collection<int, Zona>  $zonas
     * @param  array<int, array{hechas: int, total: int}>  $progreso
     * @return Collection<int, Zona>
     */
    private function ordenar(Collection $zonas, array $progreso, string $orden, string $dir): Collection
    {
        $clave = match ($orden) {
            'lugar'    => fn(Zona $zona) => $zona->lugar?->nombre ?? '',
            'progreso' => fn(Zona $zona) => $progreso[$zona->id]['total'] > 0
                ? $progreso[$zona->id]['hechas'] / $progreso[$zona->id]['total']
                : 0,
            default    => fn(Zona $zona) => $zona->nombre,
        };

        // Los nombres, en orden natural y sin distinguir mayúsculas: «Zona 10»
        // va después de «Zona 9», y «playa» no se cuela detrás de «Zona» por
        // empezar en minúscula. El progreso es un número y no quiere flags.
        $ordenadas = $orden === 'progreso'
            ? $zonas->sortBy($clave)
            : $zonas->sortBy($clave, SORT_NATURAL | SORT_FLAG_CASE);

        return ($dir === 'desc' ? $ordenadas->reverse() : $ordenadas)->values();
    }
}
