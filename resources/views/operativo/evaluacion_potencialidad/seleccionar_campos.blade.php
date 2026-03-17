<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Configurar campos aplicables
                </h2>
                <p class="text-sm text-gray-500 mt-1">Matriz de Potencialidad · <strong>{{ $zona->nombre }}</strong></p>
            </div>
            <a href="{{ route('operativo.dashboard') }}" class="text-sm text-blue-600 hover:underline">← Mis Zonas</a>
        </div>
    </x-slot>

    {{-- Estilos propios de esta vista --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap');

        .sc-root { font-family: 'DM Sans', sans-serif; background: #f0f4f8; min-height: 100vh; padding: 2rem 0 4rem; }

        /* Área acordeón */
        .sc-area { background: #fff; border-radius: 16px; border: 1.5px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .sc-area-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; cursor: pointer; user-select: none; transition: background .15s; }
        .sc-area-header:hover { background: #f8fafc; }
        .sc-area-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .sc-area-title { font-weight: 700; font-size: .9rem; color: #1e293b; }
        .sc-area-badge { font-size: .7rem; font-weight: 600; padding: 2px 8px; border-radius: 20px; }
        .sc-area-chevron { width: 18px; height: 18px; color: #94a3b8; transition: transform .2s; }
        .sc-area-chevron.open { transform: rotate(180deg); }
        .sc-area-body { border-top: 1.5px solid #f1f5f9; padding: 16px 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }

        /* Sección de campos */
        .sc-section { border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px; background: #fafbfc; }
        .sc-section-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
        .sc-section-name { font-size: .78rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .04em; }
        .sc-section-actions { display: flex; gap: 8px; }
        .sc-btn-all  { font-size: .7rem; font-weight: 600; color: #16a34a; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 6px; cursor: pointer; transition: background .15s; }
        .sc-btn-none { font-size: .7rem; font-weight: 600; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; padding: 2px 8px; border-radius: 6px; cursor: pointer; transition: background .15s; }
        .sc-btn-all:hover  { background: #dcfce7; }
        .sc-btn-none:hover { background: #fee2e2; }

        /* Checkboxes */
        .sc-check-label { display: flex; align-items: center; gap: 8px; padding: 5px 6px; border-radius: 7px; cursor: pointer; transition: background .12s; }
        .sc-check-label:hover { background: #fff; }
        .sc-check-label input[type=checkbox] { width: 15px; height: 15px; flex-shrink: 0; cursor: pointer; accent-color: #6366f1; }
        .sc-check-label span { font-size: .82rem; color: #374151; }
        .sc-check-label input:disabled { opacity: .5; cursor: not-allowed; }

        /* Colores por área */
        .area-green  .sc-area-header { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); }
        .area-green  .sc-area-icon   { background: #dcfce7; }
        .area-green  .sc-area-badge  { background: #dcfce7; color: #15803d; }
        .area-amber  .sc-area-header { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); }
        .area-amber  .sc-area-icon   { background: #fef3c7; }
        .area-amber  .sc-area-badge  { background: #fef3c7; color: #b45309; }
        .area-blue   .sc-area-header { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); }
        .area-blue   .sc-area-icon   { background: #dbeafe; }
        .area-blue   .sc-area-badge  { background: #dbeafe; color: #1d4ed8; }
        .area-violet .sc-area-header { background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); }
        .area-violet .sc-area-icon   { background: #ede9fe; }
        .area-violet .sc-area-badge  { background: #ede9fe; color: #6d28d9; }
        .area-slate  .sc-area-header { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); }
        .area-slate  .sc-area-icon   { background: #f1f5f9; }
        .area-slate  .sc-area-badge  { background: #f1f5f9; color: #475569; }
        .area-indigo .sc-area-header { background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); }
        .area-indigo .sc-area-icon   { background: #e0e7ff; }
        .area-indigo .sc-area-badge  { background: #e0e7ff; color: #4338ca; }

        /* Barra de progreso */
        .sc-progress-bar { height: 4px; background: #e2e8f0; border-radius: 2px; overflow: hidden; }
        .sc-progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 2px; transition: width .3s; }

        /* Banner info */
        .sc-info-banner { background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 50%, #ede9fe 100%); border: 1.5px solid #c7d2fe; border-radius: 16px; padding: 20px 24px; display: flex; gap: 16px; align-items: flex-start; }
        .sc-info-icon { width: 40px; height: 40px; background: #6366f1; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .sc-info-title { font-weight: 700; color: #312e81; font-size: .95rem; margin-bottom: 4px; }
        .sc-info-text { font-size: .83rem; color: #4338ca; line-height: 1.5; }
        .sc-info-example { font-size: .78rem; color: #6366f1; margin-top: 6px; padding: 6px 10px; background: rgba(255,255,255,.6); border-radius: 8px; border-left: 3px solid #6366f1; }

        /* Contador global */
        .sc-counter { font-family: 'DM Mono', monospace; }

        /* Botón submit */
        .sc-submit-btn { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #fff; font-weight: 700; font-size: .9rem; padding: 11px 32px; border-radius: 10px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: opacity .15s, transform .1s; box-shadow: 0 4px 14px rgba(99,102,241,.35); }
        .sc-submit-btn:hover { opacity: .92; transform: translateY(-1px); }
        .sc-footer { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
    </style>

    <div class="sc-root">
        <div style="max-width: 1024px; margin: 0 auto; padding: 0 1.5rem;">

            @if(session('error'))
            <div style="background:#fef2f2;border:1.5px solid #fecaca;color:#b91c1c;padding:14px 18px;border-radius:12px;margin-bottom:16px;font-size:.85rem;">
                ⚠️ {{ session('error') }}
            </div>
            @endif

            {{-- Banner informativo --}}
            <div class="sc-info-banner" style="margin-bottom: 24px;">
                <div class="sc-info-icon">🗺️</div>
                <div>
                    <div class="sc-info-title">Personaliza la evaluación según tu zona</div>
                    <div class="sc-info-text">
                        Selecciona únicamente los <strong>recursos y factores que aplican geográficamente</strong> a tu zona.
                        Los campos desactivados se excluirán del cálculo final para no penalizar la calificación.
                    </div>
                    <div class="sc-info-example">
                        💡 Ejemplo: si tu zona es de interior, puedes desmarcar toda la sección <em>"Zonas de Litoral"</em> sin afectar el resultado.
                    </div>
                </div>
            </div>

            @if($user->role_id == 3)
            <div style="background:#fffbeb;border:1.5px solid #fde68a;color:#92400e;padding:12px 18px;border-radius:12px;margin-bottom:20px;font-size:.84rem;display:flex;align-items:center;gap:10px;">
                <span style="font-size:18px;">🔒</span>
                <span>Solo el <strong>Jefe de Zona</strong> puede guardar esta configuración. Puedes revisar los campos pero no modificarlos.</span>
            </div>
            @endif

            @php
                $areaConfig = [
                    'Recursos Naturales (RN)'  => ['emoji'=>'🏔️','color'=>'green', 'secs'=>array_filter($secciones, fn($k)=>str_starts_with($k,'RN'), ARRAY_FILTER_USE_KEY)],
                    'Recursos Culturales (RC)' => ['emoji'=>'🏛️','color'=>'amber', 'secs'=>array_filter($secciones, fn($k)=>str_starts_with($k,'RC'), ARRAY_FILTER_USE_KEY)],
                    'Planta Turística (PT)'    => ['emoji'=>'🏨','color'=>'blue',  'secs'=>array_filter($secciones, fn($k)=>str_starts_with($k,'PT'), ARRAY_FILTER_USE_KEY)],
                    'Tipologías de Turismo'    => ['emoji'=>'🧭','color'=>'violet','secs'=>array_filter($secciones, fn($k)=>$k==='Tipologías de Turismo', ARRAY_FILTER_USE_KEY)],
                    'Infraestructura'          => ['emoji'=>'🔌','color'=>'slate', 'secs'=>array_filter($secciones, fn($k)=>$k==='Infraestructura', ARRAY_FILTER_USE_KEY)],
                    'Factores Exógenos'        => ['emoji'=>'📊','color'=>'indigo','secs'=>array_filter($secciones, fn($k)=>in_array($k,['Afluencia Turística','Marketing Turístico','Superestructura']), ARRAY_FILTER_USE_KEY)],
                ];
                $totalCampos = array_sum(array_map(fn($a) => array_sum(array_map('count', $a['secs'])), $areaConfig));
            @endphp

            {{-- Contador global --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <span style="font-size:.82rem;color:#64748b;">
                    <span class="sc-counter" id="contador-activos">{{ $totalCampos }}</span>
                    <span> / {{ $totalCampos }} campos activos</span>
                </span>
                <div style="display:flex;gap:8px;">
                    @if($user->role_id != 3)
                    <button type="button" onclick="toggleTodos(true)"
                            style="font-size:.75rem;font-weight:600;color:#16a34a;background:#f0fdf4;border:1px solid #bbf7d0;padding:5px 12px;border-radius:8px;cursor:pointer;">
                        ✓ Seleccionar todo
                    </button>
                    <button type="button" onclick="toggleTodos(false)"
                            style="font-size:.75rem;font-weight:600;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;padding:5px 12px;border-radius:8px;cursor:pointer;">
                        ✕ Deseleccionar todo
                    </button>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('operativo.evaluacion_potencialidad.campos', $zona->id) }}" id="campos-form">
                @csrf

                <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach($areaConfig as $areaName => $areaData)
                    @php
                        $color = $areaData['color'];
                        $emoji = $areaData['emoji'];
                        $totalArea = array_sum(array_map('count', $areaData['secs']));
                    @endphp
                    <div class="sc-area area-{{ $color }}" x-data="{ open: true }">
                        <div class="sc-area-header" @click="open = !open">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div class="sc-area-icon">{{ $emoji }}</div>
                                <div>
                                    <div class="sc-area-title">{{ $areaName }}</div>
                                    <div style="margin-top:2px;">
                                        <span class="sc-area-badge">{{ $totalArea }} campos</span>
                                    </div>
                                </div>
                            </div>
                            <svg :class="open?'sc-area-chevron open':'sc-area-chevron'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>

                        <div x-show="open" x-transition class="sc-area-body">
                        @foreach($areaData['secs'] as $seccion => $campos)
                            <div class="sc-section">
                                <div class="sc-section-head">
                                    <span class="sc-section-name">{{ str_replace(['RN — ','RC — ','PT — '], '', $seccion) }}</span>
                                    @if($user->role_id != 3)
                                    <div class="sc-section-actions">
                                        <button type="button" class="sc-btn-all"
                                                onclick="toggleSeccion(this, true)">Todos</button>
                                        <button type="button" class="sc-btn-none"
                                                onclick="toggleSeccion(this, false)">Ninguno</button>
                                    </div>
                                    @endif
                                </div>
                                <div>
                                @foreach($campos as $campo => $label)
                                    <label class="sc-check-label">
                                        <input type="checkbox" name="campos[]" value="{{ $campo }}"
                                               onchange="actualizarContador()"
                                               {{ $user->role_id == 3 ? 'disabled' : '' }}
                                               checked>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                                </div>
                            </div>
                        @endforeach
                        </div>
                    </div>
                @endforeach
                </div>

                @if($user->role_id != 3)
                <div class="sc-footer" style="margin-top:24px;">
                    <div>
                        <div style="font-weight:700;font-size:.9rem;color:#1e293b;">¿Todo listo?</div>
                        <div style="font-size:.8rem;color:#64748b;margin-top:2px;">
                            Tendrás <strong class="sc-counter" id="contador-footer">{{ $totalCampos }}</strong> campos activos en la evaluación.
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <a href="{{ route('operativo.dashboard') }}"
                           style="font-size:.85rem;color:#64748b;text-decoration:none;padding:10px 18px;border:1.5px solid #e2e8f0;border-radius:10px;font-weight:600;transition:background .15s;"
                           onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            Cancelar
                        </a>
                        <button type="submit" class="sc-submit-btn">
                            Guardar y continuar
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @endif
            </form>
        </div>
    </div>

    <script>
        function toggleSeccion(btn, estado) {
            const seccion = btn.closest('.sc-section');
            seccion.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = estado);
            actualizarContador();
        }
        function toggleTodos(estado) {
            document.querySelectorAll('#campos-form input[type=checkbox]').forEach(c => c.checked = estado);
            actualizarContador();
        }
        function actualizarContador() {
            const total = document.querySelectorAll('#campos-form input[type=checkbox]:checked').length;
            document.querySelectorAll('.sc-counter').forEach(el => el.textContent = total);
        }
    </script>
</x-app-layout>
