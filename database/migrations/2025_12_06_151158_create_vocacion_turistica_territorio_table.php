<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocacion_turistica_territorio', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('zona_id')->unique()->constrained('zonas')->onDelete('cascade'); 
            
            $table->foreignId('user_id')->nullable()->constrained('users');

            $table->decimal('fit', 8, 4);
            $table->decimal('fet', 8, 4);
            
            $table->decimal('vtt', 8, 4); 
            
            $table->string('vocacion_texto')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocacion_turistica_territorio');
    }
};