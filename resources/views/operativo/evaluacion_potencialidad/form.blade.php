<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">Evaluación de Potencialidad Turística</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $zona->nombre }}</p>
            </div>
            <div class="flex gap-2 items-center flex-wrap">
                @if($user->role_id == 2)
                <form method="POST"
                      action="{{ route('operativo.evaluacion_potencialidad.reconfigurar', $zona->id) }}"
                      onsubmit="return confirm('¿Activar todos los campos? Se restaurará la selección completa.');">
                    @csrf
                    <button type="submit"
                            style="font-size:.75rem;font-weight:600;color:#92400e;background:#fffbeb;border:1.5px solid #fde68a;padding:6px 14px;border-radius:8px;cursor:pointer;">
                        ↺ Activar todos los campos
                    </button>
                </form>
                @endif
                <a href="{{ route('operativo.dashboard') }}" class="text-sm text-blue-600 hover:underline">← Mis Zonas</a>
            </div>
        </div>
    </x-slot>

    @php
        $isConfirmado    = $evaluacion->exists && $evaluacion->estado === 'confirmado';
        $soloLectura     = ($user->role_id == 1) || ($isConfirmado && $user->role_id == 3);
        $puedeConfigurar = ($user->role_id == 2) && !$soloLectura;
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

    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

        .pt-root { font-family:'DM Sans',sans-serif; background:#f0f4f8; min-height:100vh; padding:1.5rem 0 5rem; }

        /* ── Layout ────────────────────────────────────────────────────────── */
        .pt-layout { display:grid; grid-template-columns:1fr 252px; gap:18px; align-items:start; }
        @media(max-width:900px){ .pt-layout{grid-template-columns:1fr} .pt-sidebar{display:none} }

        /* ── Sidebar ───────────────────────────────────────────────────────── */
        .pt-sidebar { position:sticky; top:20px; display:flex; flex-direction:column; gap:12px; }
        .pt-card { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:16px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
        .pt-card-title { font-size:.68rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.07em; margin-bottom:10px; }

        /* ── Scale legend ──────────────────────────────────────────────────── */
        .pt-scale-row { display:flex; align-items:center; gap:8px; padding:5px 0; }
        .pt-scale-badge { font-size:.72rem; font-weight:700; padding:2px 9px; border-radius:6px; min-width:28px; text-align:center; }

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
        .pt-field-row { display:flex; align-items:center; gap:10px; padding:6px 8px; border-radius:8px; transition:background .12s; min-height:38px; }
        .pt-field-row:hover { background:#fff; }
        .pt-field-row.pt-inactive { opacity:.55; }
        .pt-field-label { font-size:.83rem; color:#374151; flex:1; min-width:0; transition:color .15s; }
        .pt-field-label.faded { color:#9ca3af; text-decoration:line-through; }

        /* Toggle switch */
        .pt-toggle { position:relative; flex-shrink:0; width:36px; height:20px; cursor:pointer; }
        .pt-toggle input { opacity:0; width:0; height:0; position:absolute; }
        .pt-toggle-track { display:block; width:36px; height:20px; border-radius:10px; transition:background .2s; position:relative; }
        .pt-toggle-thumb { position:absolute; top:3px; left:3px; width:14px; height:14px; background:#fff; border-radius:50%; box-shadow:0 1px 3px rgba(0,0,0,.2); transition:transform .2s; }

        /* Dot indicator (non-Jefe roles) */
        .pt-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
        .pt-dot-on  { background:#22c55e; box-shadow:0 0 0 2px #dcfce7; }
        .pt-dot-off { background:#d1d5db; }

        /* Rating select */
        .pt-select-wrap { flex-shrink:0; min-width:165px; }
        .pt-select { width:100%; padding:5px 10px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:.79rem; font-family:'DM Sans',sans-serif; font-weight:500; background:#fff; cursor:pointer; outline:none; transition:border-color .15s,opacity .15s,background .15s; }
        .pt-select:focus { border-color:#6366f1; }
        .pt-select:disabled { opacity:.45; background:#f1f5f9; cursor:not-allowed; border-color:#e2e8f0; }

        /* Readonly badge */
        .pt-ro-badge { flex-shrink:0; display:inline-flex; align-items:center; padding:4px 12px; border-radius:8px; font-size:.78rem; font-weight:700; }
        .pt-ro-0 { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .pt-ro-1 { background:#fffbeb; color:#d97706; border:1px solid #fde68a; }
        .pt-ro-2 { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }

        /* Banner confirmado */
        .pt-banner-ok { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1.5px solid #86efac; border-radius:13px; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
        .pt-banner-warn { background:#fffbeb; border:1.5px solid #fde68a; border-radius:13px; padding:12px 16px; margin-bottom:18px; font-size:.83rem; color:#92400e; }

        /* Footer */
        .pt-footer { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:16px 22px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; box-shadow:0 1px 3px rgba(0,0,0,.05); margin-top:16px; }
        .pt-btn-draft   { background:#f8fafc; color:#374151; font-weight:700; font-size:.84rem; padding:10px 20px; border-radius:10px; border:1.5px solid #e2e8f0; cursor:pointer; display:flex; align-items:center; gap:7px; }
        .pt-btn-confirm { background:linear-gradient(135deg,#15803d,#16a34a); color:#fff; font-weight:700; font-size:.84rem; padding:10px 20px; border-radius:10px; border:none; cursor:pointer; display:flex; align-items:center; gap:7px; box-shadow:0 4px 12px rgba(22,163,74,.3); }
        .pt-btn-draft:hover   { background:#f1f5f9; }
        .pt-btn-confirm:hover { opacity:.9; }

        /* Nav items in sidebar */
        .pt-nav-item { display:flex; align-items:center; gap:8px; padding:6px 8px; border-radius:8px; cursor:pointer; text-decoration:none; transition:background .12s; }
        .pt-nav-item:hover { background:#f8fafc; }
        .pt-nav-label { font-size:.79rem; font-weight:500; color:#374151; }
        .pt-nav-sub { font-size:.68rem; color:#94a3b8; }
    </style>

    <div class="pt-root">
      <div style="max-width:1160px;margin:0 auto;padding:0 1.25rem;">

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
                    @if($user->role_id == 3)
                    <div style="font-size:.8rem;color:#166534;margin-top:2px;">Esta evaluación fue cerrada por el Jefe de Zona. Solo puedes consultar los valores.</div>
                    @endif
                </div>
            </div>
            <a href="{{ route('operativo.evaluacion_potencialidad.ponderacion', $zona->id) }}"
               style="background:#16a34a;color:#fff;font-weight:700;font-size:.82rem;padding:9px 18px;border-radius:9px;text-decoration:none;display:flex;align-items:center;gap:5px;box-shadow:0 3px 10px rgba(22,163,74,.3);">
                Ver Resultados →
            </a>
        </div>
        @elseif($evaluacion->exists)
        <div class="pt-banner-warn">
            ✏️ <strong>Modo Borrador</strong> — Los datos ingresados son preliminares. El Jefe de Zona debe validar para generar el resultado oficial.
        </div>
        @endif

        @if($user->role_id == 3 && !$isConfirmado)
        <div style="background:#eff6ff;border:1.5px solid #bfdbfe;color:#1e40af;padding:11px 16px;border-radius:11px;margin-bottom:14px;font-size:.82rem;display:flex;align-items:center;gap:8px;">
            🔒 Solo el <strong>Jefe de Zona</strong> puede activar o desactivar campos. Tú puedes calificar los campos activos.
        </div>
        @endif

        <div class="pt-layout">

          {{-- ══════════════ COLUMNA PRINCIPAL ══════════════ --}}
          <div>
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
                      @endphp

                      {{-- Sección ──────────────────────────────────────── --}}
                      <div class="pt-section"
                           x-data="ptSection({{ $camposJson }}, {{ $activosJson }})"
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
                          @php $val = $evaluacion->$campo ?? 0; @endphp

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

                            {{-- Etiqueta ──────────────────────────────── --}}
                            <span class="pt-field-label"
                                  :class="{ 'faded': !states['{{ $campo }}'] }">
                              {{ $label }}
                            </span>

                            {{-- Calificación: selector o badge ────────── --}}
                            @if($soloLectura)
                              {{-- Admin / Evaluación confirmada para equipo --}}
                              <span class="pt-ro-badge pt-ro-{{ $val }}">
                                {{ $val == 0 ? '🔴 0' : ($val == 1 ? '🟡 1' : '🟢 2') }}
                              </span>

                            @elseif($puedeConfigurar)
                              {{-- Jefe: select habilitado cuando el toggle está ON --}}
                              <div class="pt-select-wrap">
                                <select name="{{ $campo }}"
                                        class="pt-select"
                                        :disabled="!states['{{ $campo }}']">
                                  <option value="0" {{ $val == 0 ? 'selected' : '' }}>🔴 0 — Ausencia</option>
                                  <option value="1" {{ $val == 1 ? 'selected' : '' }}>🟡 1 — Fragilidad</option>
                                  <option value="2" {{ $val == 2 ? 'selected' : '' }}>🟢 2 — Aprovechable</option>
                                </select>
                              </div>

                            @else
                              {{-- Equipo: select solo para campos activos --}}
                              @if(in_array($campo, $camposActivos))
                              <div class="pt-select-wrap">
                                <select name="{{ $campo }}" class="pt-select">
                                  <option value="0" {{ $val == 0 ? 'selected' : '' }}>🔴 0 — Ausencia</option>
                                  <option value="1" {{ $val == 1 ? 'selected' : '' }}>🟡 1 — Fragilidad</option>
                                  <option value="2" {{ $val == 2 ? 'selected' : '' }}>🟢 2 — Aprovechable</option>
                                </select>
                              </div>
                              @else
                              <span style="font-size:.72rem;color:#cbd5e1;font-style:italic;flex-shrink:0;">inactivo</span>
                              @endif
                            @endif

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
                  @if($user->role_id == 2)
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
          </div>{{-- /columna principal --}}

          {{-- ══════════════ SIDEBAR ══════════════ --}}
          <div class="pt-sidebar">

            {{-- Escala de valores ──────────────────────────────────────── --}}
            <div class="pt-card">
              <div class="pt-card-title">Escala de calificación</div>
              <div class="pt-scale-row">
                <span class="pt-scale-badge" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">0</span>
                <div>
                  <div style="font-size:.81rem;font-weight:600;color:#374151;">Ausencia</div>
                  <div style="font-size:.71rem;color:#94a3b8;">No existe o es inexistente</div>
                </div>
              </div>
              <div class="pt-scale-row">
                <span class="pt-scale-badge" style="background:#fffbeb;color:#d97706;border:1px solid #fde68a;">1</span>
                <div>
                  <div style="font-size:.81rem;font-weight:600;color:#374151;">Fragilidad</div>
                  <div style="font-size:.71rem;color:#94a3b8;">Existe pero es débil / incipiente</div>
                </div>
              </div>
              <div class="pt-scale-row">
                <span class="pt-scale-badge" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;">2</span>
                <div>
                  <div style="font-size:.81rem;font-weight:600;color:#374151;">Aprovechable</div>
                  <div style="font-size:.71rem;color:#94a3b8;">Consolidado y funcional</div>
                </div>
              </div>
            </div>

            @if($puedeConfigurar)
            {{-- Nota para el Jefe ──────────────────────────────────────── --}}
            <div class="pt-card" style="background:#eff6ff;border-color:#bfdbfe;">
              <div class="pt-card-title" style="color:#3b82f6;">Modo Jefe</div>
              <p style="font-size:.78rem;color:#1e40af;line-height:1.5;">
                Usa los <strong>toggles</strong> para activar o desactivar campos según la realidad de tu zona.
                Solo los campos activos se incluirán en el cálculo final.
              </p>
            </div>
            @endif

            {{-- Navegación por áreas ──────────────────────────────────── --}}
            <div class="pt-card">
              <div class="pt-card-title">Áreas</div>
              @foreach($areaConfig as $areaName => $areaData)
                @php
                    $totalA = 0; $activosA = 0;
                    foreach($areaData['secs'] as $sec => $cam) {
                        foreach($cam as $c => $l) {
                            if ($puedeConfigurar || in_array($c, $camposActivos)) $totalA++;
                            if (in_array($c, $camposActivos)) $activosA++;
                        }
                    }
                    if ($totalA === 0) continue;
                @endphp
                <a href="#area-{{ Str::slug($areaName) }}" class="pt-nav-item">
                  <span style="font-size:15px;">{{ $areaData['emoji'] }}</span>
                  <div>
                    <div class="pt-nav-label">{{ $areaName }}</div>
                    <div class="pt-nav-sub">{{ $activosA }} activos · {{ $totalA }} campos</div>
                  </div>
                </a>
              @endforeach
            </div>

            {{-- Estado ────────────────────────────────────────────────── --}}
            <div class="pt-card">
              <div class="pt-card-title">Estado</div>
              @if($isConfirmado)
                <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#f0fdf4;border-radius:8px;border:1px solid #86efac;">
                  <span style="font-size:15px;">✅</span>
                  <span style="font-size:.81rem;font-weight:600;color:#15803d;">Confirmado</span>
                </div>
              @elseif($evaluacion->exists)
                <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#fffbeb;border-radius:8px;border:1px solid #fde68a;">
                  <span style="font-size:15px;">✏️</span>
                  <span style="font-size:.81rem;font-weight:600;color:#92400e;">Borrador</span>
                </div>
              @else
                <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">
                  <span style="font-size:15px;">🆕</span>
                  <span style="font-size:.81rem;font-weight:600;color:#64748b;">Sin iniciar</span>
                </div>
              @endif

              @if($evaluacion->exists && $evaluacion->updated_at)
              <div style="font-size:.71rem;color:#94a3b8;margin-top:8px;">
                Última edición:<br>
                <strong style="color:#64748b;">{{ $evaluacion->updated_at->format('d/m/Y H:i') }}</strong>
              </div>
              @endif

              @if($isConfirmado)
              <a href="{{ route('operativo.evaluacion_potencialidad.ponderacion', $zona->id) }}"
                 style="display:block;margin-top:10px;text-align:center;background:#16a34a;color:#fff;font-size:.78rem;font-weight:700;padding:8px;border-radius:8px;text-decoration:none;">
                Ver Resultados →
              </a>
              @endif
            </div>

          </div>{{-- /sidebar --}}
        </div>{{-- /pt-layout --}}
      </div>{{-- /container --}}
    </div>{{-- /pt-root --}}

    {{-- Smooth scroll para la navegación del sidebar ──────────────────────── --}}
    <script>
        // ── Componente Alpine por sección ─────────────────────────────────────
        function ptSection(campos, activosList) {
            const initStates = {};
            campos.forEach(c => { initStates[c] = activosList.includes(c); });
            return {
                states: { ...initStates },
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

        // ── Smooth scroll ──────────────────────────────────────────────────
        document.querySelectorAll('.pt-nav-item[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const id = a.getAttribute('href').slice(1);
                const target = document.getElementById(id);
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    </script>
</x-app-layout>
