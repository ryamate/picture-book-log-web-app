<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('read_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('picture_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->date('read_date');
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->index(['family_id', 'read_date']);
            $table->index(['picture_book_id', 'read_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('read_records');
    }
};
