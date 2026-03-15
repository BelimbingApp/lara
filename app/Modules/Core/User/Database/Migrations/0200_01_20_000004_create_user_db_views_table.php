<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use App\Base\Database\Concerns\RegistersTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use RegistersTables;

    /**
     * Run the migrations.
     *
     * Creates the db_views table for user-owned saved SQL queries
     * that render as pinnable pages. Each user gets their own
     * independent copy (copy-on-share model).
     */
    public function up(): void
    {
        Schema::create('user_db_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 200);
            $table->text('sql_query');
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index('user_id');
        });

        $this->registerTable('user_db_views');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->unregisterTable('user_db_views');
        Schema::dropIfExists('user_db_views');
    }
};
