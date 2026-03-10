<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the user_pins table for per-user pinned sidebar items.
     *
     * Supports two pin types:
     * - menu_item: references a MenuItem::$id from the runtime menu registry
     * - page: references an arbitrary page by URL (e.g. a tool workspace)
     *
     * The pinnable_id is a string key (not a foreign key) since menu items
     * are discovered at runtime and page pins use route-based identifiers.
     */
    public function up(): void
    {
        Schema::create('user_pins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('pinnable_id', 150);
            $table->string('label', 150);
            $table->string('url', 500);
            $table->string('icon', 100)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'type', 'pinnable_id']);
            $table->index(['user_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_pins');
    }
};
