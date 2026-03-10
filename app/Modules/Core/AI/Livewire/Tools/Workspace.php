<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>
//
// Per-tool Workspace — overview, setup checklist, health, and test examples.

namespace App\Modules\Core\AI\Livewire\Tools;

use App\Modules\Core\AI\Enums\ToolHealthState;
use App\Modules\Core\AI\Enums\ToolReadiness;
use App\Modules\Core\AI\Services\ToolMetadataRegistry;
use App\Modules\Core\AI\Services\ToolReadinessService;
use Livewire\Component;

class Workspace extends Component
{
    public string $toolName;

    public function render(): \Illuminate\Contracts\View\View
    {
        $metadataRegistry = app(ToolMetadataRegistry::class);
        $readinessService = app(ToolReadinessService::class);

        $metadata = $metadataRegistry->get($this->toolName);

        if (! $metadata) {
            return view('livewire.ai.tools.workspace', [
                'metadata' => null,
                'readiness' => ToolReadiness::UNAVAILABLE,
                'health' => ToolHealthState::UNKNOWN,
            ]);
        }

        return view('livewire.ai.tools.workspace', [
            'metadata' => $metadata,
            'readiness' => $readinessService->readiness($this->toolName),
            'health' => $readinessService->health($this->toolName),
        ]);
    }
}
