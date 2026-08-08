<?php

namespace App\Models;

use App\Matrices\Involucrados;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Un actor de la Matriz de Involucrados: sus once criterios y los tres
 * atributos de Mitchell ya reducidos a booleano.
 */
class Involucrado extends Model
{
    protected $table = 'involucrados';

    protected $fillable = [];

    /**
     * Los tres atributos empiezan en false, igual que su default(false) en la
     * migración —puesto aquí también porque es donde alguien los ve—. Hace
     * falta el doble: Eloquent no relee los defaults de la base tras un
     * insert, así que sin esto un actor recién creado (o un `new
     * Involucrado()` sin guardar) trae tiene_poder en null, y tipo_mitchell,
     * que exige bool, revienta con un TypeError antes del primer find().
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'tiene_poder'       => false,
        'tiene_legitimidad' => false,
        'tiene_urgencia'    => false,
    ];

    /**
     * Los once criterios se declaran una sola vez, en la definición del
     * instrumento (Involucrados::campos()); repetirlos aquí a mano sería una
     * segunda fuente de verdad. Un spread de un método —a diferencia de un
     * spread de una constante de clase, como hacen las siete matrices
     * anteriores— no es una expresión constante válida para el valor por
     * defecto de una propiedad, así que se compone en el constructor.
     */
    public function __construct(array $attributes = [])
    {
        $this->fillable = [
            ...Involucrados::campos(),
            'zona_id', 'nombre', 'orden',
            'tiene_poder', 'tiene_legitimidad', 'tiene_urgencia',
        ];

        parent::__construct($attributes);
    }

    protected function casts(): array
    {
        return [
            // Los once criterios son tinyInteger en la base; sin este cast un
            // motor que los devuelva como cadena rompería en silencio
            // grado() y la comparación estricta de estaCompleto().
            ...array_fill_keys(Involucrados::campos(), 'integer'),

            'tiene_poder'       => 'boolean',
            'tiene_legitimidad' => 'boolean',
            'tiene_urgencia'    => 'boolean',
        ];
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    /** Completo cuando los once criterios están respondidos, ninguno en null. */
    public function estaCompleto(): bool
    {
        foreach (Involucrados::campos() as $campo) {
            if ($this->$campo === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Los actores a los que les falta algún criterio, sin traerse las filas
     * a PHP para contarlas: la página de zona llama a esto en cada carga
     * (ver EstadoZona::filaActores()), así que el filtro tiene que resolverse
     * en SQL.
     *
     * Los orWhereNull van agrupados en una clausura: sin el paréntesis, el
     * primer "or" se saldría de cualquier where anterior (por ejemplo un
     * ->where('zona_id', ...) previo) y devolvería filas de otras zonas con
     * cualquier criterio en null.
     */
    public function scopeIncompletos(Builder $consulta): Builder
    {
        return $consulta->where(function (Builder $query) {
            foreach (Involucrados::campos() as $campo) {
                $query->orWhereNull($campo);
            }
        });
    }

    /**
     * La suma de los criterios de un atributo, o null si falta alguno: una
     * suma parcial con huecos en 0 mentiría sobre el grado real, igual que
     * las medias nullable de las otras matrices.
     */
    public function grado(string $atributo): ?int
    {
        $suma = 0;

        foreach (array_keys(Involucrados::ATRIBUTOS[$atributo]['campos']) as $campo) {
            if ($this->$campo === null) {
                return null;
            }

            $suma += $this->$campo;
        }

        return $suma;
    }

    /**
     * Delega en Involucrados::tipoDe(): la clasificación de Mitchell es
     * conocimiento del instrumento, no del modelo.
     */
    public function getTipoMitchellAttribute(): string
    {
        return Involucrados::tipoDe($this->tiene_poder, $this->tiene_legitimidad, $this->tiene_urgencia);
    }
}
