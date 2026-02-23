<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use App\Base\Authz\Database\Seeders\AuthzCapabilitySeeder;
use App\Base\Database\Concerns\RegistersSeeders;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use RegistersSeeders;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('base_authz_capabilities', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('domain')->index();
            $table->string('resource')->index();
            $table->string('action')->index();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['domain', 'action']);
        });

        $this->registerSeeder(AuthzCapabilitySeeder::class);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_authz_capabilities');
        $this->unregisterSeeder(AuthzCapabilitySeeder::class);
    }
};
