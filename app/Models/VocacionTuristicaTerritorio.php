<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VocacionTuristicaTerritorio extends Model
{
    use HasFactory;

    protected $table = 'vocacion_turistica_territorio';

    protected $fillable = [
        'zona_id',
        'user_id',
        'fit',
        'fet',
        'vtt',
        'vocacion_texto',
    ];

    protected function casts(): array
    {
        return [
            'fit' => 'float',
            'fet' => 'float',
            'vtt' => 'float',
        ];
    }

    // El VTT pondera la oferta interna (FIT) sobre la demanda externa (FET).
    private const PESO_FIT = 0.60;
    private const PESO_FET = 0.40;

    private const UMBRAL_ALTA  = 2.1;
    private const UMBRAL_MEDIA = 1.1;

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * El VTT es dato derivado: se calcula a partir de FIT y FET, nunca se
     * captura del formulario.
     *
     * @return array{fit: float, fet: float, vtt: float, vocacion_texto: string}
     */
    public static function calcular(float $fit, float $fet): array
    {
        $vtt = $fit * self::PESO_FIT + $fet * self::PESO_FET;

        return [
            'fit'            => $fit,
            'fet'            => $fet,
            'vtt'            => $vtt,
            'vocacion_texto' => self::vocacionPara($vtt),
        ];
    }

    public static function vocacionPara(float $score): string
    {
        return match (true) {
            $score >= self::UMBRAL_ALTA  => 'Alta Vocación Turística',
            $score >= self::UMBRAL_MEDIA => 'Mediana Vocación Turística',
            default                      => 'Baja Vocación Turística',
        };
    }

    /**
     * Guarda la instantánea del resultado si FIT y FET ya están confirmadas.
     *
     * Se invoca al confirmar una evaluación, no al abrir la página de
     * resultados: antes bastaba con visitar un GET para escribir en la base de
     * datos y sobrescribir la autoría.
     */
    public static function registrar(int $zonaId, int $userId): ?self
    {
        $fit = EvaluacionFit::where('zona_id', $zonaId)->first();
        $fet = EvaluacionFet::where('zona_id', $zonaId)->first();

        if (! $fit || $fit->estado !== 'confirmado' || ! $fet || $fet->estado !== 'confirmado') {
            return null;
        }

        return self::updateOrCreate(
            ['zona_id' => $zonaId],
            self::calcular((float) $fit->fit, (float) $fet->fet) + ['user_id' => $userId]
        );
    }
}
