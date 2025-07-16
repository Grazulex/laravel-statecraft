<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('state_machine_history', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('state_machine');
            $table->string('transition')->nullable();
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->string('guard')->nullable();
            $table->string('action')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('state_machine_history');
    }
};
