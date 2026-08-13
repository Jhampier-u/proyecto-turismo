<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">Evaluación de Potencialidad Turística</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $zona->nombre }}</p>
            </div>
            <div class="flex gap-2 items-center flex-wrap">
                @if($user->esJefe())
                <form method="POST"
                      action="{{ route('operativo.evaluacion_potencialidad.reconfigurar', $zona->id) }}"
                      onsubmit="return confirm('¿Activar todos los campos? Se restaurará la selección completa.');">
                    @csrf
                    {{-- Iba con `style=` en línea, que es por lo que ningún
                         barrido de botones escritos a mano lo encontraba: los
                         buscaban por `class`. --}}
                    <x-boton variante="secundario">
                        ↺ Activar todos los campos
                    </x-boton>
                </form>
                @endif
                {{-- Este formulario vive dentro de una zona: "volver" baja a
                     su panel, no salta a Mis Zonas -mismo criterio que el
                     resto de matrices. --}}
                {{-- Sin el repintado a enlace de texto que tenía: eran nueve
                     modificadores "!" de Tailwind que ganaban a las clases del
                     componente, y dejaban este «Volver a la Zona» sin
                     parecerse al de ninguna otra pantalla. --}}
                <x-boton-volver :zona="$zona" texto="← Volver a la Zona" />
            </div>
        </div>
    </x-slot>

    @php
        $isConfirmado    = $evaluacion->exists && $evaluacion->estado === 'confirmado';
        // Un solo motivo desde que el admin edita. esEquipo() se cambia por
        // ! esJefe() para que el admin también encuentre cerrada una matriz
        // validada, como decidió el diseño.
        $soloLectura     = $isConfirmado && ! $user->esJefe();
        $puedeConfigurar = ($user->esJefe()) && !$soloLectura;
        $puedeCalificar  = !$soloLectura;

        $areaConfig = [
            'Recursos Naturales'    => ['emoji'=>'🏔️','color'=>'green', 'secs'=>array_filter($secciones,fn($k)=>str_starts_with($k,'RN'),ARRAY_FILTER_USE_KEY)],
            'Recursos Culturales'   => ['emoji'=>'🏛️','color'=>'amber', 'secs'=>array_filter($secciones,fn($k)=>str_starts_with($k,'RC'),ARRAY_FILTER_USE_KEY)],
            'Planta Turística'      => ['emoji'=>'🏨','color'=>'blue',  'secs'=>array_filter($secciones,fn($k)=>str_starts_with($k,'PT'),ARRAY_FILTER_USE_KEY)],
            'Tipologías de Turismo' => ['emoji'=>'🧭','color'=>'violet','secs'=>array_filter($secciones,fn($k)=>$k==='Tipologías de Turismo',ARRAY_FILTER_USE_KEY)],
            'Infraestructura'       => ['emoji'=>'🔌','color'=>'slate', 'secs'=>array_filter($secciones,fn($k)=>$k==='Infraestructura',ARRAY_FILTER_USE_KEY)],
            'Factores Exógenos'     => ['emoji'=>'📊','color'=>'indigo','secs'=>array_filter($secciones,fn($k)=>in_array($k,['Afluencia Turística','Marketing Turístico','Superestructura']),ARRAY_FILTER_USE_KEY)],
        ];
    @endphp

    {{--
        Por qué esta vista conserva CSS propio, y qué se le quitó.

        SE QUEDA: el acordeón de áreas, el toggle de campo activo, la rejilla
        auto-fill y las seis cabeceras en degradado. Son maquetación de un
        formulario de 156 criterios que no existe en ninguna otra pantalla;
        reescribirla en clases del sistema cambiaría el aspecto y arriesgaría
        regresiones que los tests de esta matriz —que cuentan radios y campos
        activos, no maquetación— no verían.

        SE FUE, porque no era maquetación propia sino un segundo sistema:

        - Un @import a un proveedor de fuentes ajeno pidiendo DM Sans, más un
          font-family en .pt-root. La aplicación entera va con Inter desde
          FV4, servida además por otro proveedor (fonts.bunny.net, en
          layouts/app). Esta era la única pantalla con otra letra, y ningún
          test lo veía: los tests miran HTML, y el HTML de una página con otra
          tipografía es idéntico al de una con la correcta. Es el mismo
          hallazgo que FV4 tuvo que corregir en layouts/guest, que se había
          quedado pidiendo Figtree. Ahora lo vigila TipografiaUnicaTest.
        - background:#f0f4f8 y min-height:100vh. El fondo del sistema es el
          bg-gray-50 del layout —#f8fafc con el alias de tailwind.config.js—,
          así que esta vista pintaba el suyo encima.

        LOS GRISES QUE QUEDAN son copias de la escala del sistema, y por eso
        van anotados uno a uno: #f8fafc slate-50, #f1f5f9 slate-100, #e2e8f0
        slate-200, #cbd5e1 slate-300, #64748b slate-500, #334155 slate-700,
        #1e293b slate-800. Son copias de verdad, no referencias, y una copia se
        queda atrás sola: dos de ellas ya lo habían hecho —#d1d5db y #374151
        eran gray-300 y gray-700 de la paleta ANTERIOR a FV4, que nadie
        actualizó cuando `gray` pasó a ser alias de `slate`—. Corregidas aquí.

        Los otros ~23 hexadecimales del bloque NO están anotados, y conviene
        saberlo antes de dar la lista de arriba por completa: son los verdes de
        confirmado, los rojos de error, los ámbar de aviso y los seis
        degradados de cabecera de área. Se quedan sin cotejar porque cotejarlos
        es la migración que esta rama decidió no hacer —no un descuido—: son el
        aspecto propio de la pantalla, no la escala de grises que el sistema
        centraliza. El riesgo de que se queden atrás es el mismo; lo que cambia
        es que ahí no hay un token del que sean copia evidente.

        BOMBA CONOCIDA, no desactivada: `class="pt-area area-{{ $color }}"`
        se construye por concatenación, que es justo lo que Tailwind purga.
        Hoy no explota porque `area-*` es CSS de este bloque y no de Tailwind.
        El día que alguien migre estas reglas a clases del sistema, los seis
        colores de área desaparecen en silencio. Está aquí escrito para que ese
        día no sea una sorpresa.
    --}}
    <style>
        .pt-root { padding:1.5rem 0 5rem; }

        /* ── Area accordion ────────────────────────────────────────────────── */
        .pt-area { background:#fff; border-radius:14px; border:1.5px solid #e2e8f0; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.04); }
        .pt-area-header { display:flex; align-items:center; justify-content:space-between; padding:13px 18px; cursor:pointer; user-select:none; transition:opacity .15s; }
        .pt-area-header:hover { opacity:.92; }
        .pt-area-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; background:rgba(255,255,255,.25); }
        .pt-area-name { font-weight:700; font-size:.88rem; }
        .pt-area-meta { font-size:.7rem; opacity:.8; margin-top:1px; }
        .pt-area-chevron { width:17px; height:17px; transition:transform .2s; color:rgba(255,255,255,.8); }
        .pt-area-chevron.open { transform:rotate(180deg); }
        .pt-area-body { border-top:1px solid #f1f5f9; padding:14px 16px; display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:12px; }

        /* Area colors */
        .area-green  .pt-area-header { background:linear-gradient(135deg,#166534,#15803d); color:#fff; }
        .area-amber  .pt-area-header { background:linear-gradient(135deg,#92400e,#b45309); color:#fff; }
        .area-blue   .pt-area-header { background:linear-gradient(135deg,#1e3a8a,#1d4ed8); color:#fff; }
        .area-violet .pt-area-header { background:linear-gradient(135deg,#4c1d95,#6d28d9); color:#fff; }
        .area-slate  .pt-area-header { background:linear-gradient(135deg,#1e293b,#334155); color:#fff; }
        .area-indigo .pt-area-header { background:linear-gradient(135deg,#312e81,#4338ca); color:#fff; }

        /* ── Section card ──────────────────────────────────────────────────── */
        .pt-section { border:1.5px solid #e2e8f0; border-radius:11px; padding:12px; background:#fafbfc; }
        .pt-section-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #e2e8f0; gap:8px; }
        .pt-section-name { font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
        .pt-section-count { font-size:.68rem; font-weight:600; padding:1px 7px; border-radius:10px; }
        .pt-section-btns { display:flex; gap:6px; flex-shrink:0; }
        .pt-btn-all  { font-size:.65rem; font-weight:700; color:#15803d; background:#f0fdf4; border:1px solid #bbf7d0; padding:2px 8px; border-radius:6px; cursor:pointer; }
        .pt-btn-none { font-size:.65rem; font-weight:700; color:#dc2626; background:#fef2f2; border:1px solid #fecaca; padding:2px 8px; border-radius:6px; cursor:pointer; }
        .pt-btn-all:hover  { background:#dcfce7; }
        .pt-btn-none:hover { background:#fee2e2; }

        /* ── Field rows ────────────────────────────────────────────────────── */
        /* align-items:flex-start, no center: el componente de criterio pinta
           su propio nombre encima de las píldoras, así que la fila ya no es
           una línea de ~38px sino un bloque; el toggle/punto queda arriba, a
           la altura del nombre, en vez de centrado contra todo el bloque. */
        .pt-field-row { display:flex; align-items:flex-start; gap:10px; padding:10px 8px; border-radius:8px; transition:background .12s; }
        .pt-field-row:hover { background:#fff; }
        .pt-field-row.pt-inactive { opacity:.55; }
        .pt-field-control { flex:1; min-width:0; }

        /* Toggle switch */
        .pt-toggle { position:relative; flex-shrink:0; width:36px; height:20px; cursor:pointer; margin-top:3px; }
        .pt-toggle input { opacity:0; width:0; height:0; position:absolute; }
        .pt-toggle-track { display:block; width:36px; height:20px; border-radius:10px; transition:background .2s; position:relative; }
        .pt-toggle-thumb { position:absolute; top:3px; left:3px; width:14px; height:14px; background:#fff; border-radius:50%; box-shadow:0 1px 3px rgba(0,0,0,.2); transition:transform .2s; }

        /* Dot indicator (non-Jefe roles) */
        .pt-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; margin-top:6px; }
        .pt-dot-on  { background:#22c55e; box-shadow:0 0 0 2px #dcfce7; }
        /* slate-300. Era #d1d5db, que es gray-300 de la paleta anterior a
           FV4: se quedó atrás cuando `gray` pasó a ser alias de `slate`. */
        .pt-dot-off { background:#cbd5e1; }

        /* Banner confirmado */
        .pt-banner-ok { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1.5px solid #86efac; border-radius:13px; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
        .pt-banner-warn { background:#fffbeb; border:1.5px solid #fde68a; border-radius:13px; padding:12px 16px; margin-bottom:18px; font-size:.83rem; color:#92400e; }

        /* Footer */
        .pt-footer { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:16px 22px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; box-shadow:0 1px 3px rgba(0,0,0,.05); margin-top:16px; }
        /* color: slate-700. Era #374151, gray-700 de la paleta anterior. */
        .pt-btn-draft   { background:#f8fafc; color:#334155; font-weight:700; font-size:.84rem; padding:10px 20px; border-radius:10px; border:1.5px solid #e2e8f0; cursor:pointer; display:flex; align-items:center; gap:7px; }
        .pt-btn-confirm { background:linear-gradient(135deg,#15803d,#16a34a); color:#fff; font-weight:700; font-size:.84rem; padding:10px 20px; border-radius:10px; border:none; cursor:pointer; display:flex; align-items:center; gap:7px; box-shadow:0 4px 12px rgba(22,163,74,.3); }
        .pt-btn-draft:hover   { background:#f1f5f9; }
        .pt-btn-confirm:hover { opacity:.9; }

    </style>

    {{--
        Aquí había un contenedor de página escrito como estilo en línea
        —max-width:1160px;margin:0 auto—, resto de cuando esta vista traía su
        propio CSS de layout. Al no ser una clase de Tailwind no aparecía en
        la búsqueda que encontró los otros 28, y sin quitarlo Potencialidad se
        habría quedado en 1160px mientras las demás matrices pasaban a 1440.

        Se borra sin sustituirlo: el layout ya envuelve todo el contenido en
        <x-contenedor>, y poner otro aquí aplicaría su padding dos veces.
    --}}
    <div class="pt-root">

        <x-pestanas-matriz clave="potencialidad" :zona="$zona" activa="formulario" />

        @if($evaluacion?->exists && $evaluacion->user)
            <p class="text-sm text-gray-500 mb-4">
                Última edición: {{ $evaluacion->user->name }},
                {{ $evaluacion->updated_at->diffForHumans() }}
            </p>
        @endif

        {{-- Mensajes ─────────────────────────────────────────────────────── --}}
        @if(session('success'))
        <div style="background:#f0fdf4;border:1.5px solid #86efac;color:#15803d;padding:11px 16px;border-radius:11px;margin-bottom:14px;font-size:.84rem;font-weight:500;display:flex;align-items:center;gap:8px;">
            ✅ {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div style="background:#fef2f2;border:1.5px solid #fecaca;color:#b91c1c;padding:11px 16px;border-radius:11px;margin-bottom:14px;font-size:.84rem;">
            ⚠️ {{ session('error') }}
        </div>
        @endif

        {{-- Banner estado ─────────────────────────────────────────────────── --}}
        @if($isConfirmado)
        <div class="pt-banner-ok">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:22px;">✅</span>
                <div>
                    <div style="font-weight:700;color:#15803d;font-size:.93rem;">Evaluación confirmada y validada</div>
                    @if($user->esEquipo())
                    <div style="font-size:.8rem;color:#166534;margin-top:2px;">Esta evaluación fue cerrada por el Jefe de Zona. Solo puedes consultar los valores.</div>
                    @endif
                </div>
            </div>
        </div>
        @elseif($evaluacion->exists)
        <div class="pt-banner-warn">
            ✏️ <strong>Modo Borrador</strong> — Los datos ingresados son preliminares. El Jefe de Zona debe validar para generar el resultado oficial.
        </div>
        @endif

        @if($user->esEquipo() && !$isConfirmado)
        <div style="background:#eff6ff;border:1.5px solid #bfdbfe;color:#1e40af;padding:11px 16px;border-radius:11px;margin-bottom:14px;font-size:.82rem;display:flex;align-items:center;gap:8px;">
            🔒 Solo el <strong>Jefe de Zona</strong> puede activar o desactivar campos. Tú puedes calificar los campos activos.
        </div>
        @endif

        {{-- Escala de calificación: vivía en una tarjeta de la .pt-sidebar
             de siempre; se traslada al cuerpo con el mismo componente que
             ya usan FIT, FET, Paisaje y Valoración Territorial -Potencialidad
             recibe $niveles desde el controlador desde siempre, pero nunca
             lo había usado-. --}}
        <x-leyenda-escala :niveles="$niveles" />

        @if($puedeConfigurar)
        {{-- Nota "Modo Jefe": la misma que vivía en su propia tarjeta de la
             .pt-sidebar, reubicada aquí con las mismas utilidades Tailwind
             que ya usa el aviso del equipo justo arriba. --}}
        <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-4 mb-4 text-sm">
            <strong class="font-semibold">Modo Jefe:</strong> usa los interruptores para activar o
            desactivar un campo según la realidad de tu zona. Solo los campos activos se incluyen
            en el cálculo final.
        </div>
        @endif

        @php
            // El índice de la barra lateral: una entrada por área -las
            // mismas seis que ya recorre el cuerpo, más abajo-, contada
            // sobre los campos ACTIVOS de cada una, no sobre todos los que
            // declara Potencialidad::SECCIONES. El Jefe decide qué
            // criterios aplican (PotencialidadCamposActivos), así que el
            // denominador de cada área se mueve igual que el de la
            // cabecera -ver el comentario de <x-barra-lateral-formulario>-.
            //
            // Un área que el Jefe dejó sin ningún campo activo no tiene
            // fracción que enseñar -sería un "0 de 0" que el componente
            // pintaría con su marcador de completa, sin serlo-, así que se
            // omite del índice en vez de fingir un dato.
            $indiceAreas = collect($areaConfig)
                ->map(function ($areaData, $areaName) use ($camposActivos, $evaluacion) {
                    $camposArea  = collect($areaData['secs'])->flatMap(fn($campos) => array_keys($campos))->all();
                    $activosArea = array_values(array_intersect($camposArea, $camposActivos));

                    return [
                        'ancla'       => 'area-' . Str::slug($areaName),
                        'etiqueta'    => $areaName,
                        'total'       => count($activosArea),
                        'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, $activosArea),
                    ];
                })
                ->filter(fn($seccion) => $seccion['total'] > 0)
                ->values()
                ->all();

            // Misma idea para la cabecera: el override de
            // <x-barra-lateral-formulario> exige :total y :respondidos
            // juntos, los dos sobre los campos activos -nunca sobre los 156
            // fijos del registro-.
            $totalActivos       = count($camposActivos);
            $respondidosActivos = \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, $camposActivos);
        @endphp

        <div class="lg:grid lg:grid-cols-[1fr_256px] lg:gap-6 lg:items-start">
        <div class="lg:min-w-0">
            <form method="POST"
                  action="{{ route('operativo.evaluacion_potencialidad.update', $zona->id) }}"
                  id="pt-form">
              @csrf

              <div style="display:flex;flex-direction:column;gap:12px;">

              @foreach($areaConfig as $areaName => $areaData)
                @php
                    $color = $areaData['color'];

                    // Contar campos visibles y activos en esta área
                    $totalArea = 0; $activosArea = 0;
                    foreach ($areaData['secs'] as $sec => $camposSec) {
                        foreach ($camposSec as $campo => $lbl) {
                            $esVisible = $puedeConfigurar || in_array($campo, $camposActivos);
                            if ($esVisible) $totalArea++;
                            if (in_array($campo, $camposActivos)) $activosArea++;
                        }
                    }
                    if ($totalArea === 0) continue;
                @endphp

                <div class="pt-area area-{{ $color }}" id="area-{{ Str::slug($areaName) }}" x-data="{ open: true }">

                  {{-- Encabezado del área ────────────────────────────────── --}}
                  <div class="pt-area-header" @click="open = !open">
                    <div style="display:flex;align-items:center;gap:12px;">
                      <div class="pt-area-icon">{{ $areaData['emoji'] }}</div>
                      <div>
                        <div class="pt-area-name">{{ $areaName }}</div>
                        <div class="pt-area-meta">{{ $activosArea }} activos · {{ $totalArea }} campos</div>
                      </div>
                    </div>
                    <svg :class="open?'pt-area-chevron open':'pt-area-chevron'"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </div>

                  {{-- Cuerpo del área ───────────────────────────────────── --}}
                  <div x-show="open" x-transition class="pt-area-body">

                    @foreach($areaData['secs'] as $secNombre => $todosCampos)
                      @php
                          // Jefe ve todos; otros solo ven activos
                          $camposSeccion = $puedeConfigurar
                              ? $todosCampos
                              : array_filter($todosCampos, fn($c) => in_array($c, $camposActivos), ARRAY_FILTER_USE_KEY);
                          if (empty($camposSeccion)) continue;

                          $nombreCorto = preg_replace('/^(RN|RC|PT) — /', '', $secNombre);
                          $activosSec  = count(array_filter(array_keys($todosCampos), fn($c) => in_array($c, $camposActivos)));
                          $totalSec    = count($camposSeccion);

                          // ID único para esta sección (usado en Alpine)
                          $secId = Str::slug($secNombre);
                          $camposJson = json_encode(array_keys($camposSeccion));
                          $activosJson = json_encode($camposActivos);

                          // Calificación inicial de cada criterio de la
                          // sección, para el x-data que lee el componente de
                          // criterio (pildoras).
                          // old() manda sobre lo guardado -si la validación
                          // falla, el formulario tiene que devolver los demás
                          // criterios tal como estaban, no lo último guardado-,
                          // y un hueco llega como null, no como 0: (int) null
                          // lo convertiría en «Ausencia», que es una
                          // calificación real. Mismo criterio que FIT, FET y
                          // Percepción; antes de este cambio el <select> de
                          // Potencialidad preseleccionaba 0 con `?? 0`, así que
                          // un criterio activo nunca visitado se enviaba como
                          // «Ausencia» en vez de quedar sin responder.
                          $valoresIniciales = collect($camposSeccion)->mapWithKeys(
                              function ($lbl, $campo) use ($evaluacion) {
                                  $valor = old($campo, $evaluacion->$campo);

                                  return [$campo => $valor === null || $valor === '' ? null : (int) $valor];
                              }
                          )->all();
                      @endphp

                      {{-- Sección ──────────────────────────────────────── --}}
                      <div class="pt-section"
                           x-data="ptSection({{ $camposJson }}, {{ $activosJson }}, @js($valoresIniciales))"
                           x-init="init()">

                        {{-- Encabezado de sección ──────────────────────── --}}
                        <div class="pt-section-head">
                          <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0;">
                            <span class="pt-section-name">{{ $nombreCorto }}</span>
                            <span class="pt-section-count"
                                  style="background:#e0e7ff;color:#3730a3;"
                                  x-text="activosCount + '/{{ $totalSec }}'"></span>
                          </div>
                          @if($puedeConfigurar)
                          <div class="pt-section-btns">
                            <button type="button" class="pt-btn-all"
                                    @click="selectAll(true)">Todos</button>
                            <button type="button" class="pt-btn-none"
                                    @click="selectAll(false)">Ninguno</button>
                          </div>
                          @endif
                        </div>

                        {{-- Filas de campos ──────────────────────────── --}}
                        @foreach($camposSeccion as $campo => $label)
                          <div class="pt-field-row"
                               :class="{ 'pt-inactive': !states['{{ $campo }}'] }">

                            {{-- ── JEFE: toggle switch interactivo ────── --}}
                            @if($puedeConfigurar)
                              <label class="pt-toggle" title="Activar / desactivar campo">
                                <input type="checkbox"
                                       name="campos[]"
                                       value="{{ $campo }}"
                                       x-model="states['{{ $campo }}']"
                                       @change="updateCount()">
                                <div class="pt-toggle-track"
                                     :class="states['{{ $campo }}'] ? 'bg-indigo-500' : 'bg-gray-300'">
                                  <div class="pt-toggle-thumb"
                                       :style="states['{{ $campo }}'] ? 'transform:translateX(16px)' : 'transform:translateX(0)'">
                                  </div>
                                </div>
                              </label>

                            {{-- ── EQUIPO: dot + hidden input ──────────── --}}
                            @elseif(!$soloLectura)
                              @if(in_array($campo, $camposActivos))
                                <input type="hidden" name="campos[]" value="{{ $campo }}">
                                <span class="pt-dot pt-dot-on" title="Campo activo"></span>
                              @else
                                <span class="pt-dot pt-dot-off" title="Campo inactivo"></span>
                              @endif

                            {{-- ── ADMIN: dot indicador ────────────────── --}}
                            @else
                              <span class="pt-dot {{ in_array($campo, $camposActivos) ? 'pt-dot-on' : 'pt-dot-off' }}"
                                    title="{{ in_array($campo, $camposActivos) ? 'Campo activo' : 'Campo inactivo' }}">
                              </span>
                            @endif

                            {{-- Criterio: nombre + escala con el mismo
                                 componente que ya usan Paisaje, FIT, FET y
                                 Percepción, en vez del <select> / insignia de
                                 antes. :bloqueado sigue siendo $soloLectura
                                 -lectura por rol o evaluación confirmada,
                                 fijo al renderizar-; :activo-expr es lo
                                 nuevo: el servidor ya ignoraba la
                                 calificación de un campo inactivo sin
                                 importar lo que se enviara (prepararDatos()
                                 la descarta y conserva el valor guardado), así
                                 que nunca hubo un bug de datos, pero las
                                 píldoras seguían respondiendo al clic con la
                                 fila ya fundida a opacidad 0.55 -un control
                                 que reacciona cuando no debería-. states() ya
                                 existe en este x-data para el toggle; solo
                                 hacía falta pasarle su nombre al componente
                                 para que también gobierne el disabled de cada
                                 radio, en vivo y sin recargar. --}}
                            <div class="pt-field-control">
                              <x-criterio-pildoras
                                  :campo="$campo"
                                  :criterio="['nombre' => $label, 'niveles' => $niveles]"
                                  :bloqueado="$soloLectura"
                                  :activo-expr="'states[\'' . $campo . '\']'" />
                            </div>

                          </div>{{-- /pt-field-row --}}
                        @endforeach

                      </div>{{-- /pt-section --}}
                    @endforeach

                  </div>{{-- /pt-area-body --}}
                </div>{{-- /pt-area --}}
              @endforeach

              </div>{{-- /flex column --}}

              {{-- ── Footer de acciones ──────────────────────────────── --}}
              @if(!$soloLectura)
              {{-- El jefe siempre pasa por aquí (nunca está $soloLectura
                   para él), así que es el único sitio donde puede pulsar
                   "Guardar borrador" sobre una evaluación ya validada sin
                   saber que la reabre. --}}
              @if($isConfirmado && $user->esJefe())
                <x-aviso-reapertura sustantivo="evaluación" class="mb-3" />
              @endif
              <div class="pt-footer">
                <div>
                  <div style="font-weight:700;font-size:.88rem;color:#1e293b;">
                    @if($puedeConfigurar) Configurar campos y calificar @else Calificar campos activos @endif
                  </div>
                  <div style="font-size:.77rem;color:#64748b;margin-top:2px;">
                    @if($puedeConfigurar)
                      Los toggles activan o desactivan un campo del cálculo.
                    @else
                      Los campos activos fueron configurados por el Jefe de Zona.
                    @endif
                  </div>
                </div>
                <div style="display:flex;gap:10px;">
                  <button type="submit" name="accion_estado" value="borrador" class="pt-btn-draft">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Guardar borrador
                  </button>
                  @if($user->esJefe())
                  <button type="submit" name="accion_estado" value="confirmado" class="pt-btn-confirm"
                          onclick="return confirm('¿Confirmar la evaluación? Esta acción bloqueará la edición para el equipo.')">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Confirmar evaluación
                  </button>
                  @endif
                </div>
              </div>
              @endif

            </form>
        </div>{{-- /lg:min-w-0 --}}

        <x-barra-lateral-formulario
            clave="potencialidad"
            :zona="$zona"
            :secciones="$indiceAreas"
            :bloqueado="$soloLectura"
            formulario="pt-form"
            :total="$totalActivos"
            :respondidos="$respondidosActivos" />

        </div>{{-- /lg:grid --}}
    </div>{{-- /pt-root --}}

    <script>
        // ── Componente Alpine por sección ─────────────────────────────────────
        // `valoresIniciales` alimenta al componente de criterio (pildoras),
        // que lee y escribe en `valores` -no en `states`, que sigue siendo
        // solo el activo/inactivo del toggle-. Vienen en dos objetos
        // separados porque son dos preguntas distintas por criterio: "¿cuenta
        // para el cálculo?" y "¿qué calificación tiene?".
        function ptSection(campos, activosList, valoresIniciales) {
            const initStates = {};
            campos.forEach(c => { initStates[c] = activosList.includes(c); });
            return {
                states: { ...initStates },
                valores: { ...valoresIniciales },
                activosCount: 0,
                init() {
                    this.activosCount = campos.filter(c => this.states[c]).length;
                },
                updateCount() {
                    this.activosCount = campos.filter(c => this.states[c]).length;
                },
                selectAll(val) {
                    campos.forEach(c => { this.states[c] = val; });
                    this.activosCount = val ? campos.length : 0;
                }
            };
        }
    </script>
</x-app-layout>
