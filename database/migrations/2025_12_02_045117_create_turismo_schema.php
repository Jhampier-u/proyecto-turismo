<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->string('telefono')->nullable();
        });

        Schema::create('regiones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
        });

        Schema::create('provincias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regiones')->onDelete('cascade');
            $table->string('nombre');
        });

        Schema::create('lugares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincia_id')->constrained('provincias')->onDelete('cascade');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
        });

        Schema::create('zonas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lugar_id')->constrained('lugares');
            $table->foreignId('jefe_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('zona_equipo', function (Blueprint $table) {
            $table->foreignId('zona_id')->constrained('zonas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('asignado_at')->useCurrent();
            $table->primary(['zona_id', 'user_id']);
        });

        Schema::create('categorias_recurso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categorias_recurso')->nullOnDelete();
            $table->string('nombre');
            $table->string('codigo_ficha')->nullable();
        });

        Schema::create('tipos_propietario', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
        });

        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zonas')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categorias_recurso');
            $table->foreignId('propietario_id')->nullable()->constrained('tipos_propietario');
            $table->foreignId('creado_por_user_id')->constrained('users'); 

            $table->string('nombre_recurso');
            $table->string('ubicacion_detallada')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('accesibilidad')->nullable();
            $table->text('equipamiento_servicios')->nullable();
            $table->string('estado_conservacion')->nullable(); 
            $table->timestamps();
        });

        Schema::create('inventario_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_id')->constrained('inventarios')->onDelete('cascade');
            $table->string('ruta_archivo'); 
            $table->string('descripcion')->nullable();
        });

Schema::create('evaluaciones_fit', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('zona_id')->constrained('zonas')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');

            
            $table->tinyInteger('recursos_culturales')->default(0);
            $table->tinyInteger('recursos_naturales')->default(0);
            $table->tinyInteger('atractivos_manifestaciones')->default(0);
            $table->tinyInteger('atractivos_sitios')->default(0);
            $table->tinyInteger('prestadores_alojamiento')->default(0);
            $table->tinyInteger('prestadores_restauracion')->default(0);
            $table->tinyInteger('prestadores_guianza')->default(0);
            $table->tinyInteger('productos_territoriales')->default(0);
            
            $table->tinyInteger('infraestructura_basica')->default(0);
            $table->tinyInteger('infraestructura_apoyo')->default(0);
            
            $table->tinyInteger('facilidades_senaletica')->default(0);
            $table->tinyInteger('facilidades_recepcion')->default(0);
            $table->tinyInteger('facilidades_interpretacion')->default(0);
            $table->tinyInteger('facilidades_senderos')->default(0);
            $table->tinyInteger('facilidades_estacionamientos')->default(0);
            $table->tinyInteger('facilidades_campamentos')->default(0);
            $table->tinyInteger('facilidades_miradores')->default(0);
            $table->tinyInteger('facilidades_sanitarios')->default(0);


            $table->decimal('media_rtt', 5, 2)->nullable(); 
            $table->decimal('fit_rtt', 5, 2)->nullable(); 
            
            $table->decimal('media_at', 5, 2)->nullable();
            $table->decimal('fit_at', 5, 2)->nullable();
            
            $table->decimal('media_pst', 5, 2)->nullable();
            $table->decimal('fit_pst', 5, 2)->nullable();
            
            $table->decimal('media_ptt', 5, 2)->nullable();
            $table->decimal('fit_ptt', 5, 2)->nullable();

            $table->decimal('media_i', 5, 2)->nullable();
            $table->decimal('fit_i', 5, 2)->nullable();

            $table->decimal('media_ft', 5, 2)->nullable();
            $table->decimal('fit_ft', 5, 2)->nullable();

            $table->decimal('fit', 8, 4)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_fit');
        Schema::dropIfExists('inventario_imagenes');
        Schema::dropIfExists('inventarios');
        Schema::dropIfExists('tipos_propietario');
        Schema::dropIfExists('categorias_recurso');
        Schema::dropIfExists('zona_equipo');
        Schema::dropIfExists('zonas');
        Schema::dropIfExists('lugares');
        Schema::dropIfExists('provincias');
        Schema::dropIfExists('regiones');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'telefono']);
        });
        
        Schema::dropIfExists('roles');
        
        Schema::dropIfExists('evaluacion_valores');
        Schema::dropIfExists('evaluaciones');
        Schema::dropIfExists('matriz_criterios');
        Schema::dropIfExists('matriz_variables');
    }
};