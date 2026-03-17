<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('potencialidad_campos_activos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zonas')->onDelete('cascade');
            // JSON con la lista de campos activos para esta zona
            $table->json('campos_activos');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('potencialidad_campos_activos');
    }
};
