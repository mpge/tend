<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('bucket')->default('important');
            $table->string('status')->default('open');
            $table->unsignedTinyInteger('priority')->default(0);
            $table->date('due_date')->nullable();
            $table->integer('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'bucket', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
