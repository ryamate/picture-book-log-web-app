<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_read_record', function (Blueprint $table) {
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('read_record_id')->constrained()->cascadeOnDelete();
            $table->string('reaction')->nullable();
            $table->primary(['child_id', 'read_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_read_record');
    }
};
