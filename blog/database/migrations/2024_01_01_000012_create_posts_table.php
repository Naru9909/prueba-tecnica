<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();

            // PREGUNTA: ¿Cuál es la diferencia entre text, mediumText y longText en MySQL?
            // ¿Cuál debería usarse aquí y por qué?
            $table->longText('content');

            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'published', 'archived', 'scheduled'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->integer('reading_time')->default(0); // en minutos
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_comments')->default(true);

            // PREGUNTA: ¿Por qué almacenar views_count directamente en la tabla puede ser problemático
            // en un sistema con alta concurrencia? ¿Qué alternativa existe?
            $table->unsignedInteger('views_count')->default(0);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // PREGUNTA: ¿Este índice compuesto es suficiente? ¿Qué queries comunes no está cubriendo?
            $table->index(['status', 'published_at']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
