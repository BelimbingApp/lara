<?php
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong

namespace App\Http\Controllers;

use App\Services\HealthMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function __construct(
        protected HealthMonitor $healthMonitor
    ) {}

    /**
     * Health check endpoint (for load balancers and monitoring)
     * Returns 200 if healthy, 503 if unhealthy
     */
    public function health(): JsonResponse
    {
        $status = $this->healthMonitor->getHealthStatus();
        $httpStatus = $status['status'] === 'healthy' ? 200 : 503;

        return response()->json($status, $httpStatus);
    }

    /**
     * Detailed health dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $status = $this->healthMonitor->getHealthStatus();

        // Add additional metrics
        $status['metrics'] = [
            'queue_workers' => $this->healthMonitor->checkQueueWorkers(),
            'log_file_size' => $this->getLogFileSize(),
        ];

        return response()->json($status);
    }

    /**
     * Get log file size
     */
    private function getLogFileSize(): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (file_exists($logPath)) {
            $size = filesize($logPath);
            return [
                'size_bytes' => $size,
                'size_mb' => round($size / 1024 / 1024, 2),
                'status' => $size > 100 * 1024 * 1024 ? 'warning' : 'ok', // > 100MB
            ];
        }

        return [
            'status' => 'ok',
            'message' => 'Log file does not exist',
        ];
    }
}
