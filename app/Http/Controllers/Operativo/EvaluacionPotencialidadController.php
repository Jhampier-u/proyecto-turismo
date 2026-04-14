<?php
namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Models\EvaluacionPotencialidad;
use App\Models\PotencialidadCamposActivos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluacionPotencialidadController extends Controller
{
    // ── Estructura completa de secciones y sus campos ────────────────────────
    public static array $secciones = [
        'RN — Zonas de Litoral' => [
            'rn_litoral_playas'               => 'Playas',
            'rn_litoral_arrecifes'            => 'Arrecifes',
            'rn_litoral_cuevas'               => 'Cuevas / Grutas / Cenotes',
            'rn_litoral_flora_fauna'          => 'Flora y Fauna Litoral',
            'rn_litoral_actividades_acuaticas'=> 'Actividades Acuáticas',
            'rn_litoral_areas_deserticas'     => 'Áreas Desérticas Costeras',
        ],
        'RN — Zonas de Montaña' => [
            'rn_montana_montanas'         => 'Montañas',
            'rn_montana_sierras'          => 'Sierras',
            'rn_montana_canadas'          => 'Cañadas',
            'rn_montana_canones'          => 'Cañones',
            'rn_montana_cuevas'           => 'Cuevas y Grutas',
            'rn_montana_geisers'          => 'Géisers',
            'rn_montana_volcanes'         => 'Volcanes',
            'rn_montana_valles'           => 'Valles',
            'rn_montana_bosques'          => 'Bosques',
            'rn_montana_flora_fauna'      => 'Flora y Fauna de Montaña',
            'rn_montana_areas_deserticas' => 'Áreas Desérticas de Montaña',
        ],
        'RN — Áreas Naturales Protegidas' => [
            'rn_anp_reservas_marinas'      => 'Reservas Marinas',
            'rn_anp_reserva_geobotanica'   => 'Reserva Geobotánica',
            'rn_anp_reserva_ecologica'     => 'Reserva Ecológica',
            'rn_anp_reserva_fauna'         => 'Reserva de Producción de Fauna',
            'rn_anp_reserva_biologica'     => 'Reserva Biológica',
            'rn_anp_reserva_vida_silvestre'=> 'Reserva de Vida Silvestre',
            'rn_anp_parques_nacionales'    => 'Parques Nacionales',
            'rn_anp_area_privada'          => 'Área Protegida Privada',
            'rn_anp_area_comunitaria'      => 'Área Protegida Comunitaria',
            'rn_anp_area_recreacion'       => 'Área Nacional de Recreación',
            'rn_anp_area_conservacion'     => 'Área Ecológica de Conservación',
        ],
        'RN — Cuerpos de Agua' => [
            'rn_agua_lagos'    => 'Lagos y Lagunas',
            'rn_agua_rios'     => 'Ríos y Arroyos',
            'rn_agua_cascadas' => 'Cascadas y Caídas de Agua',
            'rn_agua_termas'   => 'Termas',
            'rn_agua_esteros'  => 'Esteros',
        ],
        'RC — Artístico Monumental' => [
            'rc_am_zonas_arqueologicas'  => 'Zonas Arqueológicas',
            'rc_am_fosiles'              => 'Fósiles',
            'rc_am_pinturas_rupestres'   => 'Pinturas Rupestres',
            'rc_am_ciudades_coloniales'  => 'Ciudades Coloniales',
            'rc_am_pueblos_antiguos'     => 'Pueblos Antiguos',
            'rc_am_patrimonio_humanidad' => 'Sitios Patrimonio de la Humanidad',
            'rc_am_santuarios'           => 'Santuarios',
        ],
        'RC — Nacionalidades y Pueblos' => [
            'rc_np_grupos_etnicos'         => 'Grupos Étnicos',
            'rc_np_expresiones_artisticas' => 'Expresiones Artísticas Folklóricas',
            'rc_np_ferias_mercados'        => 'Ferias y Mercados Tradicionales',
            'rc_np_eventos_folkloricos'    => 'Eventos Folklóricos',
            'rc_np_eventos_historicos'     => 'Eventos Históricos y/o Religiosos',
        ],
        'RC — Expresiones Contemporáneas' => [
            'rc_ec_obras_arte'             => 'Obras de Arte',
            'rc_ec_centros_cientificos'    => 'Centros Científicos y Técnicos',
            'rc_ec_explotaciones_mineras'  => 'Explotaciones Mineras',
            'rc_ec_plantaciones'           => 'Plantaciones Agropecuarias',
            'rc_ec_complejos_industriales' => 'Complejos Industriales',
        ],
        'PT — Alojamiento' => [
            'pt_aloj_hoteles'        => 'Hoteles',
            'pt_aloj_hostales'       => 'Hostales',
            'pt_aloj_hosterias'      => 'Hosterías',
            'pt_aloj_haciendas'      => 'Haciendas Turísticas',
            'pt_aloj_lodges'         => 'Lodges',
            'pt_aloj_resorts'        => 'Resorts',
            'pt_aloj_refugios'       => 'Refugios',
            'pt_aloj_campamentos'    => 'Campamentos Turísticos',
            'pt_aloj_casa_huespedes' => 'Casa de Huéspedes',
            'pt_aloj_ctc'            => 'Centro de Turismo Comunitario',
        ],
        'PT — Restauración' => [
            'pt_rest_restaurantes'  => 'Restaurantes',
            'pt_rest_cafeterias'    => 'Cafeterías',
            'pt_rest_bares'         => 'Bares',
            'pt_rest_discotecas'    => 'Discotecas',
            'pt_rest_moviles'       => 'Establecimientos Móviles',
            'pt_rest_plazas_comida' => 'Plazas de Comida',
            'pt_rest_catering'      => 'Servicios de Catering',
            'pt_rest_ctc'           => 'CTC — Restauración',
        ],
        'PT — Intermediación' => [
            'pt_inter_mayoristas'      => 'Agencias Mayoristas',
            'pt_inter_internacionales' => 'Agencias Internacionales',
            'pt_inter_operadoras'      => 'Operadoras Turísticas',
            'pt_inter_duales'          => 'Agencias Duales',
            'pt_inter_ctc'             => 'CTC — Operación',
        ],
        'PT — Transportación' => [
            'pt_trans_terrestre'   => 'Transportación Terrestre',
            'pt_trans_ferroviaria' => 'Transportación Ferroviaria',
            'pt_trans_aerea'       => 'Transportación Aérea',
            'pt_trans_maritima'    => 'Transportación Marítima',
            'pt_trans_fluvial'     => 'Transportación Fluvial',
            'pt_trans_ctc'         => 'CTC — Transportación',
        ],
        'PT — Interpretación / Guianza' => [
            'pt_guia_locales'    => 'Guías Locales',
            'pt_guia_nacionales' => 'Guías Nacionales',
            'pt_guia_patrimonio' => 'Guías Especializados Patrimonio',
            'pt_guia_aventura'   => 'Guías Especializados Aventura',
        ],
        'Tipologías de Turismo' => [
            'tt_naturaleza'          => 'Turismo de Naturaleza',
            'tt_sol_playa'           => 'Turismo de Sol y Playa',
            'tt_cultural'            => 'Turismo Cultural',
            'tt_urbano'              => 'Turismo Urbano',
            'tt_especializado'       => 'Turismo Especializado / Alternativo',
            'tt_rural'               => 'Turismo Rural',
            'tt_agroturismo'         => 'Agroturismo',
            'tt_etnoturismo'         => 'Etnoturismo',
            'tt_aventura'            => 'Turismo de Aventura',
            'tt_deportivo'           => 'Turismo Deportivo',
            'tt_ecoturismo'          => 'Ecoturismo',
            'tt_negocios'            => 'Turismo de Negocios',
            'tt_gastronomico'        => 'Turismo Gastronómico',
            'tt_activo'              => 'Turismo Activo',
            'tt_vivencial'           => 'Turismo Vivencial',
            'tt_experiencial'        => 'Turismo Experiencial',
            'tt_patrimonial'         => 'Turismo Patrimonial',
            'tt_historico'           => 'Turismo Histórico',
            'tt_arqueologico'        => 'Turismo Arqueológico',
            'tt_arquitectonico'      => 'Turismo Arquitectónico y Monumental',
            'tt_literario'           => 'Turismo Literario',
            'tt_astronomico'         => 'Turismo Astronómico',
            'tt_espacial'            => 'Turismo Espacial',
            'tt_compras'             => 'Turismo de Compras',
            'tt_enoturismo'          => 'Enoturismo',
            'tt_salud'               => 'Turismo de Salud',
            'tt_artistico'           => 'Turismo Artístico / Lúdico / Festivo',
            'tt_cinematografico'     => 'Turismo Cinematográfico',
            'tt_cinegetico'          => 'Turismo Cinegético',
            'tt_intereses_especiales'=> 'Turismo de Intereses Especiales',
            'tt_idiomatico'          => 'Turismo Idiomático',
            'tt_cruceros'            => 'Turismo de Cruceros',
            'tt_marino_costero'      => 'Turismo Marino Costero',
            'tt_nautico'             => 'Turismo Náutico',
            'tt_religioso'           => 'Turismo Religioso',
            'tt_social'              => 'Turismo Social',
            'tt_comunitario'         => 'Turismo Comunitario',
        ],
        'Infraestructura' => [
            'i_transporte'         => 'Transporte',
            'i_vialidad'           => 'Vialidad',
            'i_comunicaciones'     => 'Comunicaciones',
            'i_salud'              => 'Salud',
            'i_energia'            => 'Energía Eléctrica',
            'i_agua_potable'       => 'Agua Potable',
            'i_alcantarillado'     => 'Alcantarillado',
            'i_tratamiento_basura' => 'Tratamiento de Basura',
            'i_aguas_residuales'   => 'Aguas Residuales',
            'i_conectividad'       => 'Conectividad / Internet',
            'i_senalizacion'       => 'Señalización',
        ],
        'Afluencia Turística' => [
            'dt_at_locales'         => 'Flujos Locales',
            'dt_at_regionales'      => 'Flujos Regionales',
            'dt_at_nacionales'      => 'Flujos Nacionales',
            'dt_at_internacionales' => 'Flujos Internacionales',
            'dt_at_estadia'         => 'Estadía Promedio',
        ],
        'Marketing Turístico' => [
            'dt_mk_organismo_promotor'        => 'Organismo Público Promotor',
            'dt_mk_plan_marketing'            => 'Plan de Marketing',
            'dt_mk_tendencias_mercado'        => 'Tendencias de Mercado',
            'dt_mk_investigacion'             => 'Investigación y Segmentación',
            'dt_mk_publicidad'                => 'Publicidad y Promoción',
            'dt_mk_comercializacion_tradicional' => 'Comercialización Tradicional',
            'dt_mk_comercializacion_digital'  => 'Comercialización Digital',
            'dt_mk_imagen_sitio'              => 'Imagen del Sitio',
            'dt_mk_presencia_digital'         => 'Presencia Digital',
            'dt_mk_innovacion'                => 'Innovación Turística',
        ],
        'Superestructura' => [
            'st_politica_publica'              => 'Política Pública de Turismo',
            'st_modelo_gestion'                => 'Modelo de Gestión Turística',
            'st_actores'                       => 'Actores de la Actividad Turística',
            'st_marco_normativo'               => 'Marco Normativo',
            'st_participacion_ciudadana'       => 'Participación Ciudadana',
            'st_planificacion_participativa'   => 'Planificación Participativa',
            'st_entes_publicos'                => 'Entes Públicos de Turismo',
            'st_representacion_gremial'        => 'Representación Gremial',
            'st_representatividad_comunitaria' => 'Representatividad Comunitaria',
            'st_fomento'                       => 'Fomento al Sector Turístico',
        ],
    ];

    // ── Devuelve todos los nombres de campos de las secciones ─────────────────
    private function getAllCampos(): array
    {
        return collect(self::$secciones)
            ->flatMap(fn($campos) => array_keys($campos))
            ->all();
    }

    // ── Vista combinada (selección + calificación en una sola página) ─────────
    public function edit($zonaId)
    {
        $zona      = Zona::findOrFail($zonaId);
        $user      = Auth::user();
        $evaluacion = EvaluacionPotencialidad::firstOrNew(['zona_id' => $zonaId]);
        $config    = PotencialidadCamposActivos::where('zona_id', $zonaId)->first();

        // Si no hay configuración previa, todos los campos están activos por defecto
        $camposActivos = $config
            ? $config->campos_activos
            : $this->getAllCampos();

        $secciones = self::$secciones;

        return view('operativo.evaluacion_potencialidad.form',
            compact('zona', 'evaluacion', 'camposActivos', 'user', 'secciones'));
    }

    // ── Guarda selección + calificaciones en un solo POST ────────────────────
    public function update(Request $request, $zonaId)
    {
        $user            = Auth::user();
        $evaluacionActual = EvaluacionPotencialidad::where('zona_id', $zonaId)->first();

        if ($evaluacionActual && $evaluacionActual->estado === 'confirmado' && $user->role_id == 3) {
            return back()->with('error', 'Evaluación cerrada. No puedes editar.');
        }

        // Campos seleccionados (activos) enviados desde el formulario
        $camposActivos = $request->input('campos', []);

        // Solo el Jefe puede cambiar qué campos están activos
        if ($user->role_id == 2) {
            PotencialidadCamposActivos::updateOrCreate(
                ['zona_id' => $zonaId],
                ['campos_activos' => $camposActivos]
            );
        } else {
            // Equipo: conservar la configuración actual del Jefe
            $config = PotencialidadCamposActivos::where('zona_id', $zonaId)->first();
            $camposActivos = $config ? $config->campos_activos : $this->getAllCampos();
        }

        // Validar solo los campos activos
        $rules = [];
        foreach ($camposActivos as $campo) {
            $rules[$campo] = 'integer|min:0|max:2';
        }
        $request->validate($rules);

        // Construir el array de valores para todos los campos conocidos
        $todosLosCampos = $this->getAllCampos();
        $valores = [];
        foreach ($todosLosCampos as $campo) {
            if (in_array($campo, $camposActivos)) {
                $valores[$campo] = (int) $request->input($campo, 0);
            } else {
                // Preservar valor anterior o establecer 0
                $valores[$campo] = $evaluacionActual ? ($evaluacionActual->$campo ?? 0) : 0;
            }
        }

        // Calcular puntajes usando solo los campos activos
        $calc = $this->calcular($valores, $camposActivos);

        $estado = ($user->role_id == 2)
            ? $request->input('accion_estado', 'borrador')
            : 'borrador';

        EvaluacionPotencialidad::updateOrCreate(
            ['zona_id' => $zonaId],
            array_merge($valores, $calc, [
                'user_id' => $user->id,
                'estado'  => $estado,
            ])
        );

        $mensaje = ($estado === 'confirmado')
            ? 'Evaluación CONFIRMADA. FN: ' . number_format($calc['fn_total'], 2) . ' | FX: ' . number_format($calc['fx_total'], 2)
            : 'Borrador guardado correctamente.';

        return redirect()
            ->route('operativo.evaluacion_potencialidad.edit', $zonaId)
            ->with('success', $mensaje);
    }

    // ── Reconfigurar: activa todos los campos (reset) ─────────────────────────
    public function reconfigurarCampos($zonaId)
    {
        $user = Auth::user();
        if ($user->role_id == 3) {
            return back()->with('error', 'Solo el Jefe de Zona puede reconfigurar los campos.');
        }

        PotencialidadCamposActivos::updateOrCreate(
            ['zona_id' => $zonaId],
            ['campos_activos' => $this->getAllCampos()]
        );

        return redirect()
            ->route('operativo.evaluacion_potencialidad.edit', $zonaId)
            ->with('success', 'Todos los campos han sido activados.');
    }

    // ── Resultados / Ponderación ──────────────────────────────────────────────
    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $eval = EvaluacionPotencialidad::where('zona_id', $zonaId)->firstOrFail();
        return view('operativo.evaluacion_potencialidad.ponderacion', compact('zona', 'eval'));
    }

    // ── Método de campos (ya no se usa como página separada; redirige a edit) ──
    public function guardarCampos(Request $request, $zonaId)
    {
        return redirect()->route('operativo.evaluacion_potencialidad.edit', $zonaId);
    }

    // ── Cálculo ponderado usando solo los campos activos ─────────────────────
    private function calcular(array $v, array $camposActivos): array
    {
        $avg = function(array $candidatos) use ($v, $camposActivos): float {
            $activos = array_filter($candidatos, fn($c) => in_array($c, $camposActivos) && isset($v[$c]));
            if (empty($activos)) return 0;
            return array_sum(array_map(fn($c) => (float)$v[$c], $activos)) / count($activos);
        };

        $hasCampos = fn(array $lista) => !empty(array_intersect($camposActivos, $lista));

        // ── Recursos Naturales ──────────────────────────────────────────────
        $litoral = ['rn_litoral_playas','rn_litoral_arrecifes','rn_litoral_cuevas','rn_litoral_flora_fauna','rn_litoral_actividades_acuaticas','rn_litoral_areas_deserticas'];
        $montana = ['rn_montana_montanas','rn_montana_sierras','rn_montana_canadas','rn_montana_canones','rn_montana_cuevas','rn_montana_geisers','rn_montana_volcanes','rn_montana_valles','rn_montana_bosques','rn_montana_flora_fauna','rn_montana_areas_deserticas'];
        $anp     = ['rn_anp_reservas_marinas','rn_anp_reserva_geobotanica','rn_anp_reserva_ecologica','rn_anp_reserva_fauna','rn_anp_reserva_biologica','rn_anp_reserva_vida_silvestre','rn_anp_parques_nacionales','rn_anp_area_privada','rn_anp_area_comunitaria','rn_anp_area_recreacion','rn_anp_area_conservacion'];
        $agua    = ['rn_agua_lagos','rn_agua_rios','rn_agua_cascadas','rn_agua_termas','rn_agua_esteros'];

        $rn_litoral = $avg($litoral); $rn_montana = $avg($montana);
        $rn_anp = $avg($anp);         $rn_agua = $avg($agua);

        $rn_grupos = array_filter([$hasCampos($litoral) ? $rn_litoral : null, $hasCampos($montana) ? $rn_montana : null, $hasCampos($anp) ? $rn_anp : null, $hasCampos($agua) ? $rn_agua : null], fn($v) => $v !== null);
        $val_rn = empty($rn_grupos) ? 0 : array_sum($rn_grupos) / count($rn_grupos);

        // ── Recursos Culturales ─────────────────────────────────────────────
        $am = ['rc_am_zonas_arqueologicas','rc_am_fosiles','rc_am_pinturas_rupestres','rc_am_ciudades_coloniales','rc_am_pueblos_antiguos','rc_am_patrimonio_humanidad','rc_am_santuarios'];
        $np = ['rc_np_grupos_etnicos','rc_np_expresiones_artisticas','rc_np_ferias_mercados','rc_np_eventos_folkloricos','rc_np_eventos_historicos'];
        $ec = ['rc_ec_obras_arte','rc_ec_centros_cientificos','rc_ec_explotaciones_mineras','rc_ec_plantaciones','rc_ec_complejos_industriales'];

        $rc_am = $avg($am); $rc_np = $avg($np); $rc_ec = $avg($ec);

        $rc_grupos = array_filter([$hasCampos($am) ? $rc_am : null, $hasCampos($np) ? $rc_np : null, $hasCampos($ec) ? $rc_ec : null], fn($v) => $v !== null);
        $val_rc = empty($rc_grupos) ? 0 : array_sum($rc_grupos) / count($rc_grupos);

        $val_rt = $val_rn * 0.5 + $val_rc * 0.5;

        // ── Planta Turística ────────────────────────────────────────────────
        $aloj  = ['pt_aloj_hoteles','pt_aloj_hostales','pt_aloj_hosterias','pt_aloj_haciendas','pt_aloj_lodges','pt_aloj_resorts','pt_aloj_refugios','pt_aloj_campamentos','pt_aloj_casa_huespedes','pt_aloj_ctc'];
        $rest  = ['pt_rest_restaurantes','pt_rest_cafeterias','pt_rest_bares','pt_rest_discotecas','pt_rest_moviles','pt_rest_plazas_comida','pt_rest_catering','pt_rest_ctc'];
        $inter = ['pt_inter_mayoristas','pt_inter_internacionales','pt_inter_operadoras','pt_inter_duales','pt_inter_ctc'];
        $trans = ['pt_trans_terrestre','pt_trans_ferroviaria','pt_trans_aerea','pt_trans_maritima','pt_trans_fluvial','pt_trans_ctc'];
        $guia  = ['pt_guia_locales','pt_guia_nacionales','pt_guia_patrimonio','pt_guia_aventura'];

        $pt_aloj  = $avg($aloj);  $pt_rest  = $avg($rest);  $pt_inter = $avg($inter);
        $pt_trans = $avg($trans); $pt_guia  = $avg($guia);

        $pt_grupos = array_filter([$hasCampos($aloj)?$pt_aloj:null,$hasCampos($rest)?$pt_rest:null,$hasCampos($inter)?$pt_inter:null,$hasCampos($trans)?$pt_trans:null,$hasCampos($guia)?$pt_guia:null], fn($v)=>$v!==null);
        $val_pt = empty($pt_grupos) ? 0 : array_sum($pt_grupos) / count($pt_grupos);

        // ── Tipologías e Infraestructura ────────────────────────────────────
        $tt_campos = array_keys(self::$secciones['Tipologías de Turismo']);
        $i_campos  = array_keys(self::$secciones['Infraestructura']);
        $val_tt = $avg($tt_campos);
        $val_i  = $avg($i_campos);

        // FN = RT×40% + PT×20% + TT×20% + I×20%
        $fn_total = $val_rt * 0.40 + $val_pt * 0.20 + $val_tt * 0.20 + $val_i * 0.20;

        // ── Factores Exógenos ───────────────────────────────────────────────
        $at_campos = array_keys(self::$secciones['Afluencia Turística']);
        $mk_campos = array_keys(self::$secciones['Marketing Turístico']);
        $st_campos = array_keys(self::$secciones['Superestructura']);

        $val_afluencia   = $avg($at_campos);
        $val_marketing   = $avg($mk_campos);
        $val_superestructura = $avg($st_campos);

        // FX = Afluencia×40% + Marketing×30% + Superestructura×30%
        $fx_total = $val_afluencia * 0.40 + $val_marketing * 0.30 + $val_superestructura * 0.30;

        return [
            'val_rn_litoral'       => $rn_litoral, 'val_rn_montana'    => $rn_montana,
            'val_rn_anp'           => $rn_anp,     'val_rn_agua'       => $rn_agua,
            'val_recursos_naturales'  => $val_rn,  'val_rc_am'         => $rc_am,
            'val_rc_np'            => $rc_np,      'val_rc_ec'         => $rc_ec,
            'val_recursos_culturales' => $val_rc,  'val_recursos_turisticos' => $val_rt,
            'val_pt_alojamiento'   => $pt_aloj,    'val_pt_restauracion'  => $pt_rest,
            'val_pt_intermediacion'=> $pt_inter,   'val_pt_transportacion' => $pt_trans,
            'val_pt_interpretacion'=> $pt_guia,    'val_planta_turistica'  => $val_pt,
            'val_tipologias'       => $val_tt,     'val_infraestructura'   => $val_i,
            'fn_total'             => $fn_total,
            'val_afluencia'        => $val_afluencia,
            'val_marketing'        => $val_marketing,
            'val_superestructura'  => $val_superestructura,
            'fx_total'             => $fx_total,
        ];
    }
}
