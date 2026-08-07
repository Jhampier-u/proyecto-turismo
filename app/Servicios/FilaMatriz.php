<?php

namespace App\Servicios;

/**
 * Una fila de la página de zona, ya resuelta.
 *
 * La vista no decide nada: recibe esto y lo pinta.
 */
final class FilaMatriz
{
    public function __construct(
        public readonly string  $clave,
        public readonly string  $nombre,
        public readonly string  $icono,
        /** sin_empezar | borrador | validada | bloqueada | sin_estado */
        public readonly string  $estado,
        public readonly string  $detalle,
        public readonly ?string $url,
        /** Empezar | Continuar | Ver | Abrir | null cuando no procede */
        public readonly ?string $accion,
        public readonly bool    $puedeValidar = false,
        public readonly ?string $avisoValidacion = null,
    ) {
    }
}
