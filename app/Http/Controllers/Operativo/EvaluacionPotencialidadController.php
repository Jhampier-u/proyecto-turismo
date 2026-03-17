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
    // Estructura completa de secciones y sus campos
    public static array $secciones = [
        'RN — Zonas de Litoral' => [
            'rn_litoral_playas'            => 'Playas',
            'rn_litoral_arrecifes'         => 'Arrecifes',
            'rn_litoral_cuevas'            => 'Cuevas / Grutas / Cenotes',
            'rn_litoral_flora_fauna'       => 'Flora y Fauna Litoral',
            'rn_litoral_actividades_acuaticas' => 'Actividades Acuáticas',
            'rn_litoral_areas_deserticas'  => 'Áreas Desérticas Costeras',
        ],
        'RN — Zonas de Montaña' => [
            'rn_montana_montanas'          => 'Montañas',
            'rn_montana_sierras'           => 'Sierras',
            'rn_montana_canadas'           => 'Cañadas',
            'rn_montana_canones'           => 'Cañones',
            'rn_montana_cuevas'            => 'Cuevas y Grutas',
            'rn_montana_geisers'           => 'Géisers',
            'rn_montana_volcanes'          => 'Volcanes',
            'rn_montana_valles'            => 'Valles',
            'rn_montana_bosques'           => 'Bosques',
            'rn_montana_flora_fauna'       => 'Flora y Fauna de Montaña',
            'rn_montana_areas_deserticas'  => 'Áreas Desérticas de Montaña',
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
            'rc_am_zonas_arqueologicas' => 'Zonas Arqueológicas',
            'rc_am_fosiles'             => 'Fósiles',
            'rc_am_pinturas_rupestres'  => 'Pinturas Rupestres',
            'rc_am_ciudades_coloniales' => 'Ciudades Coloniales',
            'rc_am_pueblos_antiguos'    => 'Pueblos Antiguos',
            'rc_am_patrimonio_humanidad'=> 'Sitios Patrimonio de la Humanidad',
            'rc_am_santuarios'          => 'Santuarios',
        ],
        'RC — Nacionalidades y Pueblos' => [
            'rc_np_grupos_etnicos'        => 'Grupos Étnicos',
            'rc_np_expresiones_artisticas'=> 'Expresiones Artísticas Folklóricas',
            'rc_np_ferias_mercados'       => 'Ferias y Mercados Tradicionales',
            'rc_np_eventos_folkloricos'   => 'Eventos Folklóricos',
            'rc_np_eventos_historicos'    => 'Eventos Históricos y/o Religiosos',
        ],
        'RC — Expresiones Contemporáneas' => [
            'rc_ec_obras_arte'            => 'Obras de Arte',
            'rc_ec_centros_cientificos'   => 'Centros Científicos y Técnicos',
            'rc_ec_explotaciones_mineras' => 'Explotaciones Mineras',
            'rc_ec_plantaciones'          => 'Plantaciones Agropecuarias',
            'rc_ec_complejos_industriales'=> 'Complejos Industriales',
        ],
        'PT — Alojamiento' => [
            'pt_aloj_hoteles'       => 'Hoteles',
            'pt_aloj_hostales'      => 'Hostales',
            'pt_aloj_hosterias'     => 'Hosterías',
            'pt_aloj_haciendas'     => 'Haciendas Turísticas',
            'pt_aloj_lodges'        => 'Lodges',
            'pt_aloj_resorts'       => 'Resorts',
            'pt_aloj_refugios'      => 'Refugios',
            'pt_aloj_campamentos'   => 'Campamentos Turísticos',
            'pt_aloj_casa_huespedes'=> 'Casa de Huéspedes',
            'pt_aloj_ctc'           => 'Centro de Turismo Comunitario',
        ],
        'PT — Restauración' => [
            'pt_rest_restaurantes' => 'Restaurantes',
            'pt_rest_cafeterias'   => 'Cafeterías',
            'pt_rest_bares'        => 'Bares',
            'pt_rest_discotecas'   => 'Discotecas',
            'pt_rest_moviles'      => 'Establecimientos Móviles',
            'pt_rest_plazas_comida'=> 'Plazas de Comida',
            'pt_rest_catering'     => 'Servicios de Catering',
            'pt_rest_ctc'          => 'CTC — Restauración',
        ],
        'PT — Intermediación' => [
            'pt_inter_mayoristas'      => 'Agencias Mayoristas',
            'pt_inter_internacionales' => 'Agencias Internacionales',
            'pt_inter_operadoras'      => 'Operadoras Turísticas',
            'pt_inter_duales'          => 'Agencias Duales',
            'pt_inter_ctc'             => 'CTC — Operación',
        ],
        'PT — Transportación' => [
            'pt_trans_terrestre'  => 'Transportación Terrestre',
            'pt_trans_ferroviaria'=> 'Transportación Ferroviaria',
            'pt_trans_aerea'      => 'Transportación Aérea',
            'pt_trans_maritima'   => 'Transportación Marítima',
            'pt_trans_fluvial'    => 'Transportación Fluvial',
            'pt_trans_ctc'        => 'CTC — Transportación',
        ],
        'PT — Interpretación / Guianza' => [
            'pt_guia_locales'    => 'Guías Locales',
            'pt_guia_nacionales' => 'Guías Nacionales',
            'pt_guia_patrimonio' => 'Guías Especializados Patrimonio',
            'pt_guia_aventura'   => 'Guías Especializados Aventura',
        ],
        'Tipologías de Turismo' => [
            'tt_naturaleza'         => 'Turismo de Naturaleza',
            'tt_sol_playa'          => 'Turismo de Sol y Playa',
            'tt_cultural'           => 'Turismo Cultural',
            'tt_urbano'             => 'Turismo Urbano',
            'tt_especializado'      => 'Turismo Especializado / Alternativo',
            'tt_rural'              => 'Turismo Rural',
            'tt_agroturismo'        => 'Agroturismo',
            'tt_etnoturismo'        => 'Etnoturismo',
            'tt_aventura'           => 'Turismo de Aventura',
            'tt_deportivo'          => 'Turismo Deportivo',
            'tt_ecoturismo'         => 'Ecoturismo',
            'tt_negocios'           => 'Turismo de Negocios',
            'tt_gastronomico'       => 'Turismo Gastronómico',
            'tt_activo'             => 'Turismo Activo',
            'tt_vivencial'          => 'Turismo Vivencial',
            'tt_experiencial'       => 'Turismo Experiencial',
            'tt_patrimonial'        => 'Turismo Patrimonial',
            'tt_historico'          => 'Turismo Histórico',
            'tt_arqueologico'       => 'Turismo Arqueológico',
            'tt_arquitectonico'     => 'Turismo Arquitectónico y Monumental',
            'tt_literario'          => 'Turismo Literario',
            'tt_astronomico'        => 'Turismo Astronómico',
            'tt_espacial'           => 'Turismo Espacial',
            'tt_compras'            => 'Turismo de Compras',
            'tt_enoturismo'         => 'Enoturismo',
            'tt_salud'              => 'Turismo de Salud',
            'tt_artistico'          => 'Turismo Artístico / Lúdico / Festivo',
            'tt_cinematografico'    => 'Turismo Cinematográfico',
            'tt_cinegetico'         => 'Turismo Cinegético',
            'tt_intereses_especiales'=> 'Turismo de Intereses Especiales',
            'tt_idiomatico'         => 'Turismo Idiomático',
            'tt_cruceros'           => 'Turismo de Cruceros',
            'tt_marino_costero'     => 'Turismo Marino Costero',
            'tt_nautico'            => 'Turismo Náutico',
            'tt_religioso'          => 'Turismo Religioso',
            'tt_social'             => 'Turismo Social',
            'tt_comunitario'        => 'Turismo Comunitario',
        ],
        'Infraestructura' => [
            'i_transporte'          => 'Transporte',
            'i_vialidad'            => 'Vialidad',
            'i_comunicaciones'      => 'Comunicaciones',
            'i_salud'               => 'Salud',
            'i_energia'             => 'Energía Eléctrica',
            'i_agua_potable'        => 'Agua Potable',
            'i_alcantarillado'      => 'Alcantarillado',
            'i_tratamiento_basura'  => 'Tratamiento de Basura',
            'i_aguas_residuales'    => 'Aguas Residuales',
            'i_conectividad'        => 'Conectividad / Internet',
            'i_senalizacion'        => 'Señalización',
        ],
        'Afluencia Turística' => [
            'dt_at_locales'        => 'Flujos Locales',
            'dt_at_regionales'     => 'Flujos Regionales',
            'dt_at_nacionales'     => 'Flujos Nacionales',
            'dt_at_internacionales'=> 'Flujos Internacionales',
            'dt_at_estadia'        => 'Estadía Promedio',
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
            'st_politica_publica'             => 'Política Pública de Turismo',
            'st_modelo_gestion'               => 'Modelo de Gestión Turística',
            'st_actores'                      => 'Actores de la Actividad Turística',
            'st_marco_normativo'              => 'Marco Normativo',
            'st_participacion_ciudadana'      => 'Participación Ciudadana',
            'st_planificacion_participativa'  => 'Planificación Participativa',
            'st_entes_publicos'               => 'Entes Públicos de Turismo',
            'st_representacion_gremial'       => 'Representación Gremial',
            'st_representatividad_comunitaria'=> 'Representatividad Comunitaria',
            'st_fomento'                      => 'Fomento al Sector Turístico',
        ],
    ];

    // Mostrar selector de campos activos si no existe, o el formulario si ya existe config
    public function edit($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $user = Auth::user();
        $evaluacion = EvaluacionPotencialidad::firstOrNew(['zona_id' => $zonaId]);
        $config = PotencialidadCamposActivos::where('zona_id', $zonaId)->first();

        $secciones = self::$secciones;

        // Si no hay configuración de campos → mostrar selector primero
        if (!$config) {
            return view('operativo.evaluacion_potencialidad.seleccionar_campos',
                compact('zona', 'user', 'secciones'));
        }

        $camposActivos = $config->campos_activos;
        return view('operativo.evaluacion_potencialidad.form',
            compact('zona', 'evaluacion', 'camposActivos', 'user', 'secciones'));
    }

    // Guardar configuración de campos activos
    public function guardarCampos(Request $request, $zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $camposSeleccionados = $request->input('campos', []);

        if (empty($camposSeleccionados)) {
            return back()->with('error', 'Debes seleccionar al menos un campo.');
        }

        PotencialidadCamposActivos::updateOrCreate(
            ['zona_id' => $zonaId],
            ['campos_activos' => $camposSeleccionados]
        );

        return redirect()->route('operativo.evaluacion_potencialidad.edit', $zonaId)
            ->with('success', 'Configuración de campos guardada.');
    }

    // Reconfigurar campos (resetea la config para poder cambiarla)
    public function reconfigurarCampos($zonaId)
    {
        $user = Auth::user();
        if ($user->role_id == 3) {
            return back()->with('error', 'Solo el Jefe de Zona puede reconfigurar los campos.');
        }
        PotencialidadCamposActivos::where('zona_id', $zonaId)->delete();
        return redirect()->route('operativo.evaluacion_potencialidad.edit', $zonaId)
            ->with('success', 'Configuración de campos reseteada. Selecciona los campos aplicables.');
    }

    public function update(Request $request, $zonaId)
    {
        $user = Auth::user();
        $evaluacionActual = EvaluacionPotencialidad::where('zona_id', $zonaId)->first();

        if ($evaluacionActual && $evaluacionActual->estado === 'confirmado' && $user->role_id == 3) {
            return back()->with('error', 'Evaluación cerrada. No puedes editar.');
        }

        $config = PotencialidadCamposActivos::where('zona_id', $zonaId)->firstOrFail();
        $camposActivos = $config->campos_activos;

        $rules = [];
        foreach ($camposActivos as $campo) {
            $rules[$campo] = 'required|integer|min:0|max:2';
        }
        $validated = $request->validate($rules);

        $calc = $this->calcular($validated, $camposActivos);

        $estado = ($user->role_id == 2)
            ? $request->input('accion_estado', 'borrador')
            : 'borrador';

        EvaluacionPotencialidad::updateOrCreate(
            ['zona_id' => $zonaId],
            array_merge($validated, $calc, [
                'user_id' => $user->id,
                'estado'  => $estado,
            ])
        );

        $mensaje = ($estado === 'confirmado')
            ? 'Evaluación VALIDADA. FN: ' . number_format($calc['fn_total'], 2) . ' | FX: ' . number_format($calc['fx_total'], 2)
            : 'Borrador guardado. FN: ' . number_format($calc['fn_total'], 2) . ' | FX: ' . number_format($calc['fx_total'], 2);

        return redirect()->route('operativo.dashboard')->with('success', $mensaje);
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $eval = EvaluacionPotencialidad::where('zona_id', $zonaId)->firstOrFail();
        return view('operativo.evaluacion_potencialidad.ponderacion', compact('zona', 'eval'));
    }

    // ── Cálculo con sólo los campos activos ─────────────────────────────────
    private function calcular(array $v, array $camposActivos): array
    {
        // Promedia sólo los campos que están activos dentro de un grupo
        $avg = function(array $candidatos) use ($v, $camposActivos): float {
            $activos = array_filter($candidatos, fn($c) => in_array($c, $camposActivos) && isset($v[$c]));
            if (empty($activos)) return 0;
            return array_sum(array_map(fn($c) => (float)$v[$c], $activos)) / count($activos);
        };

        // Recursos Naturales
        $rn_litoral = $avg(['rn_litoral_playas','rn_litoral_arrecifes','rn_litoral_cuevas','rn_litoral_flora_fauna','rn_litoral_actividades_acuaticas','rn_litoral_areas_deserticas']);
        $rn_montana = $avg(['rn_montana_montanas','rn_montana_sierras','rn_montana_canadas','rn_montana_canones','rn_montana_cuevas','rn_montana_geisers','rn_montana_volcanes','rn_montana_valles','rn_montana_bosques','rn_montana_flora_fauna','rn_montana_areas_deserticas']);
        $rn_anp     = $avg(['rn_anp_reservas_marinas','rn_anp_reserva_geobotanica','rn_anp_reserva_ecologica','rn_anp_reserva_fauna','rn_anp_reserva_biologica','rn_anp_reserva_vida_silvestre','rn_anp_parques_nacionales','rn_anp_area_privada','rn_anp_area_comunitaria','rn_anp_area_recreacion','rn_anp_area_conservacion']);
        $rn_agua    = $avg(['rn_agua_lagos','rn_agua_rios','rn_agua_cascadas','rn_agua_termas','rn_agua_esteros']);
        // Promedio de los 4 subgrupos que tienen al menos 1 campo activo
        $rn_grupos_activos = [];
        if (!empty(array_intersect($camposActivos, ['rn_litoral_playas','rn_litoral_arrecifes','rn_litoral_cuevas','rn_litoral_flora_fauna','rn_litoral_actividades_acuaticas','rn_litoral_areas_deserticas']))) $rn_grupos_activos[] = $rn_litoral;
        if (!empty(array_intersect($camposActivos, ['rn_montana_montanas','rn_montana_sierras','rn_montana_canadas','rn_montana_canones','rn_montana_cuevas','rn_montana_geisers','rn_montana_volcanes','rn_montana_valles','rn_montana_bosques','rn_montana_flora_fauna','rn_montana_areas_deserticas']))) $rn_grupos_activos[] = $rn_montana;
        if (!empty(array_intersect($camposActivos, ['rn_anp_reservas_marinas','rn_anp_reserva_geobotanica','rn_anp_reserva_ecologica','rn_anp_reserva_fauna','rn_anp_reserva_biologica','rn_anp_reserva_vida_silvestre','rn_anp_parques_nacionales','rn_anp_area_privada','rn_anp_area_comunitaria','rn_anp_area_recreacion','rn_anp_area_conservacion']))) $rn_grupos_activos[] = $rn_anp;
        if (!empty(array_intersect($camposActivos, ['rn_agua_lagos','rn_agua_rios','rn_agua_cascadas','rn_agua_termas','rn_agua_esteros']))) $rn_grupos_activos[] = $rn_agua;
        $val_rn = empty($rn_grupos_activos) ? 0 : array_sum($rn_grupos_activos) / count($rn_grupos_activos);

        // Recursos Culturales
        $rc_am = $avg(['rc_am_zonas_arqueologicas','rc_am_fosiles','rc_am_pinturas_rupestres','rc_am_ciudades_coloniales','rc_am_pueblos_antiguos','rc_am_patrimonio_humanidad','rc_am_santuarios']);
        $rc_np = $avg(['rc_np_grupos_etnicos','rc_np_expresiones_artisticas','rc_np_ferias_mercados','rc_np_eventos_folkloricos','rc_np_eventos_historicos']);
        $rc_ec = $avg(['rc_ec_obras_arte','rc_ec_centros_cientificos','rc_ec_explotaciones_mineras','rc_ec_plantaciones','rc_ec_complejos_industriales']);
        $rc_grupos = [];
        if (!empty(array_intersect($camposActivos, ['rc_am_zonas_arqueologicas','rc_am_fosiles','rc_am_pinturas_rupestres','rc_am_ciudades_coloniales','rc_am_pueblos_antiguos','rc_am_patrimonio_humanidad','rc_am_santuarios']))) $rc_grupos[] = $rc_am;
        if (!empty(array_intersect($camposActivos, ['rc_np_grupos_etnicos','rc_np_expresiones_artisticas','rc_np_ferias_mercados','rc_np_eventos_folkloricos','rc_np_eventos_historicos']))) $rc_grupos[] = $rc_np;
        if (!empty(array_intersect($camposActivos, ['rc_ec_obras_arte','rc_ec_centros_cientificos','rc_ec_explotaciones_mineras','rc_ec_plantaciones','rc_ec_complejos_industriales']))) $rc_grupos[] = $rc_ec;
        $val_rc = empty($rc_grupos) ? 0 : array_sum($rc_grupos) / count($rc_grupos);

        $val_rt = $val_rn * 0.5 + $val_rc * 0.5; // RT = RN 50% + RC 50%

        // Planta Turística
        $pt_aloj  = $avg(['pt_aloj_hoteles','pt_aloj_hostales','pt_aloj_hosterias','pt_aloj_haciendas','pt_aloj_lodges','pt_aloj_resorts','pt_aloj_refugios','pt_aloj_campamentos','pt_aloj_casa_huespedes','pt_aloj_ctc']);
        $pt_rest  = $avg(['pt_rest_restaurantes','pt_rest_cafeterias','pt_rest_bares','pt_rest_discotecas','pt_rest_moviles','pt_rest_plazas_comida','pt_rest_catering','pt_rest_ctc']);
        $pt_inter = $avg(['pt_inter_mayoristas','pt_inter_internacionales','pt_inter_operadoras','pt_inter_duales','pt_inter_ctc']);
        $pt_trans = $avg(['pt_trans_terrestre','pt_trans_ferroviaria','pt_trans_aerea','pt_trans_maritima','pt_trans_fluvial','pt_trans_ctc']);
        $pt_guia  = $avg(['pt_guia_locales','pt_guia_nacionales','pt_guia_patrimonio','pt_guia_aventura']);
        $pt_grupos = [];
        if (!empty(array_intersect($camposActivos, ['pt_aloj_hoteles','pt_aloj_hostales','pt_aloj_hosterias','pt_aloj_haciendas','pt_aloj_lodges','pt_aloj_resorts','pt_aloj_refugios','pt_aloj_campamentos','pt_aloj_casa_huespedes','pt_aloj_ctc']))) $pt_grupos[] = $pt_aloj;
        if (!empty(array_intersect($camposActivos, ['pt_rest_restaurantes','pt_rest_cafeterias','pt_rest_bares','pt_rest_discotecas','pt_rest_moviles','pt_rest_plazas_comida','pt_rest_catering','pt_rest_ctc']))) $pt_grupos[] = $pt_rest;
        if (!empty(array_intersect($camposActivos, ['pt_inter_mayoristas','pt_inter_internacionales','pt_inter_operadoras','pt_inter_duales','pt_inter_ctc']))) $pt_grupos[] = $pt_inter;
        if (!empty(array_intersect($camposActivos, ['pt_trans_terrestre','pt_trans_ferroviaria','pt_trans_aerea','pt_trans_maritima','pt_trans_fluvial','pt_trans_ctc']))) $pt_grupos[] = $pt_trans;
        if (!empty(array_intersect($camposActivos, ['pt_guia_locales','pt_guia_nacionales','pt_guia_patrimonio','pt_guia_aventura']))) $pt_grupos[] = $pt_guia;
        $val_pt = empty($pt_grupos) ? 0 : array_sum($pt_grupos) / count($pt_grupos);

        // Tipologías
        $tt_campos = ['tt_naturaleza','tt_sol_playa','tt_cultural','tt_urbano','tt_especializado','tt_rural','tt_agroturismo','tt_etnoturismo','tt_aventura','tt_deportivo','tt_ecoturismo','tt_negocios','tt_gastronomico','tt_activo','tt_vivencial','tt_experiencial','tt_patrimonial','tt_historico','tt_arqueologico','tt_arquitectonico','tt_literario','tt_astronomico','tt_espacial','tt_compras','tt_enoturismo','tt_salud','tt_artistico','tt_cinematografico','tt_cinegetico','tt_intereses_especiales','tt_idiomatico','tt_cruceros','tt_marino_costero','tt_nautico','tt_religioso','tt_social','tt_comunitario'];
        $val_tt = $avg($tt_campos);

        // Infraestructura
        $i_campos = ['i_transporte','i_vialidad','i_comunicaciones','i_salud','i_energia','i_agua_potable','i_alcantarillado','i_tratamiento_basura','i_aguas_residuales','i_conectividad','i_senalizacion'];
        $val_i = $avg($i_campos);

        // FN = RT 40% + PT 20% + TT 20% + I 20%
        $fn_total = $val_rt * 0.40 + $val_pt * 0.20 + $val_tt * 0.20 + $val_i * 0.20;

        // Afluencia
        $val_afluencia = $avg(['dt_at_locales','dt_at_regionales','dt_at_nacionales','dt_at_internacionales','dt_at_estadia']);

        // Marketing
        $val_marketing = $avg(['dt_mk_organismo_promotor','dt_mk_plan_marketing','dt_mk_tendencias_mercado','dt_mk_investigacion','dt_mk_publicidad','dt_mk_comercializacion_tradicional','dt_mk_comercializacion_digital','dt_mk_imagen_sitio','dt_mk_presencia_digital','dt_mk_innovacion']);

        // Superestructura
        $val_super = $avg(['st_politica_publica','st_modelo_gestion','st_actores','st_marco_normativo','st_participacion_ciudadana','st_planificacion_participativa','st_entes_publicos','st_representacion_gremial','st_representatividad_comunitaria','st_fomento']);

        // FX = Afluencia 40% + Marketing 30% + Superestructura 30%
        $fx_total = $val_afluencia * 0.40 + $val_marketing * 0.30 + $val_super * 0.30;

        return [
            'val_rn_litoral'=>$rn_litoral,'val_rn_montana'=>$rn_montana,'val_rn_anp'=>$rn_anp,'val_rn_agua'=>$rn_agua,
            'val_recursos_naturales'=>$val_rn,'val_rc_am'=>$rc_am,'val_rc_np'=>$rc_np,'val_rc_ec'=>$rc_ec,
            'val_recursos_culturales'=>$val_rc,'val_recursos_turisticos'=>$val_rt,
            'val_pt_alojamiento'=>$pt_aloj,'val_pt_restauracion'=>$pt_rest,'val_pt_intermediacion'=>$pt_inter,
            'val_pt_transportacion'=>$pt_trans,'val_pt_interpretacion'=>$pt_guia,'val_planta_turistica'=>$val_pt,
            'val_tipologias'=>$val_tt,'val_infraestructura'=>$val_i,'fn_total'=>$fn_total,
            'val_afluencia'=>$val_afluencia,'val_marketing'=>$val_marketing,'val_superestructura'=>$val_super,
            'fx_total'=>$fx_total,
        ];
    }
}
