<?php
namespace App\Http\Controllers\Operativo;

use App\Models\Zona;
use App\Models\EvaluacionPotencialidad;
use App\Models\PotencialidadCamposActivos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EvaluacionPotencialidadController extends EvaluacionZonaController
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

    /**
     * campo => etiqueta de los 156 campos, aplanando self::$secciones -que ya
     * es "Título de sección" => [campo => etiqueta]-. No copia nada nuevo:
     * es la misma estructura que ya usan getAllCampos(), edit() y
     * ponderacion() para pintar el formulario y los resultados.
     *
     * @return array<string, string>
     */
    private function etiquetas(): array
    {
        return array_merge(...array_values(self::$secciones));
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

    protected function modelo(): string
    {
        return EvaluacionPotencialidad::class;
    }

    protected function rutaResultados(): string
    {
        // A diferencia de las otras tres matrices, el formulario de Potencialidad
        // incluye también la selección de campos activos (ver edit()), así que
        // tras guardar se vuelve a él en lugar de ir a los resultados: permite
        // seguir ajustando la configuración sin un paso extra. No es una
        // inconsistencia a "corregir".
        return 'operativo.evaluacion_potencialidad.edit';
    }

    protected function mensajeCerrada(): string
    {
        return 'Evaluación cerrada. No puedes editar.';
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        // Un total puede estar sin calcular aunque la evaluación esté completa:
        // basta con que no haya ningún campo activo de ese bloque. Pasarle ese
        // null a number_format() lo pintaría como «0,00», que es exactamente el
        // resultado inventado que esta rama quita de en medio.
        $cifra = fn(?float $valor) => $valor === null ? 'sin calcular' : number_format($valor, 2);

        return $estado === 'confirmado'
            ? 'Evaluación CONFIRMADA. FN: ' . $cifra($datos['fn_total'])
              . ' | FX: ' . $cifra($datos['fx_total'])
            : 'Borrador guardado correctamente.';
    }

    protected function prepararDatos(Request $request, $zonaId, ?Model $actual, string $estado): array
    {
        $user = Auth::user();

        // La selección de campos se valida contra la lista blanca antes de nada:
        // sus valores se usan como claves de reglas de validación y se serializan
        // a la columna JSON, así que no puede entrar texto arbitrario.
        $request->validate([
            'campos'   => 'nullable|array',
            'campos.*' => ['string', Rule::in($this->getAllCampos())],
        ]);

        $esJefe = $user->esJefe();

        if ($esJefe) {
            $camposActivos = $request->input('campos', []);
        } else {
            // Equipo: conservar la configuración actual del Jefe
            $camposActivos = $this->camposActivosDe($zonaId);
        }

        // Validar solo los campos activos. Solo se exige responderlos todos al
        // confirmar: con 156 criterios, perder el avance por no tenerlos todos
        // era lo que más dolía al usar esto de verdad.
        $obligatoriedad = $estado === 'confirmado' ? 'required' : 'nullable';

        $reglas = [];
        foreach ($camposActivos as $campo) {
            $reglas[$campo] = "{$obligatoriedad}|integer|min:0|max:2";
        }
        // Tercer argumento, no una entrada por campo en lang/es/validation.php:
        // con 156 criterios esa lista se desincronizaría de self::$secciones en
        // cuanto alguien renombrara una etiqueta ahí. Ver el mismo razonamiento
        // en MatrizPonderadaController::etiquetas(), que esta clase no hereda
        // -Potencialidad no es una MatrizPonderadaController- pero sigue igual.
        $request->validate($reglas, [], $this->etiquetas());

        // Todo validado: recién ahora se persiste la configuración, para que un
        // error de validación no deje la selección de campos ya modificada.
        if ($esJefe) {
            PotencialidadCamposActivos::updateOrCreate(
                ['zona_id' => $zonaId],
                ['campos_activos' => $camposActivos]
            );
        }

        // Un campo activo sin responder entra como null, no como 0: con 156
        // criterios, «no lo he mirado» puntuando como «Nulo» hundía la media de
        // su grupo sin que nadie lo notara. Los desactivados conservan lo que ya
        // tuvieran, por si vuelven a activarse.
        $valores = [];
        foreach ($this->getAllCampos() as $campo) {
            if (! in_array($campo, $camposActivos, true)) {
                $valores[$campo] = $actual->$campo ?? null;
                continue;
            }

            $bruto = $request->input($campo);
            $valores[$campo] = $bruto === null ? null : (int) $bruto;
        }

        $this->camposActivosEnMemoria = $camposActivos;

        return $valores + $this->calcular($valores, $camposActivos);
    }

    /** Campos activos guardados para la zona, o todos si nunca se configuró. */
    private function camposActivosDe($zonaId): array
    {
        $config = PotencialidadCamposActivos::where('zona_id', $zonaId)->first();

        return $config ? $config->campos_activos : $this->getAllCampos();
    }

    /**
     * Campos activos de la última llamada a prepararDatos().
     *
     * estaCompleta() se ejecuta después de guardar y necesita saber cuáles eran
     * obligatorios en ESA petición, no los que hubiera configurados antes.
     *
     * @var list<string>|null
     */
    private ?array $camposActivosEnMemoria = null;

    protected function estaCompleta(array $datos): bool
    {
        foreach ($this->camposActivosEnMemoria ?? [] as $campo) {
            if (($datos[$campo] ?? null) === null) {
                return false;
            }
        }

        return true;
    }

    protected function mensajeIncompleto(array $datos): string
    {
        $activos = $this->camposActivosEnMemoria ?? [];

        $respondidos = count(array_filter(
            $activos,
            fn(string $campo) => ($datos[$campo] ?? null) !== null
        ));

        return "Borrador guardado. Llevas {$respondidos} de " . count($activos) . ' criterios activos.';
    }

    // ── Reconfigurar: activa todos los campos (reset) ─────────────────────────
    public function reconfigurarCampos($zonaId)
    {
        $user = Auth::user();
        if ($user->esEquipo()) {
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
        $config = PotencialidadCamposActivos::where('zona_id', $zonaId)->first();
        $camposActivos = $config ? $config->campos_activos : $this->getAllCampos();
        $secciones = self::$secciones;
        return view('operativo.evaluacion_potencialidad.ponderacion', compact('zona', 'eval', 'camposActivos', 'secciones'));
    }

    // ── Cálculo ponderado usando solo los campos activos ─────────────────────
    private function calcular(array $v, array $camposActivos): array
    {
        // Un campo sin responder no entra en el promedio. isset() no servía:
        // decía que sí a un 0 —que es una respuesta— y también a un null, que
        // no lo es. null aquí significa «ningún campo activo respondido»: no
        // hay nada que promediar, y devolver 0 sería inventarse un resultado.
        $avg = function(array $candidatos) use ($v, $camposActivos): ?float {
            $respondidos = array_filter(
                $candidatos,
                fn($c) => in_array($c, $camposActivos, true) && ($v[$c] ?? null) !== null
            );

            if ($respondidos === []) {
                return null;
            }

            return array_sum(array_map(fn($c) => (float) $v[$c], $respondidos)) / count($respondidos);
        };

        // Sigue significando «tiene campos activos», no «tiene respuestas»: un
        // grupo activo que nadie respondió no debe caerse de la ponderación
        // como si no existiera, tiene que dejar el total sin calcular.
        $hasCampos = fn(array $lista) => !empty(array_intersect($camposActivos, $lista));

        /**
         * Promedia los grupos de un factor. Cada grupo cuenta solo si tiene
         * campos activos, y basta con que uno de esos esté sin responder para
         * que el factor entero se quede sin valor: a un promedio al que le
         * falta un grupo le pasa lo mismo que a una media a la que le falta un
         * criterio, parece un resultado y mide otra cosa.
         *
         * @param list<array{0: list<string>, 1: ?float}> $pares campos y valor de cada grupo
         */
        $mediaGrupos = function(array $pares) use ($hasCampos): ?float {
            $valores = [];

            foreach ($pares as [$campos, $valor]) {
                if (! $hasCampos($campos)) {
                    continue;
                }

                if ($valor === null) {
                    return null;
                }

                $valores[] = $valor;
            }

            return $valores === [] ? null : array_sum($valores) / count($valores);
        };

        // ── Recursos Naturales ──────────────────────────────────────────────
        $litoral = ['rn_litoral_playas','rn_litoral_arrecifes','rn_litoral_cuevas','rn_litoral_flora_fauna','rn_litoral_actividades_acuaticas','rn_litoral_areas_deserticas'];
        $montana = ['rn_montana_montanas','rn_montana_sierras','rn_montana_canadas','rn_montana_canones','rn_montana_cuevas','rn_montana_geisers','rn_montana_volcanes','rn_montana_valles','rn_montana_bosques','rn_montana_flora_fauna','rn_montana_areas_deserticas'];
        $anp     = ['rn_anp_reservas_marinas','rn_anp_reserva_geobotanica','rn_anp_reserva_ecologica','rn_anp_reserva_fauna','rn_anp_reserva_biologica','rn_anp_reserva_vida_silvestre','rn_anp_parques_nacionales','rn_anp_area_privada','rn_anp_area_comunitaria','rn_anp_area_recreacion','rn_anp_area_conservacion'];
        $agua    = ['rn_agua_lagos','rn_agua_rios','rn_agua_cascadas','rn_agua_termas','rn_agua_esteros'];

        $rn_litoral = $avg($litoral); $rn_montana = $avg($montana);
        $rn_anp = $avg($anp);         $rn_agua = $avg($agua);

        $val_rn = $mediaGrupos([[$litoral, $rn_litoral], [$montana, $rn_montana], [$anp, $rn_anp], [$agua, $rn_agua]]);

        // ── Recursos Culturales ─────────────────────────────────────────────
        $am = ['rc_am_zonas_arqueologicas','rc_am_fosiles','rc_am_pinturas_rupestres','rc_am_ciudades_coloniales','rc_am_pueblos_antiguos','rc_am_patrimonio_humanidad','rc_am_santuarios'];
        $np = ['rc_np_grupos_etnicos','rc_np_expresiones_artisticas','rc_np_ferias_mercados','rc_np_eventos_folkloricos','rc_np_eventos_historicos'];
        $ec = ['rc_ec_obras_arte','rc_ec_centros_cientificos','rc_ec_explotaciones_mineras','rc_ec_plantaciones','rc_ec_complejos_industriales'];

        $rc_am = $avg($am); $rc_np = $avg($np); $rc_ec = $avg($ec);

        $val_rc = $mediaGrupos([[$am, $rc_am], [$np, $rc_np], [$ec, $rc_ec]]);

        // RT: redistribuir peso entre RN y RC según campos activos. Con los dos
        // activos la media de ambos es el 0.5/0.5 de siempre; con uno solo, ese
        // uno. Sin ninguno se queda en 0: no es un resultado, es que RT no entra
        // en la ponderación de FN, y así estaba congelado por test.
        $allRN = array_merge($litoral, $montana, $anp, $agua);
        $allRC = array_merge($am, $np, $ec);
        $hasRN = $hasCampos($allRN);
        $hasRC = $hasCampos($allRC);

        $val_rt = ($hasRN || $hasRC)
            ? $mediaGrupos([[$allRN, $val_rn], [$allRC, $val_rc]])
            : 0;

        // ── Planta Turística ────────────────────────────────────────────────
        $aloj  = ['pt_aloj_hoteles','pt_aloj_hostales','pt_aloj_hosterias','pt_aloj_haciendas','pt_aloj_lodges','pt_aloj_resorts','pt_aloj_refugios','pt_aloj_campamentos','pt_aloj_casa_huespedes','pt_aloj_ctc'];
        $rest  = ['pt_rest_restaurantes','pt_rest_cafeterias','pt_rest_bares','pt_rest_discotecas','pt_rest_moviles','pt_rest_plazas_comida','pt_rest_catering','pt_rest_ctc'];
        $inter = ['pt_inter_mayoristas','pt_inter_internacionales','pt_inter_operadoras','pt_inter_duales','pt_inter_ctc'];
        $trans = ['pt_trans_terrestre','pt_trans_ferroviaria','pt_trans_aerea','pt_trans_maritima','pt_trans_fluvial','pt_trans_ctc'];
        $guia  = ['pt_guia_locales','pt_guia_nacionales','pt_guia_patrimonio','pt_guia_aventura'];

        $pt_aloj  = $avg($aloj);  $pt_rest  = $avg($rest);  $pt_inter = $avg($inter);
        $pt_trans = $avg($trans); $pt_guia  = $avg($guia);

        $val_pt = $mediaGrupos([[$aloj, $pt_aloj], [$rest, $pt_rest], [$inter, $pt_inter], [$trans, $pt_trans], [$guia, $pt_guia]]);

        // ── Tipologías e Infraestructura ────────────────────────────────────
        $tt_campos = array_keys(self::$secciones['Tipologías de Turismo']);
        $i_campos  = array_keys(self::$secciones['Infraestructura']);
        $val_tt = $avg($tt_campos);
        $val_i  = $avg($i_campos);

        // FN: redistribuir pesos entre factores que sí tengan campos activos
        $fn_pesos = [];
        $allRT = array_merge($allRN, $allRC);
        if ($hasCampos($allRT))    $fn_pesos['rt'] = 0.40;
        if ($hasCampos($aloj) || $hasCampos($rest) || $hasCampos($inter) || $hasCampos($trans) || $hasCampos($guia))
                                   $fn_pesos['pt'] = 0.20;
        if ($hasCampos($tt_campos)) $fn_pesos['tt'] = 0.20;
        if ($hasCampos($i_campos))  $fn_pesos['i']  = 0.20;

        $fn_total = $this->totalPonderado($fn_pesos, [
            'rt' => $val_rt, 'pt' => $val_pt, 'tt' => $val_tt, 'i' => $val_i,
        ]);

        // ── Factores Exógenos ───────────────────────────────────────────────
        $at_campos = array_keys(self::$secciones['Afluencia Turística']);
        $mk_campos = array_keys(self::$secciones['Marketing Turístico']);
        $st_campos = array_keys(self::$secciones['Superestructura']);

        $val_afluencia   = $avg($at_campos);
        $val_marketing   = $avg($mk_campos);
        $val_superestructura = $avg($st_campos);

        // FX: redistribuir pesos entre factores que sí tengan campos activos
        $fx_pesos = [];
        if ($hasCampos($at_campos)) $fx_pesos['at'] = 0.40;
        if ($hasCampos($mk_campos)) $fx_pesos['mk'] = 0.30;
        if ($hasCampos($st_campos)) $fx_pesos['st'] = 0.30;

        $fx_total = $this->totalPonderado($fx_pesos, [
            'at' => $val_afluencia, 'mk' => $val_marketing, 'st' => $val_superestructura,
        ]);

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

    /**
     * Suma ponderada de los factores, renormalizando los pesos entre los que
     * tienen campos activos.
     *
     * Devuelve null si algún factor ponderado está sin responder, y también si
     * no hay ningún factor activo. Descontar el que falta daría un número que
     * se lee como el total del instrumento cuando en realidad está medido sobre
     * otra escala: es el mismo engaño que promediar sobre los criterios
     * respondidos, una capa más arriba.
     *
     * @param array<string, float>  $pesos       factor => peso, solo los activos
     * @param array<string, ?float> $componentes factor => valor calculado
     */
    private function totalPonderado(array $pesos, array $componentes): ?float
    {
        if ($pesos === []) {
            return null;
        }

        $suma  = array_sum($pesos);
        $total = 0.0;

        foreach ($pesos as $factor => $peso) {
            if ($componentes[$factor] === null) {
                return null;
            }

            $total += $componentes[$factor] * ($peso / $suma);
        }

        return $total;
    }
}
