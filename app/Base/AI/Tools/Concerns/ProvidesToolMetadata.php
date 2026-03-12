<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\AI\Tools\Concerns;

trait ProvidesToolMetadata
{
    public function displayName(): string
    {
        return $this->toolMetadata()['displayName'];
    }

    public function summary(): string
    {
        return $this->toolMetadata()['summary'];
    }

    public function explanation(): string
    {
        return $this->toolMetadata()['explanation'];
    }

    public function setupRequirements(): array
    {
        return $this->toolMetadata()['setupRequirements'];
    }

    public function testExamples(): array
    {
        return $this->toolMetadata()['testExamples'];
    }

    public function healthChecks(): array
    {
        return $this->toolMetadata()['healthChecks'];
    }

    public function limits(): array
    {
        return $this->toolMetadata()['limits'];
    }

    /**
     * @return array{
     *   displayName: string,
     *   summary: string,
     *   explanation: string,
     *   setupRequirements: list<string>,
     *   testExamples: list<array{label: string, input: array<string, mixed>, runnable?: bool}>,
     *   healthChecks: list<string>,
     *   limits: list<string>
     * }
     */
    abstract protected function toolMetadata(): array;
}
