<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reporte Final de Vocación Turística — {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

                <h3 class="text-center text-4xl font-extrabold mb-8 text-gray-800">Dictamen Final</h3>

                <div class="flex flex-col items-center mb-10">
                    <p class="text-base font-bold text-gray-500 uppercase tracking-widest mb-6">
                        Gráfica de Vocación Turística del Territorio
                    </p>

                    @php
                        $vttVal  = (float) $vtt->vtt;
                        $cx = 280; $cy = 260;
                        $Ro = 220; $Ri = 130;

                        // valor 0→180°(izq), 3→0°(der)
                        $deg     = 180 - ($vttVal / 3) * 180;
                        $rad     = deg2rad($deg);

                        // Punta aguja
                        $tipX = $cx + ($Ro - 18) * cos($rad);
                        $tipY = $cy - ($Ro - 18) * sin($rad);

                        // Base aguja (perpendicular, ancho ±7px)
                        $pw   = 7;
                        $perp = $rad + M_PI / 2;
                        $lx   = $cx + $pw * cos($perp); $ly = $cy - $pw * sin($perp);
                        $rx   = $cx - $pw * cos($perp); $ry = $cy + $pw * sin($perp);

                        // Cola
                        $tailX = $cx - 22 * cos($rad);
                        $tailY = $cy + 22 * sin($rad);
                    @endphp

                    <svg width="560" height="310" viewBox="0 0 560 310" xmlns="http://www.w3.org/2000/svg">

                        {{-- SECTOR ROJO  0→1  outer sweep=1, inner sweep=0 --}}
                        <path d="M 60,260 A 220,220 0 0,1 170,69.47 L 215,147.42 A 130,130 0 0,0 150,260 Z"
                              fill="#ef4444" stroke="white" stroke-width="2"/>

                        {{-- SECTOR AMARILLO  1→2 --}}
                        <path d="M 170,69.47 A 220,220 0 0,1 390,69.47 L 345,147.42 A 130,130 0 0,0 215,147.42 Z"
                              fill="#facc15" stroke="white" stroke-width="2"/>

                        {{-- SECTOR VERDE  2→3 --}}
                        <path d="M 390,69.47 A 220,220 0 0,1 500,260 L 410,260 A 130,130 0 0,0 345,147.42 Z"
                              fill="#22c55e" stroke="white" stroke-width="2"/>

                        {{-- Etiquetas en cada sector --}}
                        <text transform="translate(128,172) rotate(-60)" text-anchor="middle" font-size="13" font-weight="bold" fill="#7f1d1d">
                            <tspan x="0" dy="-16">Baja</tspan><tspan x="0" dy="17">Vocación</tspan><tspan x="0" dy="17">Turística</tspan>
                        </text>
                        <text transform="translate(280,84)" text-anchor="middle" font-size="13" font-weight="bold" fill="#92400e">
                            <tspan x="0" dy="0">Mediana</tspan><tspan x="0" dy="17">Vocación</tspan><tspan x="0" dy="17">Turística</tspan>
                        </text>
                        <text transform="translate(432,172) rotate(60)" text-anchor="middle" font-size="13" font-weight="bold" fill="#14532d">
                            <tspan x="0" dy="-16">Alta</tspan><tspan x="0" dy="17">Vocación</tspan><tspan x="0" dy="17">Turística</tspan>
                        </text>

                        {{-- Ticks y números 0, 1, 2, 3 --}}
                        <line x1="55"  y1="260" x2="36"  y2="260" stroke="#374151" stroke-width="2.5"/>
                        <text x="20"  y="260" text-anchor="middle" dominant-baseline="middle" font-size="15" font-weight="bold" fill="#374151">0</text>
                        <line x1="167" y1="64"  x2="158" y2="49"  stroke="#374151" stroke-width="2.5"/>
                        <text x="150" y="34"  text-anchor="middle" dominant-baseline="middle" font-size="15" font-weight="bold" fill="#374151">1</text>
                        <line x1="393" y1="64"  x2="402" y2="49"  stroke="#374151" stroke-width="2.5"/>
                        <text x="410" y="34"  text-anchor="middle" dominant-baseline="middle" font-size="15" font-weight="bold" fill="#374151">2</text>
                        <line x1="505" y1="260" x2="524" y2="260" stroke="#374151" stroke-width="2.5"/>
                        <text x="540" y="260" text-anchor="middle" dominant-baseline="middle" font-size="15" font-weight="bold" fill="#374151">3</text>

                        {{-- Aguja --}}
                        <polygon points="{{ round($tipX,2) }},{{ round($tipY,2) }} {{ round($lx,2) }},{{ round($ly,2) }} {{ round($tailX,2) }},{{ round($tailY,2) }} {{ round($rx,2) }},{{ round($ry,2) }}"
                                 fill="#0f172a"/>

                        {{-- Pivote --}}
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="20" fill="#0f172a"/>
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="11" fill="white"/>

                        {{-- Valor --}}
                        <text x="{{ $cx }}" y="{{ $cy + 28 }}" text-anchor="middle" font-size="19" font-weight="bold" fill="#0f172a">
                            {{ number_format($vtt->vtt, 4) }}
                        </text>

                    </svg>

                    <div class="flex gap-8 mt-2">
                        <span class="flex items-center gap-2 text-sm font-semibold text-red-600">
                            <span class="inline-block w-4 h-4 rounded-full bg-red-500"></span> Baja Vocación (0 – 1)
                        </span>
                        <span class="flex items-center gap-2 text-sm font-semibold text-yellow-600">
                            <span class="inline-block w-4 h-4 rounded-full bg-yellow-400"></span> Mediana Vocación (1.1 – 2)
                        </span>
                        <span class="flex items-center gap-2 text-sm font-semibold text-green-600">
                            <span class="inline-block w-4 h-4 rounded-full bg-green-500"></span> Alta Vocación (2.1 – 3)
                        </span>
                    </div>
                </div>

                {{-- RESULTADO --}}
                <div class="w-full mx-auto p-8 rounded-xl border-4 text-center mb-10
                    {{ $vtt->vocacion_texto == 'Alta Vocación Turística'  ? 'border-green-500 bg-green-50'  :
                      ($vtt->vocacion_texto == 'Baja Vocación Turística'  ? 'border-red-500 bg-red-50'      :
                                                                            'border-yellow-500 bg-yellow-50') }}">
                    <p class="text-2xl text-gray-500 mb-1">Valor de Vocación Turística (VTT)</p>
                    <p class="text-7xl font-black mb-3
                        {{ $vtt->vocacion_texto == 'Alta Vocación Turística' ? 'text-green-600' :
                          ($vtt->vocacion_texto == 'Baja Vocación Turística' ? 'text-red-600'   : 'text-yellow-500') }}">
                        {{ number_format($vtt->vtt, 6) }}
                    </p>
                    <p class="text-3xl font-extrabold uppercase tracking-wide
                        {{ $vtt->vocacion_texto == 'Alta Vocación Turística' ? 'text-green-700' :
                          ($vtt->vocacion_texto == 'Baja Vocación Turística' ? 'text-red-700'   : 'text-yellow-700') }}">
                        {{ $vtt->vocacion_texto }}
                    </p>
                    <p class="text-sm text-gray-400 mt-4">
                        Reporte finalizado por {{ $vtt->user->name ?? 'Sistema' }} el {{ $vtt->created_at->format('d/m/Y') }}
                    </p>
                </div>

                {{-- TABLA RANGOS --}}
                <div class="overflow-x-auto border border-gray-200 rounded-lg mb-10 max-w-2xl mx-auto">
                    <table class="min-w-full text-sm text-center">
                        <thead class="bg-gray-100 font-bold uppercase text-gray-600">
                            <tr>
                                <th class="border border-gray-200 p-3">Resultado Obtenido</th>
                                <th class="border border-gray-200 p-3">Zona</th>
                                <th class="border border-gray-200 p-3 text-left">Explicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-red-50">
                                <td class="border border-gray-200 p-3 font-bold">De 0 a 1</td>
                                <td class="border border-gray-200 p-3 font-bold text-red-600">Baja Vocación Turística</td>
                                <td class="border border-gray-200 p-3 text-left text-gray-600">El territorio no presenta las condiciones necesarias para desarrollar actividades turísticas.</td>
                            </tr>
                            <tr class="bg-yellow-50">
                                <td class="border border-gray-200 p-3 font-bold">De 1.1 a 2</td>
                                <td class="border border-gray-200 p-3 font-bold text-yellow-600">Mediana Vocación Turística</td>
                                <td class="border border-gray-200 p-3 text-left text-gray-600">Cuenta con limitaciones para el desarrollo turístico, pero pueden trabajarse de manera conjunta.</td>
                            </tr>
                            <tr class="bg-green-50">
                                <td class="border border-gray-200 p-3 font-bold">De 2.1 a 3</td>
                                <td class="border border-gray-200 p-3 font-bold text-green-600">Alta Vocación Turística</td>
                                <td class="border border-gray-200 p-3 text-left text-gray-600">Presenta las condiciones necesarias para desarrollar actividades turísticas de forma eficiente.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- TABLA FINAL --}}
                <div class="overflow-x-auto border border-gray-200 rounded-lg mb-10 max-w-2xl mx-auto">
                    <table class="min-w-full text-sm text-center">
                        <thead class="bg-gray-100 font-bold uppercase text-gray-600">
                            <tr>
                                <th class="border border-gray-200 p-3">Lugar</th>
                                <th class="border border-gray-200 p-3">Factores Intrínsecos</th>
                                <th class="border border-gray-200 p-3">Factores Extrínsecos</th>
                                <th class="border border-gray-200 p-3">Vocación Turística</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-200 p-3 font-bold">{{ $zona->nombre }}</td>
                                <td class="border border-gray-200 p-3 font-bold text-blue-600">{{ number_format($vtt->fit, 6) }}</td>
                                <td class="border border-gray-200 p-3 font-bold text-green-600">{{ number_format($vtt->fet, 6) }}</td>
                                <td class="border border-gray-200 p-3 font-black text-lg
                                    {{ $vtt->vocacion_texto == 'Alta Vocación Turística' ? 'text-green-600' :
                                      ($vtt->vocacion_texto == 'Baja Vocación Turística' ? 'text-red-600'   : 'text-yellow-500') }}">
                                    {{ number_format($vtt->vtt, 6) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- DETALLE --}}
                <h4 class="text-xl font-bold text-gray-700 mb-6">Detalle de Factores</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                    <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                        <h5 class="text-lg font-bold text-blue-800 mb-2">Factor Intrínseco (FIT)</h5>
                        <p class="text-5xl font-extrabold text-blue-600">{{ number_format($vtt->fit, 6) }}</p>
                        <p class="text-sm text-gray-600 mt-3">Promedio ponderado de Oferta, Infraestructura y Facilidades Turísticas.</p>
                        <a href="{{ route('operativo.evaluacion_fit.ponderacion', $zona->id) }}" class="mt-3 inline-block text-blue-600 hover:underline text-sm font-semibold">Ver Tabla FIT →</a>
                    </div>
                    <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                        <h5 class="text-lg font-bold text-green-800 mb-2">Factor Extrínseco (FET)</h5>
                        <p class="text-5xl font-extrabold text-green-600">{{ number_format($vtt->fet, 6) }}</p>
                        <p class="text-sm text-gray-600 mt-3">Promedio ponderado de Demanda, Superestructura e Imagen del Sitio.</p>
                        <a href="{{ route('operativo.evaluacion_fet.ponderacion', $zona->id) }}" class="mt-3 inline-block text-green-600 hover:underline text-sm font-semibold">Ver Tabla FET →</a>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    @php $user = Auth::user(); @endphp
                    @if($user->role_id === 1)
                        <a href="{{ route('admin.zonas.index') }}" class="inline-block px-5 py-2 bg-red-600 text-white font-bold text-lg rounded-lg hover:bg-red-700 shadow-md">
                            Volver a Gestión de Zonas
                        </a>
                    @else
                        <a href="{{ route('operativo.dashboard') }}" class="inline-block px-5 py-2 bg-blue-600 text-white font-bold text-lg rounded-lg hover:bg-blue-700 shadow-md">
                            Volver a Mis Zonas
                        </a>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
