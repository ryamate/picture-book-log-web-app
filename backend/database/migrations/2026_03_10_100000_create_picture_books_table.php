<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('picture_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('google_books_id')->nullable();
            $table->string('isbn')->nullable();
            $table->string('title');
            $table->json('authors');
            $table->string('thumbnail_url')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('read_status')->default('unread');
            $table->text('review')->nullable();
            $table->timestamps();

            $table->index(['family_id', 'read_status']);
            $table->index(['family_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('picture_books');
    }
};
