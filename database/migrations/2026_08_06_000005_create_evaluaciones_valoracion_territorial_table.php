<?php

use App\Matrices\ValoracionTerritorial;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_valoracion_territorial', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->unique()->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');

            foreach (array_keys(ValoracionTerritorial::todos()) as $campo) {
                $table->tinyInteger($campo)->default(0);
            }

            $table->decimal('ct_total', 5, 3)->default(0);
            $table->decimal('uc_total', 5, 3)->default(0);

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_valoracion_territorial');
    }
};
