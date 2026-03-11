<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PREGUNTA: Se podría haber implementado "likes" con una columna counter en posts.
        // ¿Cuáles son las ventajas y desventajas de tener una tabla separada para esto?
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Polymorphic: un like puede ser para un Post o un Comment
            // PREGUNTA: ¿Qué es una relación polimórfica en Laravel? ¿Cuándo usarla
            // y cuándo es preferible una tabla dedicada por entidad?
            $table->morphs('likeable'); // crea likeable_id y likeable_type
            $table->timestamps();

            $table->unique(['user_id', 'likeable_id', 'likeable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
