<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Workflow;

use App\Base\Workflow\Console\Commands\WorkflowAddKanbanColumnCommand;
use App\Base\Workflow\Console\Commands\WorkflowAddStatusCommand;
use App\Base\Workflow\Console\Commands\WorkflowAddTransitionCommand;
use App\Base\Workflow\Console\Commands\WorkflowCreateCommand;
use App\Base\Workflow\Console\Commands\WorkflowDescribeCommand;
use App\Base\Workflow\Console\Commands\WorkflowValidateCommand;
use App\Base\Workflow\Services\StatusManager;
use App\Base\Workflow\Services\TransitionManager;
use App\Base\Workflow\Services\TransitionValidator;
use App\Base\Workflow\Services\WorkflowEngine;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register workflow engine services.
     */
    public function register(): void
    {
        $this->app->singleton(StatusManager::class);
        $this->app->singleton(TransitionManager::class);
        $this->app->singleton(TransitionValidator::class);
        $this->app->singleton(WorkflowEngine::class);
    }

    /**
     * Bootstrap workflow commands.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                WorkflowCreateCommand::class,
                WorkflowAddStatusCommand::class,
                WorkflowAddTransitionCommand::class,
                WorkflowAddKanbanColumnCommand::class,
                WorkflowDescribeCommand::class,
                WorkflowValidateCommand::class,
            ]);
        }
    }
}
