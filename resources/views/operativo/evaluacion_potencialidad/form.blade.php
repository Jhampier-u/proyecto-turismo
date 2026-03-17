<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">Evaluación de Potencialidad Turística</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $zona->nombre }}</p>
            </div>
            <div class="flex gap-2 flex-wrap items-center">
                @if($user->role_id == 2)
                <form method="POST" action="{{ route('operativo.evaluacion_potencialidad.reconfigurar', $zona->id) }}"
                      onsubmit="return confirm('¿Reconfigurar campos? La configuración actual se borrará.');">
                    @csrf
                    <button type="submit"
                            style="font-size:.75rem;font-weight:600;color:#92400e;background:#fffbeb;border:1.5px solid #fde68a;padding:6px 14px;border-radius:8px;cursor:pointer;transition:background .15s;">
                        ⚙️ Reconfigurar campos
                    </button>
                </form>
                @endif
                <a href="{{ route('operativo.dashboard') }}" class="text-sm text-blue-600 hover:underline">← Mis Zonas</a>
            </div>
        </div>
    </x-slot>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap');

        .ev-root { font-family: 'DM Sans', sans-serif; background: #f0f4f8; min-height: 100vh; padding: 1.5rem 0 5rem; }

        /* Barra lateral sticky de progreso */
        .ev-layout { display: grid; grid-template-columns: 1fr 260px; gap: 20px; align-items: start; }
        @media (max-width: 900px) { .ev-layout { grid-template-columns: 1fr; } .ev-sidebar { display: none; } }

        /* Sidebar */
        .ev-sidebar { position: sticky; top: 20px; display: flex; flex-direction: column; gap: 12px; }
        .ev-sidebar-card { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
        .ev-sidebar-title { font-size: .7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10px; }
        .ev-nav-item { display: flex; align-items: center; gap: 8px; padding: 7px 10px; border-radius: 8px; cursor: pointer; transition: background .15s; text-decoration: none; }
        .ev-nav-item:hover { background: #f8fafc; }
        .ev-nav-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .ev-nav-label { font-size: .8rem; font-weight: 500; color: #374151; }

        /* Escala leyenda */
        .ev-scale-item { display: flex; align-items: center; gap: 8px; padding: 5px 0; }
        .ev-scale-badge { font-size: .75rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; font-family: 'DM Mono', monospace; }

        /* FN/FX live indicators */
        .ev-indicator { text-align: center; }
        .ev-indicator-val { font-family: 'DM Mono', monospace; font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .ev-indicator-label { font-size: .72rem; color: #94a3b8; margin-top: 3px; }
        .ev-indicator-bar { height: 6px; border-radius: 3px; background: #e2e8f0; margin-top: 8px; overflow: hidden; }
        .ev-indicator-fill { height: 100%; border-radius: 3px; transition: width .4s; }

        /* Área acordeón */
        .ev-area { background: #fff; border-radius: 16px; border: 1.5px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
        .ev-area-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; cursor: pointer; user-select: none; transition: background .15s; }
        .ev-area-header:hover { opacity: .92; }
        .ev-area-icon { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
        .ev-area-name { font-weight: 700; font-size: .9rem; }
        .ev-area-badge { font-size: .7rem; font-weight: 600; padding: 2px 8px; border-radius: 20px; margin-left: 8px; }
        .ev-area-chevron { width: 18px; height: 18px; transition: transform .2s; color: rgba(255,255,255,.7); }
        .ev-area-chevron.open { transform: rotate(180deg); }
        .ev-area-body { border-top: 1px solid #f1f5f9; padding: 16px 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 12px; }

        /* Colores de áreas */
        .area-c-green  .ev-area-header { background: linear-gradient(135deg,#166534,#15803d); color: #fff; }
        .area-c-green  .ev-area-icon   { background: rgba(255,255,255,.2); }
        .area-c-green  .ev-area-badge  { background: rgba(255,255,255,.2); color: #fff; }
        .area-c-amber  .ev-area-header { background: linear-gradient(135deg,#92400e,#b45309); color: #fff; }
        .area-c-amber  .ev-area-icon   { background: rgba(255,255,255,.2); }
        .area-c-amber  .ev-area-badge  { background: rgba(255,255,255,.2); color: #fff; }
        .area-c-blue   .ev-area-header { background: linear-gradient(135deg,#1e3a8a,#1d4ed8); color: #fff; }
        .area-c-blue   .ev-area-icon   { background: rgba(255,255,255,.2); }
        .area-c-blue   .ev-area-badge  { background: rgba(255,255,255,.2); color: #fff; }
        .area-c-violet .ev-area-header { background: linear-gradient(135deg,#4c1d95,#6d28d9); color: #fff; }
        .area-c-violet .ev-area-icon   { background: rgba(255,255,255,.2); }
        .area-c-violet .ev-area-badge  { background: rgba(255,255,255,.2); color: #fff; }
        .area-c-slate  .ev-area-header { background: linear-gradient(135deg,#1e293b,#334155); color: #fff; }
        .area-c-slate  .ev-area-icon   { background: rgba(255,255,255,.2); }
        .area-c-slate  .ev-area-badge  { background: rgba(255,255,255,.2); color: #fff; }
        .area-c-indigo .ev-area-header { background: linear-gradient(135deg,#312e81,#4338ca); color: #fff; }
        .area-c-indigo .ev-area-icon   { background: rgba(255,255,255,.2); }
        .area-c-indigo .ev-area-badge  { background: rgba(255,255,255,.2); color: #fff; }

        /* Sección de campos */
        .ev-section { border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px; background: #fafbfc; }
        .ev-section-name { font-size: .75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .05em; padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; }

        /* Fila de campo */
        .ev-field-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 7px 8px; border-radius: 8px; transition: background .12s; }
        .ev-field-row:hover { background: #fff; }
        .ev-field-label { font-size: .84rem; color: #374151; flex: 1; min-width: 0; }
        .ev-field-select { min-width: 155px; flex-shrink: 0; }
        .ev-field-select select { width: 100%; padding: 5px 10px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: .8rem; font-family: 'DM Sans',sans-serif; font-weight: 500; background: #fff; cursor: pointer; transition: border-color .15s; outline: none; }
        .ev-field-select select:focus { border-color: #6366f1; }
        .ev-field-select select option[value="0"] { color: #dc2626; }
        .ev-field-select select option[value="1"] { color: #d97706; }
        .ev-field-select select option[value="2"] { color: #16a34a; }

        /* Valor solo lectura */
        .ev-readonly-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 7px; font-size: .8rem; font-weight: 700; font-family: 'DM Mono',monospace; }
        .ev-readonly-0 { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .ev-readonly-1 { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .ev-readonly-2 { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        /* Banner confirmado */
        .ev-confirmed-banner { background: linear-gradient(135deg,#f0fdf4,#dcfce7); border: 1.5px solid #86efac; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }

        /* Footer de acciones */
        .ev-footer { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.05); margin-top: 20px; }
        .ev-btn-draft   { background: #f8fafc; color: #374151; font-weight: 700; font-size: .85rem; padding: 10px 22px; border-radius: 10px; border: 1.5px solid #e2e8f0; cursor: pointer; transition: background .15s; display: flex; align-items: center; gap: 7px; }
        .ev-btn-draft:hover   { background: #f1f5f9; }
        .ev-btn-confirm { background: linear-gradient(135deg,#15803d,#16a34a); color: #fff; font-weight: 700; font-size: .85rem; padding: 10px 22px; border-radius: 10px; border: none; cursor: pointer; transition: opacity .15s,transform .1s; display: flex; align-items: center; gap: 7px; box-shadow: 0 4px 12px rgba(22,163,74,.3); }
        .ev-btn-confirm:hover { opacity: .9; transform: translateY(-1px); }
    </style>

    <div class="ev-root">
        <div style="max-width: 1140px; margin: 0 auto; padding: 0 1.25rem;">

            @if(session('success'))
            <div style="background:#f0fdf4;border:1.5px solid #86efac;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:16px;font-size:.85rem;font-weight:500;">
                ✅ {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div style="background:#fef2f2;border:1.5px solid #fecaca;color:#b91c1c;padding:12px 18px;border-radius:12px;margin-bottom:16px;font-size:.85rem;">
                ⚠️ {{ session('error') }}
            </div>
            @endif

            @php
                $isConfirmado = $evaluacion->exists && $evaluacion->estado === 'confirmado';
                $soloLectura  = ($isConfirmado && $user->role_id == 3) || $user->role_id == 1;

                $areaConfig = [
                    'Recursos Naturales'  => ['emoji'=>'🏔️','color'=>'green', 'secs'=>array_filter($secciones, fn($k)=>str_starts_with($k,'RN'), ARRAY_FILTER_USE_KEY)],
                    'Recursos Culturales' => ['emoji'=>'🏛️','color'=>'amber', 'secs'=>array_filter($secciones, fn($k)=>str_starts_with($k,'RC'), ARRAY_FILTER_USE_KEY)],
                    'Planta Turística'    => ['emoji'=>'🏨','color'=>'blue',  'secs'=>array_filter($secciones, fn($k)=>str_starts_with($k,'PT'), ARRAY_FILTER_USE_KEY)],
                    'Tipologías de Turismo' => ['emoji'=>'🧭','color'=>'violet','secs'=>array_filter($secciones, fn($k)=>$k==='Tipologías de Turismo', ARRAY_FILTER_USE_KEY)],
                    'Infraestructura'     => ['emoji'=>'🔌','color'=>'slate', 'secs'=>array_filter($secciones, fn($k)=>$k==='Infraestructura', ARRAY_FILTER_USE_KEY)],
                    'Factores Exógenos'   => ['emoji'=>'📊','color'=>'indigo','secs'=>array_filter($secciones, fn($k)=>in_array($k,['Afluencia Turística','Marketing Turístico','Superestructura']), ARRAY_FILTER_USE_KEY)],
                ];

                $navColors = ['green'=>'#16a34a','amber'=>'#d97706','blue'=>'#1d4ed8','violet'=>'#6d28d9','slate'=>'#334155','indigo'=>'#4338ca'];

                // Contar campos activos por área para el sidebar
                $camposPorArea = [];
                foreach ($areaConfig as $aName => $aData) {
                    $total = 0;
                    foreach ($aData['secs'] as $secNombre => $camposSec) {
                        $activos = array_filter($camposSec, fn($c) => in_array($c, $camposActivos), ARRAY_FILTER_USE_KEY);
                        $total += count($activos);
                    }
                    $camposPorArea[$aName] = $total;
                }
            @endphp

            @if($isConfirmado)
            <div class="ev-confirmed-banner">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="font-size:24px;">✅</span>
                    <div>
                        <div style="font-weight:700;color:#15803d;font-size:.95rem;">Evaluación confirmada y validada</div>
                        @if($user->role_id == 3)
                        <div style="font-size:.82rem;color:#166534;margin-top:2px;">Esta evaluación fue cerrada por el Jefe de Zona. Solo puedes consultar los valores.</div>
                        @endif
                    </div>
                </div>
                <a href="{{ route('operativo.evaluacion_potencialidad.ponderacion', $zona->id) }}"
                   style="background:#16a34a;color:#fff;font-weight:700;font-size:.85rem;padding:9px 20px;border-radius:10px;text-decoration:none;display:flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(22,163,74,.3);">
                    Ver Resultados →
                </a>
            </div>
            @endif

            <div class="ev-layout">
                {{-- COLUMNA PRINCIPAL --}}
                <div>
                    <form method="POST" action="{{ route('operativo.evaluacion_potencialidad.update', $zona->id) }}" id="ev-form">
                        @csrf

                        <div style="display:flex;flex-direction:column;gap:12px;">
                        @foreach($areaConfig as $areaName => $areaData)
                            @php
                                $color = $areaData['color'];
                                $seccionesVisibles = [];
                                foreach($areaData['secs'] as $secNombre => $camposSec) {
                                    $activos = array_filter($camposSec, fn($c) => in_array($c, $camposActivos), ARRAY_FILTER_USE_KEY);
                                    if (!empty($activos)) $seccionesVisibles[$secNombre] = $activos;
                                }
                                if (empty($seccionesVisibles)) continue;
                                $totalAreaActivos = array_sum(array_map('count', $seccionesVisibles));
                            @endphp

                            <div class="ev-area area-c-{{ $color }}" id="area-{{ Str::slug($areaName) }}" x-data="{ open: true }">
                                <div class="ev-area-header" @click="open = !open">
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div class="ev-area-icon">{{ $areaData['emoji'] }}</div>
                                        <div>
                                            <span class="ev-area-name">{{ $areaName }}</span>
                                            <span class="ev-area-badge">{{ $totalAreaActivos }} campos</span>
                                        </div>
                                    </div>
                                    <svg :class="open?'ev-area-chevron open':'ev-area-chevron'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>

                                <div x-show="open" x-transition class="ev-area-body">
                                @foreach($seccionesVisibles as $secNombre => $camposActs)
                                    <div class="ev-section">
                                        <div class="ev-section-name">
                                            {{ preg_replace('/^(RN|RC|PT) — /', '', $secNombre) }}
                                        </div>
                                        @foreach($camposActs as $campo => $label)
                                        <div class="ev-field-row">
                                            <label class="ev-field-label" for="{{ $campo }}">{{ $label }}</label>
                                            @if($soloLectura)
                                                @php $val = $evaluacion->$campo ?? 0; @endphp
                                                <span class="ev-readonly-badge ev-readonly-{{ $val }}">
                                                    {{ $val == 0 ? '🔴 0' : ($val == 1 ? '🟡 1' : '🟢 2') }}
                                                </span>
                                            @else
                                                <div class="ev-field-select">
                                                    <select name="{{ $campo }}" id="{{ $campo }}"
                                                            onchange="recalcular()" class="ev-select-field">
                                                        <option value="0" {{ old($campo, $evaluacion->$campo ?? 0) == 0 ? 'selected' : '' }}>🔴 0 — Ausencia</option>
                                                        <option value="1" {{ old($campo, $evaluacion->$campo ?? 0) == 1 ? 'selected' : '' }}>🟡 1 — Fragilidad</option>
                                                        <option value="2" {{ old($campo, $evaluacion->$campo ?? 0) == 2 ? 'selected' : '' }}>🟢 2 — Aprovechable</option>
                                                    </select>
                                                </div>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                @endforeach
                                </div>
                            </div>
                        @endforeach
                        </div>

                        @if(!$soloLectura)
                        <div class="ev-footer">
                            <div>
                                <div style="font-weight:700;font-size:.9rem;color:#1e293b;">Guardar evaluación</div>
                                <div style="font-size:.78rem;color:#64748b;margin-top:2px;">
                                    Borrador para continuar después, o confirmar para validar definitivamente.
                                </div>
                            </div>
                            <div style="display:flex;gap:10px;">
                                <button type="submit" name="accion_estado" value="borrador" class="ev-btn-draft">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                    </svg>
                                    Guardar borrador
                                </button>
                                @if($user->role_id == 2)
                                <button type="submit" name="accion_estado" value="confirmado" class="ev-btn-confirm"
                                        onclick="return confirm('¿Confirmar y validar la evaluación? Esta acción bloqueará la edición para el equipo operativo.')">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Confirmar evaluación
                                </button>
                                @endif
                            </div>
                        </div>
                        @endif
                    </form>
                </div>

                {{-- SIDEBAR --}}
                <div class="ev-sidebar">
                    {{-- Escala de valores --}}
                    <div class="ev-sidebar-card">
                        <div class="ev-sidebar-title">Escala de valores</div>
                        <div class="ev-scale-item">
                            <span class="ev-scale-badge" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">0</span>
                            <span style="font-size:.82rem;color:#374151;font-weight:500;">Ausencia</span>
                            <span style="font-size:.75rem;color:#94a3b8;margin-left:auto;">No existe</span>
                        </div>
                        <div class="ev-scale-item">
                            <span class="ev-scale-badge" style="background:#fffbeb;color:#d97706;border:1px solid #fde68a;">1</span>
                            <span style="font-size:.82rem;color:#374151;font-weight:500;">Fragilidad</span>
                            <span style="font-size:.75rem;color:#94a3b8;margin-left:auto;">Existe, débil</span>
                        </div>
                        <div class="ev-scale-item">
                            <span class="ev-scale-badge" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;">2</span>
                            <span style="font-size:.82rem;color:#374151;font-weight:500;">Aprovechable</span>
                            <span style="font-size:.75rem;color:#94a3b8;margin-left:auto;">Consolidado</span>
                        </div>
                    </div>

                    {{-- Áreas de navegación --}}
                    <div class="ev-sidebar-card">
                        <div class="ev-sidebar-title">Secciones</div>
                        @foreach($areaConfig as $aName => $aData)
                            @if(($camposPorArea[$aName] ?? 0) > 0)
                            <a href="#area-{{ Str::slug($aName) }}" class="ev-nav-item" style="text-decoration:none;">
                                <span style="font-size:15px;">{{ $aData['emoji'] }}</span>
                                <div>
                                    <div class="ev-nav-label">{{ $aName }}</div>
                                    <div style="font-size:.7rem;color:#94a3b8;">{{ $camposPorArea[$aName] }} campos</div>
                                </div>
                            </a>
                            @endif
                        @endforeach
                    </div>

                    {{-- Estado actual --}}
                    <div class="ev-sidebar-card">
                        <div class="ev-sidebar-title">Estado</div>
                        @if($isConfirmado)
                            <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#f0fdf4;border-radius:9px;border:1px solid #86efac;">
                                <span style="font-size:16px;">✅</span>
                                <span style="font-size:.82rem;font-weight:600;color:#15803d;">Confirmado</span>
                            </div>
                        @elseif($evaluacion->exists)
                            <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#fffbeb;border-radius:9px;border:1px solid #fde68a;">
                                <span style="font-size:16px;">✏️</span>
                                <span style="font-size:.82rem;font-weight:600;color:#92400e;">Borrador</span>
                            </div>
                        @else
                            <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#f8fafc;border-radius:9px;border:1px solid #e2e8f0;">
                                <span style="font-size:16px;">🆕</span>
                                <span style="font-size:.82rem;font-weight:600;color:#64748b;">Nueva</span>
                            </div>
                        @endif

                        @if($evaluacion->updated_at)
                        <div style="font-size:.72rem;color:#94a3b8;margin-top:8px;padding:0 2px;">
                            Última edición:<br>
                            <strong style="color:#64748b;">{{ $evaluacion->updated_at->format('d/m/Y H:i') }}</strong>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Smooth scroll para navegación sidebar
        document.querySelectorAll('.ev-nav-item[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const target = document.querySelector(a.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    </script>
</x-app-layout>
