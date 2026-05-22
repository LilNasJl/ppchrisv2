<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_role')->nullable()->index();
            $table->string('action')->index();
            $table->string('model_type')->nullable()->index();
            $table->unsignedBigInteger('model_id')->nullable()->index();
            $table->string('model_label')->nullable();
            $table->string('record_label')->nullable();
            $table->string('summary')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->json('changed_data')->nullable();
            $table->timestamps();

            $table->index(['actor_role', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_histories');
    }
};
