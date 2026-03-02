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
     */
    public function up(): void
    {
        Schema::table('ai_provider_models', function (Blueprint $table): void {
            $table->unsignedInteger('context_window')->nullable()->after('capability_tags');
            $table->unsignedInteger('max_tokens')->nullable()->after('context_window');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_provider_models', function (Blueprint $table): void {
            $table->dropColumn(['context_window', 'max_tokens']);
        });
    }
};
