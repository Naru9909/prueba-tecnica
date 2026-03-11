<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PREGUNTA: Esta tabla guarda el historial de versiones de un post.
        // ¿Qué patrón de diseño de base de datos representa esto?
        // ¿Cómo se llama este patrón y cuáles son sus implicaciones de almacenamiento?
        Schema::create('post_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // quien hizo la revisión
            $table->string('title');
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->integer('revision_number');
            $table->string('change_summary')->nullable();
            $table->timestamp('created_at');

            $table->index(['post_id', 'revision_number']);
        });

        // Tabla de notificaciones del sistema
        // PREGUNTA: Laravel tiene su propia tabla de notifications integrada.
        // ¿En qué se diferencia esta implementación manual de la que provee Laravel?
        // ¿Cuándo preferirías una sobre la otra?
        //
        // NOTA: La tabla 'notifications' ya es creada por Laravel/Sanctum si se usa
        // el sistema de notificaciones nativo. morphs() crea su propio índice, por eso
        // no hay que agregar uno manual con $table->index() — PREGUNTA: ¿Qué método de
        // Blueprint crea automáticamente el índice compuesto al usar morphs()?
        Schema::create('blog_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable'); // genera: notifiable_id, notifiable_type + índice
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_notifications');
        Schema::dropIfExists('post_revisions');
    }
};
