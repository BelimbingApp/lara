<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Seeder Registry Model
 *
 * Tracks database seeders registered by migrations for registry-based execution.
 * Provides status tracking, error handling, and module filtering capabilities.
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static pending() Query seeders that are pending execution
 * @method static \Illuminate\Database\Eloquent\Builder|static failed() Query seeders that failed execution
 * @method static \Illuminate\Database\Eloquent\Builder|static completed() Query seeders that completed successfully
 * @method static \Illuminate\Database\Eloquent\Builder|static runnable() Query seeders that are pending or failed (ready to run/retry)
 * @method static \Illuminate\Database\Eloquent\Builder|static forModules(array|string $modules) Filter seeders by module name(s)
 * @method static \Illuminate\Database\Eloquent\Builder|static inMigrationOrder() Order seeders by migration file for execution order
 *
 * @property int $id
 * @property string $seeder_class Fully qualified seeder class name
 * @property string|null $module_name Module name (e.g., 'Geonames')
 * @property string|null $module_path Module path (e.g., 'app/Modules/Core/Geonames')
 * @property string|null $migration_file Migration file that registered this seeder
 * @property string $status Current execution status
 * @property \Illuminate\Support\Carbon|null $ran_at Timestamp when seeder completed
 * @property string|null $error_message Error message if seeder failed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SeederRegistry extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'base_database_seeders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'seeder_class',
        'module_name',
        'module_path',
        'migration_file',
        'status',
        'ran_at',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ran_at' => 'datetime',
    ];

    /**
     * Status: Seeder is pending execution.
     */
    const STATUS_PENDING = 'pending';

    /**
     * Status: Seeder is currently running.
     */
    const STATUS_RUNNING = 'running';

    /**
     * Status: Seeder completed successfully.
     */
    const STATUS_COMPLETED = 'completed';

    /**
     * Status: Seeder execution failed.
     */
    const STATUS_FAILED = 'failed';

    /**
     * Status: Seeder was skipped.
     */
    const STATUS_SKIPPED = 'skipped';

    /**
     * Scope to query seeders that are pending execution.
     */
    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to query seeders that failed execution.
     */
    #[Scope]
    protected function failed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to query seeders that completed successfully.
     */
    #[Scope]
    protected function completed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to query seeders that are pending or failed (ready to run/retry).
     */
    #[Scope]
    protected function runnable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_FAILED,
        ]);
    }

    /**
     * Scope to filter seeders by module name(s).
     *
     * @param  array|string  $modules
     */
    #[Scope]
    protected function forModules(Builder $query, array|string $modules): Builder
    {
        $modules = (array) $modules;

        // If wildcard "*" is present, don't filter
        if (in_array('*', $modules)) {
            return $query;
        }

        return $query->whereIn('module_name', $modules);
    }

    /**
     * Scope to order seeders by migration file for execution order.
     */
    #[Scope]
    protected function inMigrationOrder(Builder $query): Builder
    {
        return $query->orderBy('migration_file');
    }

    /**
     * Register a seeder in the registry.
     *
     * @param  string  $seederClass  Fully qualified seeder class name
     * @param  string  $moduleName  Module name (e.g., 'Geonames')
     * @param  string  $modulePath  Module path (e.g., 'app/Modules/Core/Geonames')
     * @param  string  $migrationFile  Migration file that registered this seeder
     */
    public static function register(
        string $seederClass,
        string $moduleName,
        string $modulePath,
        string $migrationFile
    ): void {
        // Use updateOrCreate to handle rollback/re-run: reset status to pending
        self::updateOrCreate(
            ['seeder_class' => $seederClass],
            [
                'module_name' => $moduleName,
                'module_path' => $modulePath,
                'migration_file' => $migrationFile,
                'status' => self::STATUS_PENDING,
                'ran_at' => null,
                'error_message' => null,
            ]
        );
    }

    /**
     * Unregister a seeder from the registry.
     *
     * @param  string  $seederClass  Fully qualified seeder class name
     */
    public static function unregister(string $seederClass): void
    {
        self::where('seeder_class', $seederClass)->delete();
    }

    /**
     * Mark this seeder as running.
     *
     * Clears any previous error message.
     */
    public function markAsRunning(): bool
    {
        return $this->update([
            'status' => self::STATUS_RUNNING,
            'error_message' => null,
        ]);
    }

    /**
     * Mark this seeder as completed.
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'ran_at' => now(),
        ]);
    }

    /**
     * Mark this seeder as failed with an error message.
     */
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Check if this seeder is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if this seeder has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if this seeder has completed.
     */
    public function hasCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
