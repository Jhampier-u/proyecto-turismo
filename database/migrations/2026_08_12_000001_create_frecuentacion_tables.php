<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice Espacial de Frecuentación Turística: dos tablas, mismo reparto que
 * Involucrados y por el mismo motivo -lo que se valida es el conjunto
 * entero, no una fila suelta- con un escalar de más.
 *
 * `frecuentacion_config` lleva el estado del conjunto Y la Superficie
 * Territorial (ST): un dato de la ZONA, compartido por todos sus sitios, no
 * uno por sitio. Guardarlo junto a cada sitio lo repetiría sin necesidad, y
 * guardarlo en una fila aparte obligaría a inventar un sitio especial que no
 * es un sitio.
 *
 * `frecuentacion_sitios` es un sitio por fila: nombre y su DET (Densidad/
 * Densidad Espacial Turística; la unidad no está confirmada y no bloquea,
 * ver el diseño).
 *
 * `st` y `det` son `decimal`, nullable y sin defecto -un sitio recién creado
 * no tiene "cero frecuentación", tiene "sin responder todavía", igual que
 * los criterios de Involucrados-. Dígitos generosos: decimal(14,4), para no
 * repetir el aprieto que dejó anotado la migración de Irritación
 * (decimal(5,3) "va justo de dígitos").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frecuentacion_config', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->unique()->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');
            $table->decimal('st', 14, 4)->nullable();

            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('frecuentacion_sitios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->string('nombre', 200);
            $table->unsignedInteger('orden')->default(0);
            $table->decimal('det', 14, 4)->nullable();

            $table->timestamps();

            $table->index('zona_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frecuentacion_sitios');
        Schema::dropIfExists('frecuentacion_config');
    }
};
