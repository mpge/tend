<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habit_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->timestamps();

            $table->unique(['habit_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_entries');
    }
};
