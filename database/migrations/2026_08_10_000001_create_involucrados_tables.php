<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matriz de Involucrados Turísticos Territoriales: dos tablas, no una.
 *
 * Las siete matrices anteriores tienen una fila por zona porque puntúan un
 * formulario de criterios fijo. Esta puntúa una lista variable de actores, y
 * eso obliga a separar dos cosas que en las demás vivían juntas en la misma
 * fila:
 *
 *   - `involucrados_config` es el estado del CONJUNTO: borrador o confirmado,
 *     quién lo confirmó y cuándo. Va aparte porque el resultado del
 *     instrumento se normaliza dividiendo por el total de actores, y
 *     "validar" solo tiene sentido para la lista entera. Guardar el estado en
 *     cada actor obligaría a preguntar "¿están todos de acuerdo?" cada vez
 *     que alguien confirma, y confirmar la mitad de los actores no significa
 *     nada: no hay un "actor a medio confirmar", el conjunto se confirma
 *     entero o no se confirma.
 *
 *   - `involucrados` es un actor por fila: nombre, orden en la lista y sus
 *     once criterios. Nace y muere con el CRUD de actores, sin más estado que
 *     los propios criterios.
 *
 * Los once criterios son tinyInteger NULLABLE y sin defecto, igual que ya
 * hicieron las migraciones "criterios_nullable_*" para las siete matrices
 * anteriores: un criterio sin responder no es un 0. Aquí es más importante
 * todavía, porque en este instrumento 0 no es "el peor valor de la escala",
 * es "no lo posee" —una valoración real y distinta de "todavía no se ha
 * mirado"—. Un defecto en 0 convertiría todo actor recién creado en un actor
 * ya evaluado sin poder, legitimidad ni urgencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('involucrados_config', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->unique()->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');

            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('involucrados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->string('nombre', 200);
            $table->unsignedInteger('orden')->default(0);

            // Poder — 7 criterios.
            $table->tinyInteger('pod_autoridad')->nullable();
            $table->tinyInteger('pod_poder')->nullable();
            $table->tinyInteger('pod_recursos')->nullable();
            $table->tinyInteger('pod_presupuesto')->nullable();
            $table->tinyInteger('pod_tecnologicos')->nullable();
            $table->tinyInteger('pod_cadena_valor')->nullable();
            $table->tinyInteger('pod_intelectuales')->nullable();

            // Legitimidad — 2 criterios.
            $table->tinyInteger('leg_territorio')->nullable();
            $table->tinyInteger('leg_sociedad')->nullable();

            // Urgencia — 2 criterios.
            $table->tinyInteger('urg_sensibilidad')->nullable();
            $table->tinyInteger('urg_criticidad')->nullable();

            // Los tres atributos reducidos a "lo tiene o no", listos para
            // Involucrados::tipoDe(). Se guardan en vez de recalcularse al
            // vuelo porque decidir "tiene poder" a partir de los siete
            // criterios es una regla de negocio (¿algún criterio a 0 ya
            // basta para decir que no lo tiene? ¿hace falta un umbral?) que
            // pertenece a la tarea 3, no a esta capa de datos.
            $table->boolean('tiene_poder')->default(false);
            $table->boolean('tiene_legitimidad')->default(false);
            $table->boolean('tiene_urgencia')->default(false);

            $table->timestamps();

            $table->index('zona_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('involucrados');
        Schema::dropIfExists('involucrados_config');
    }
};
