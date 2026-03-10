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
     * Stores per-user pinned menu items for sidebar quick-access.
     * Each row is a menu_item_id pinned by a user, with a sort_order
     * for drag-reorder persistence. The menu_item_id is a string matching
     * MenuItem::$id (not a foreign key to another table — menu items are
     * discovered at runtime from config files, not stored in the database).
     */
    public function up(): void
    {
        Schema::create('user_pinned_menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('menu_item_id', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'menu_item_id']);
            $table->index(['user_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_pinned_menu_items');
    }
};
