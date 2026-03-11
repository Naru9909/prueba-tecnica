<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Para anidamiento de comentarios (respuestas)
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');

            $table->text('body');
            $table->enum('status', ['pending', 'approved', 'spam', 'rejected'])->default('pending');

            // PREGUNTA: ¿Qué problema de seguridad puede haber si 'body' se muestra
            // directamente en el frontend sin sanitizar? ¿Cómo lo prevendrías en Laravel?
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // PREGUNTA: ¿Falta algún índice aquí? ¿Cuáles queries frecuentes no estarían optimizadas?
            $table->index(['post_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
