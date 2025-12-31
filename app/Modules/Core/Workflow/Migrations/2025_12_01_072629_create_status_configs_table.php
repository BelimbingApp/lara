<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blb_status_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('entity');
            $table->string('code');
            $table->string('label');
            $table->json('permissions')->nullable();
            $table->json('pic')->nullable();
            $table->json('notifications')->nullable();
            $table->json('next_statuses')->nullable();
            $table->integer('position')->default(0);
            $table->json('comment_tags')->nullable();
            $table->text('prompt')->nullable();
            $table->string('kanban_code')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamps();

            $table->unique(['entity', 'code'], 'unique_entity_code');
            $table->index('entity', 'idx_entity');
            $table->index(['entity', 'is_active'], 'idx_entity_active');
            $table->index('kanban_code', 'idx_kanban_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blb_status_configs');
    }
};
