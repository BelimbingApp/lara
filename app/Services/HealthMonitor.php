<?php
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class HealthMonitor
{
    /**
     * Get overall health status
     */
    public function getHealthStatus(): array
    {
        return [
            'status' => $this->isHealthy() ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
                'cache' => $this->checkCache(),
                'disk' => $this->checkDisk(),
                'memory' => $this->checkMemory(),
            ],
        ];
    }

    /**
     * Check if system is healthy
     */
    public function isHealthy(): bool
    {
        $checks = [
            $this->checkDatabase()['status'] === 'ok',
            $this->checkRedis()['status'] === 'ok',
            $this->checkCache()['status'] === 'ok',
            $this->checkDisk()['status'] === 'ok',
        ];

        return !in_array(false, $checks, true);
    }

    /**
     * Check database connectivity and performance
     */
    public function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = (microtime(true) - $start) * 1000; // Convert to milliseconds

            // Test a simple query
            DB::select('SELECT 1');

            return [
                'status' => 'ok',
                'response_time_ms' => round($responseTime, 2),
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    public function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $responseTime = (microtime(true) - $start) * 1000;

            // Test set/get
            $testKey = 'health_check_' . time();
            Redis::set($testKey, 'test', 'EX', 10);
            Redis::get($testKey);
            Redis::del($testKey);

            return [
                'status' => 'ok',
                'response_time_ms' => round($responseTime, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache functionality
     */
    public function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';

            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'ok',
                    'driver' => config('cache.default'),
                ];
            }

            return [
                'status' => 'error',
                'error' => 'Cache retrieval failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check disk space
     */
    public function checkDisk(): array
    {
        try {
            $storagePath = storage_path();
            $totalSpace = disk_total_space($storagePath);
            $freeSpace = disk_free_space($storagePath);
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercent = ($usedSpace / $totalSpace) * 100;

            $status = 'ok';
            if ($usagePercent > 90) {
                $status = 'warning';
            } elseif ($usagePercent > 95) {
                $status = 'error';
            }

            return [
                'status' => $status,
                'total_bytes' => $totalSpace,
                'free_bytes' => $freeSpace,
                'used_bytes' => $usedSpace,
                'usage_percent' => round($usagePercent, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check memory usage
     */
    public function checkMemory(): array
    {
        try {
            if (function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
                $current = memory_get_usage(true);
                $peak = memory_get_peak_usage(true);
                $limit = ini_get('memory_limit');

                // Convert memory limit to bytes
                $limitBytes = $this->convertToBytes($limit);

                return [
                    'status' => 'ok',
                    'current_bytes' => $current,
                    'peak_bytes' => $peak,
                    'limit_bytes' => $limitBytes,
                    'limit' => $limit,
                ];
            }

            return [
                'status' => 'unknown',
                'message' => 'Memory functions not available',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convert PHP memory limit string to bytes
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Get queue worker status
     */
    public function checkQueueWorkers(): array
    {
        try {
            // Check if queue connection is configured
            $queueConnection = config('queue.default');
            if (!$queueConnection) {
                return [
                    'status' => 'error',
                    'message' => 'Queue not configured',
                ];
            }

            // Check queue size (for database driver)
            if ($queueConnection === 'database') {
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();

                return [
                    'status' => 'ok',
                    'connection' => $queueConnection,
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                    'message' => $pendingJobs > 100 ? 'High queue depth' : 'Normal',
                ];
            }

            // For other queue drivers (Redis, SQS, etc.), basic check
            return [
                'status' => 'ok',
                'connection' => $queueConnection,
                'message' => 'Queue connection active',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }
}
